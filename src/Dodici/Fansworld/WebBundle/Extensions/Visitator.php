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

    function __construct(SecurityContext $security_context, EntityManager $em)
    {
        $this->request = Request::createFromGlobals();
        $this->security_context = $security_context;
        $this->em = $em;
        $this->user = null;
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
    	$visit = new Visit();
    	$visit->setIp($this->request->getClientIp());
    	if ($this->user) $visit->setAuthor($this->user);
    	$entity->addVisit($visit);
		
    	$this->em->persist($entity);
        $this->em->flush();
		
		return $visit;
    }    
}