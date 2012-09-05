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
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Creates notifications when something notification-worthy happens
 */
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
    		if ($parent && ($entity->getAuthor() != $parent->getAuthor())) {
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
		
		if ($entity instanceof Photo && $entity->getAuthor()) {
			$comment = new Comment();
			$comment->setType(Comment::TYPE_NEW_PHOTO);
			$comment->setAuthor($entity->getAuthor());
			$comment->setTarget($entity->getAuthor());
			$comment->setPrivacy($entity->getPrivacy());
			
			$share = new Share();
			$share->setPhoto($entity);
    		$comment->setShare($share);
			$em->persist($comment);
			$em->flush();
		}
		
    	if ($entity instanceof Video && $entity->getAuthor() && !$entity->getHighlight()) {
			$comment = new Comment();
			$comment->setType(Comment::TYPE_NEW_VIDEO);
			$comment->setAuthor($entity->getAuthor());
			$comment->setTarget($entity->getAuthor());
			$comment->setPrivacy($entity->getPrivacy());
			
			$share = new Share();
			$share->setVideo($entity);
    		$comment->setShare($share);
			$em->persist($comment);
			$em->flush();
		} elseif ($entity instanceof Video && $entity->getHighlight() && $entity->getProcessed() && $entity->getActive() && !$entity->getNotified()) {
		    $this->notifyNewIdolTeamVideo($entity, $em);
		    $entity->setNotified(true);
			$em->persist($entity);
			$em->flush();
		}
		
    }
    
	public function postUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
        if ($entity instanceof Video && $entity->getHighlight() && $entity->getProcessed() && $entity->getActive() && !$entity->getNotified()) {
		    $this->notifyNewIdolTeamVideo($entity, $em);
            
			$entity->setNotified(true);
			$em->persist($entity);
			
			$em->flush();
		}
    }
    
    private function notifyNewIdolTeamVideo($video, $em)
    {
        $teams = array();
        $idols = array();
        
        $hasteams = $video->getHasteams();
        $hasidols = $video->getHasidols();
        
        foreach ($hasteams as $ht) $teams[] = $ht->getTeam();
        foreach ($hasidols as $ht) $idols[] = $ht->getIdol();
        $users = array();
        
        $usersteams = $em->getRepository('ApplicationSonataUserBundle:User')->byTeams($teams);
        $usersidols = $em->getRepository('ApplicationSonataUserBundle:User')->byIdols($idols);
        
        foreach ($usersteams as $u) $users[$u->getId()] = $u;
        foreach ($usersidols as $u) $users[$u->getId()] = $u;
        
        foreach ($users as $user) {
            // notif: ha respondido tu comentario...
			$notification = new Notification();
			$notification->setType(Notification::TYPE_VIDEO_NEW_FROM_IDOL_TEAM);
			$notification->setAuthor($user);
			$notification->setTarget($user);
			$notification->setVideo($video);
			
			$em->persist($notification);
        }
    }
}