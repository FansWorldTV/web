<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\ForumPost;
use Dodici\Fansworld\WebBundle\Entity\ForumThread;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\HasUser;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

class Notificator
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof HasUser && ($entity->getAuthor()->getId() != $entity->getTarget()->getId())) {
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
		
		if ($entity instanceof ForumThread) {
			$idol = $entity->getAuthor();
			// postear en su muro que tiene nuevo thread
			$comment = new Comment();
			$comment->setType(Comment::TYPE_NEW_THREAD);
			$comment->setAuthor($idol);
			$comment->setTarget($idol);
			$comment->setPrivacy(Privacy::EVERYONE);
			$share = new Share();
			$share->setForumThread($entity);
    		$comment->setShare($share);
			$em->persist($comment);
    		
			// notificar a usuarios que tienen al ídolo
			$fans = $em->getRepository('ApplicationSonataUserBundle:User')->byIdols($idol);
			
			foreach($fans as $fan) {
				$notification = new Notification();
    			$notification->setType(Notification::TYPE_FORUM_CREATED);
    			$notification->setAuthor($idol);
    			$notification->setTarget($fan);
    			$notification->setForumThread($entity);
    			$em->persist($notification);
			}
			$em->flush();
		}
		
		if ($entity instanceof ForumPost) {
			$thread = $entity->getForumThread();
			$thread->setPostCount($thread->getPostCount() + 1);
			$em->persist($thread);
			if ($entity->getAuthor()->getType() == User::TYPE_IDOL) {
				// si author es tipo idolo, postear en su muro que respondió
				$comment = new Comment();
				$comment->setType(Comment::TYPE_THREAD_ANSWERED);
				$comment->setAuthor($entity->getAuthor());
				$comment->setTarget($entity->getAuthor());
				$comment->setPrivacy(Privacy::EVERYONE);
				$share = new Share();
				$share->setForumThread($thread);
	    		$comment->setShare($share);
				$em->persist($comment);
				
				// y notificar a los que postearon en el thread
				$fans = $em->getRepository('ApplicationSonataUserBundle:User')->byThread($thread);
				
				foreach($fans as $fan) {
					$notification = new Notification();
	    			$notification->setType(Notification::TYPE_FORUM_ANSWERED);
	    			$notification->setAuthor($entity->getAuthor());
	    			$notification->setTarget($fan);
	    			$notification->setForumThread($thread);
	    			$em->persist($notification);
				}
				
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
				$em->persist($comment);
				// wall: inverso
				$comment = new Comment();
				$comment->setType(Comment::TYPE_NEW_FRIEND);
				$comment->setAuthor($entity->getAuthor());
				$comment->setTarget($entity->getTarget());
				$comment->setPrivacy(Privacy::FRIENDS_ONLY);
				$em->persist($comment);
				
				//$em->flush();
            }
        }
    }
}