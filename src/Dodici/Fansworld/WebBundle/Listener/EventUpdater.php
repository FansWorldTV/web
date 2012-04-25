<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Eventship;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class EventUpdater
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Eventship) {
			$event = $entity->getEvent();
			$event->setUserCount($event->getUserCount() + 1);
			
			$em->persist($event);
			$em->flush();
		}
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
    	$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Eventship) {
			$event = $entity->getEvent();
			$event->setUserCount($event->getUserCount() - 1);
			
			$em->persist($event);
			$em->flush();
		}
    }
    
}