<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Complaint;

use Doctrine\ORM\EntityManager;

class Complainer
{
    protected $request;
	protected $em;
	protected $appstate;

    function __construct(EntityManager $em, $appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
    }

    /**
     * Create a complaint on entity, authored by user
     * @param User $user
     * @param $entity (User|Comment|Photo|Video)
     * @param string $content
     */
    public function complain(User $user, $entity, $content)
    {
    	$classname = $this->appstate->getType($entity);
    	
    	$complaint = new Complaint();
		$complaint->setAuthor($user);
		$complaint->setContent($content);
		
		if ($entity instanceof User) {
			$complaint->setTarget($entity);
		} else {
			$methodname = 'set'.$classname;
			$complaint->$methodname($entity);
			if (method_exists($entity, 'getAuthor')) {
				$complaint->setTarget($entity->getAuthor());
			}
		}
		
    	$this->em->persist($complaint);
			
		$this->em->flush();
    }    
}