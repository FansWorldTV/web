<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\HasTag;
use Dodici\Fansworld\WebBundle\Entity\Tag;

/**
 * Updates tag usecounts when something is tagged
 */
class TagUpdater
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof HasTag) {
			$tag = $entity->getTag();
			
			if ($tag) {
				$tag->useUp();
				$em->persist($tag);
			}
			
			$em->flush();
		}
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
    	if ($entity instanceof HasTag) {
			$tag = $entity->getTag();
			
			if ($tag) {
				$tag->useDown();
				$em->persist($tag);
			}
			
			$em->flush();
		}
    }
}