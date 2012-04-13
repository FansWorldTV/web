<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

class MeteorPusher
{
    protected $container;
    protected $request;
    
	function __construct($container)
    {
        $this->container = $container;
        $this->request = Request::createFromGlobals();
    }
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
    	if ($entity instanceof Notification || $entity instanceof Friendship) {
			// send comet push
			$this->container->get('meteor')->push($entity);
		}
    }
    
}