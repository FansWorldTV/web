<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Activity;

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
    
    protected $container;

    function __construct($container)
    {
        $this->container = $container;
    }
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof HasUser && ($entity->getAuthor()->getId() != $entity->getTarget()->getId())) {
			// notif: has sido etiquetado en ...
			$notification = new Notification();
			
			if ($entity->getPhoto()) {
    		    $notification->setType(Notification::TYPE_USER_TAGGED_PHOTO);
			} elseif ($entity->getVideo()) {
			    $notification->setType(Notification::TYPE_USER_TAGGED_VIDEO);
			} else {
			    return false;
			}
    		
    		$notification->setAuthor($entity->getAuthor());
    		$notification->setTarget($entity->getTarget());
    		if ($entity->getComment()) $notification->setComment($entity->getComment());
    		if ($entity->getAlbum()) $notification->setAlbum($entity->getAlbum());
    		if ($entity->getPhoto()) $notification->setPhoto($entity->getPhoto());
    		if ($entity->getVideo()) $notification->setVideo($entity->getVideo());
    		if ($entity->getContest()) $notification->setContest($entity->getContest());
    		if ($entity->getNewspost()) $notification->setNewspost($entity->getNewspost());
    		$em->persist($notification);
			
    		// activity: ha sido etiquetado en ...
			if ($entity->getPhoto() || $entity->getVideo())
    		    $this->container->get('user.feed.logger')->log(
    		        Activity::TYPE_LABELLED_IN, 
    		        array($entity->getPhoto(), $entity->getVideo()), 
    		        $entity->getTarget(), 
    		        false
    		    );
						
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
			// activity: new photo
			$this->container->get('user.feed.logger')->log(Activity::TYPE_NEW_PHOTO, $entity, $entity->getAuthor());
		}
		
    	if ($entity instanceof Video && $entity->getAuthor() && !$entity->getHighlight()) {
			$this->container->get('user.feed.logger')->log(Activity::TYPE_NEW_VIDEO, $entity, $entity->getAuthor());
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
        
        $related = array_merge(array($video), $teams, $idols);
        
        $this->container->get('user.feed.logger')->log(Activity::TYPE_NEW_VIDEO, $related, false, false);
        
        $users = array();
        
        $usersteams = array(); $usersidols = array();
        
        if ($teams) {
            $usersteams = $em->getRepository('ApplicationSonataUserBundle:User')->byTeams($teams);
        }
        if ($idols) {
            $usersidols = $em->getRepository('ApplicationSonataUserBundle:User')->byIdols($idols);
        }
        
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