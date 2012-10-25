<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Dodici\Fansworld\WebBundle\Entity\Eventship;

use Symfony\Component\Security\Core\SecurityContext;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Handles Event, create, removal and pushing to Meteor
 */
class EventShipManager
{    
    protected $security_context;
    protected $em;
    protected $user;
    protected $meteor;

    function __construct(SecurityContext $security_context, EntityManager $em, $meteor)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->meteor = $meteor;
        $this->user = null;
        $user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->user = $user;
        }
    }

    /**
     * Add a event to the list of events
     * @param Event $event
     * @param User $author
     */
    public function createEventShip(Event $event, User $author)
    {
        $this->meteor->addCreateEvent($event, $author);
    }

    /**
     * Remove an event to the list of events
     * @param $eventid
     * @param $authorid
     */
    public function removeEventShip($eventid, $authorid)
    {
        $this->meteor->removeEventShip($eventid, $authorid);
    }  
}