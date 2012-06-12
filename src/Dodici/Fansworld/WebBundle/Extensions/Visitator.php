<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\Security\Core\SecurityContext;
use Dodici\Fansworld\WebBundle\Model\VisitableInterface;
use Dodici\Fansworld\WebBundle\Entity\Visit;
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
    const LIMIT_VISIT = 10;

    function __construct(SecurityContext $security_context, EntityManager $em, $session,$appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->security_context = $security_context;
        $this->em = $em;
        $this->user = null;
        $this->session = $session;
        $this->appstate = $appstate;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Create a visit object, add to entity
     * @param $entity
     */
    public function visit(VisitableInterface $entity)
    {
        $type = $this->appstate->getType($entity);
        
    	$visit = new Visit();
    	$visit->setIp($this->request->getClientIp());
    	if ($this->user) $visit->setAuthor($this->user);
    	$entity->addVisit($visit);
		
    	$this->em->persist($entity);
        $this->em->flush();
		
		return $visit;
    }    
    
    /**
     * Check sesion for timestamp, if correct add visit
     * @param $entity
     */
    public function addVisit(VisitableInterface $entity)
    {
        $visit = null;
        if($this->shouldAddVisit($entity)){
            $visit = $this->visit($entity);
            $this->updateLastVisit($entity);
        }
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