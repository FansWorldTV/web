<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\EventTweet;
use Dodici\Fansworld\WebBundle\Entity\EventIncident;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Symfony\Component\HttpFoundation\Request;
use Dodici\Fansworld\WebBundle\Entity\Notification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Pushes newly created relevant entities into the Meteor service
 */
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

        /* NOTIFICATIONS / FRIENDSHIPS */
        if ($entity instanceof Notification && !$entity->getReaded()) {
            $target = $entity->getTarget();
            $allowed = $target->getNotifyprefs();
            
            $type = $entity->getType();
            
            if (in_array($type, $allowed)) {
                // send comet push
                $this->container->get('meteor')->push($entity);
            } else {
                if ($entity instanceof Notification) {
                    if ($entity->getType() != Notification::TYPE_FRIENDSHIP_PENDING) {
                        $entity->setReaded(true);
                    }
                    $em->persist($entity);
                    $em->flush();
                }
            }
        }
        
        /* COMMENTS, EVENT INCIDENTS, EVENT TWEETS */
        if ($entity instanceof Comment ||
            $entity instanceof EventIncident ||
            $entity instanceof EventTweet) 
        {
            $this->container->get('meteor')->push($entity);
        }
        
    }

}