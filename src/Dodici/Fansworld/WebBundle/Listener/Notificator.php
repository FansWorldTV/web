<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\HasUser;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Application\Sonata\UserBundle\Entity\User;

class Notificator
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof HasUser) {
			// notif: has sido etiquetado en ...
			// wall: ha sido etiquetado en ...
			
			$em->flush();
		}
		
    	if ($entity instanceof Comment) {
			$parent = $entity->getComment();
    		if ($parent) {
    			// notif: ha respondido tu comentario...
    		}
			
			$em->flush();
		}
    }
    
	public function preUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
    	if ($entity instanceof Friendship) {
            if ($eventArgs->hasChangedField('active') && $eventArgs->getNewValue('active') == true) {
                // notif: carlitos aceptó tu solicitud de amistad...
                // wall: juan es ahora amigo de carlitos
            }
            
            $em->flush();
        }
    }
}