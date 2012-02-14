<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Notification;
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
			$notification = new Notification();
    		$notification->setType(Notification::TYPE_USER_TAGGED);
    		$notification->setAuthor($entity->getAuthor());
    		$notification->setTarget($entity->getTarget());
    		if ($entity->getComment()) $notification->setComment($entity->getComment());
    		if ($entity->getAlbum()) $notification->setAlbum($entity->getAlbum());
    		if ($entity->getPhoto()) $notification->setPhoto($entity->getPhoto());
    		if ($entity->getVideo()) $notification->setVideo($entity->getVideo());
    		if ($entity->getContest()) $notification->setContest($entity->getContest());
    		if ($entity->getNewspost()) $notification->setNewspost($entity->getNewspost());
    		$em->persist($notification);
			// wall: ha sido etiquetado en ...
			$comment = new Comment();
			$comment->setType(Comment::TYPE_LABELLED);
			$comment->setAuthor($entity->getTarget());
			$comment->setTarget($entity->getTarget());
			$comment->setPrivacy(Privacy::FRIENDS_ONLY);
			
			$share = new Share();
    		if ($entity->getComment()) $share->setComment($entity->getComment());
    		if ($entity->getAlbum()) $share->setAlbum($entity->getAlbum());
    		if ($entity->getPhoto()) $share->setPhoto($entity->getPhoto());
    		if ($entity->getVideo()) $share->setVideo($entity->getVideo());
    		if ($entity->getContest()) $share->setContest($entity->getContest());
    		if ($entity->getNewspost()) $share->setNewspost($entity->getNewspost());
    		
    		$comment->setShare($share);
			
    		$em->persist($comment);
			
			$em->flush();
		}
		
    	if ($entity instanceof Comment) {
			$parent = $entity->getComment();
    		if ($parent) {
    			// notif: ha respondido tu comentario...
    			$notification = new Notification();
    			$notification->setType(Notification::TYPE_COMMENT_ANSWERED);
    			$notification->setAuthor($entity->getAuthor());
    			$notification->setTarget($parent->getAuthor());
    			$notification->setComment($entity);
    			$em->persist($notification);
    			
    			$em->flush();
    		}
		}
    }
    
	public function preUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
    	if ($entity instanceof Friendship) {
            if ($eventArgs->hasChangedField('active') && $eventArgs->getNewValue('active') == true) {
                // notif: carlitos aceptó tu solicitud de amistad...
                $notification = new Notification();
	    		$notification->setType(Notification::TYPE_FRIENDSHIP_ACCEPTED);
	    		$notification->setAuthor($entity->getTarget());
	    		$notification->setTarget($entity->getAuthor());
	    		$em->persist($notification);
                // wall: juan es ahora amigo de carlitos
                $comment = new Comment();
				$comment->setType(Comment::TYPE_NEW_FRIEND);
				$comment->setAuthor($entity->getTarget());
				$comment->setTarget($entity->getAuthor());
				$comment->setPrivacy(Privacy::FRIENDS_ONLY);
				
				$em->flush();
            }
        }
    }
}