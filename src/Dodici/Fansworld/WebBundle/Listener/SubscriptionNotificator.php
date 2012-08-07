<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Notification;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Notifies users with a subscription to a VideoCategory that a new (highlight) video is available
 */
class SubscriptionNotificator
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
        
        if ($entity instanceof Video && $entity->getActive() && $entity->getHighlight() && $entity->getVideoCategory()) {
            $entity->setProcessed(true);
            $em->persist($entity);
            
            $this->notify($entity, $em);
        }
    }
    
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $em = $eventArgs->getEntityManager();
        
        if ($entity instanceof Video && $entity->getActive() && !$entity->getProcessed() && $entity->getHighlight() && $entity->getVideoCategory()) {
            $entity->setProcessed(true);
            $em->persist($entity);
            
            $this->notify($entity, $em);
        }
    }
    
    private function notify($video, $em)
    {
        $users = $this->container->get('subscriptions')->usersSubscribed($video->getVideoCategory());
        
        foreach ($users as $user) {
            $notification = new Notification();
    		$notification->setType(Notification::TYPE_VIDEO_SUBSCRIPTION);
    		$notification->setAuthor($user);
    		$notification->setTarget($user);
    		$notification->setVideo($video);
    		$em->persist($notification);
        }
        
        $em->flush();
    }

}