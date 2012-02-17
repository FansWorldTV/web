<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Complaint;

use Doctrine\ORM\EntityManager;

class Complainer
{
	protected $request;
	protected $em;

    function __construct(EntityManager $em)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    /**
     * Create a complaint on entity, authored by user
     * @param User $user
     * @param $entity (User|Comment|Photo|Video)
     * @param string $content
     */
    public function complain(User $user, $entity, $content)
    {
    	$exp = explode('\\', get_class($entity));
    	$classname = end($exp);
    	
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