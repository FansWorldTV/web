<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\SecurityContext;
use Dodici\Fansworld\WebBundle\Model\VisitableInterface;
use Dodici\Fansworld\WebBundle\Entity\Visit;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class Visitator
{
	protected $request;
	protected $security_context;
	protected $em;
    protected $user;
    protected $session;
    protected $appstate;
    protected $videoplaylist;
    const LIMIT_VISIT = 10;

    function __construct(SecurityContext $security_context, EntityManager $em, $session,$appstate, $videoplaylist)
    {
        $this->request = Request::createFromGlobals();
        $this->security_context = $security_context;
        $this->em = $em;
        $this->user = null;
        $this->session = $session;
        $this->appstate = $appstate;
        $this->videoplaylist = $videoplaylist;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Check sesion for timestamp, if correct add visit
     * @param $entity
     */
    public function visit(VisitableInterface $entity, $device=null)
    {
        $visit = null;
        if ($this->shouldAddVisit($entity)) {
            $visit = $this->addVisit($entity, $device);
            $this->updateLastVisit($entity);
        }

        $user = $this->user;

        if ($user && $entity instanceof Video) {
            if ($this->videoplaylist->isInPlaylist($entity, $user)) {
                $this->videoplaylist->remove($entity, $user);
            }
        }

    	return $visit;
    }

    /**
     * Create a visit object, add to entity
     * @param $entity
     */
    private function addVisit(VisitableInterface $entity, $device)
    {
        $type = $this->appstate->getType($entity);

    	$visit = new Visit();
        $visit->setDevice($device);
    	$visit->setIp($this->request->getClientIp());
    	if ($this->user) $visit->setAuthor($this->user);
    	$entity->addVisit($visit);
    	$this->em->persist($entity);
        $this->em->flush();
		return $visit;
    }

    private function updateLastVisit($entity){
        $type = $this->appstate->getType($entity);
        $this->session->set($type.'_lastvisit',time());
    }

    private function shouldAddVisit($entity){
        $shouldUpdate = false;
        $type = $this->appstate->getType($entity);
        if($this->session->has($type.'_lastvisit')){
    	    $lastVisit = $this->session->get($type.'_lastvisit');

    	    if( time() > $lastVisit + self::LIMIT_VISIT * 60 ){
    	        $shouldUpdate = true;
    	    }
    	}else $shouldUpdate = true;

    	return $shouldUpdate;
    }
}