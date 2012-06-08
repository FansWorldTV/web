<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\OpinionVote;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class OpinionCountUpdater
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof OpinionVote) {
			$opinion = $entity->getOpinion();
			
			if ($entity->getValue()) {
			    $opinion->setYesCount($opinion->getYesCount() + 1);
			} else {
			    $opinion->setNoCount($opinion->getNoCount() + 1);
			}
			
			$em->persist($opinion);			
			$em->flush();
		}
    }
    
}