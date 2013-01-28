<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Activity;

use Dodici\Fansworld\WebBundle\Entity\Team;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Dodici\Fansworld\WebBundle\Entity\Event;
use Dodici\Fansworld\WebBundle\Entity\Eventship;
use Symfony\Component\Security\Core\SecurityContext;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;

/**
 * Handles Event, create, removal and pushing to Meteor
 */
class EventshipManager
{    
    protected $security_context;
    protected $em;
    protected $user;
    protected $meteor;
    protected $userfeedlogger;

    function __construct(SecurityContext $security_context, EntityManager $em, $meteor, $userfeedlogger)
    {
        $this->security_context = $security_context;
        $this->em = $em;
        $this->meteor = $meteor;
        $this->userfeedlogger = $userfeedlogger;
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
     * @param Int $eventshipType
     */
    public function addEventship(Event $event,  User $author, Team $team, $eventshipType)
    {
        $eventship = new Eventship();
        $eventship->setAuthor($author);
        $eventship->setEvent($event);
        $eventship->setTeam($team);
        $eventship->setType($eventshipType);

        $this->em->persist($eventship);
        
        $this->userfeedlogger->log(Activity::TYPE_CHECKED_IN, $event, $author, false);
        
        $this->em->flush();

        $this->meteor->addEventship($eventship);
    }

    /**
     * Remove an event to the list of events
     * @param Eventship $eventShip
     */
    public function removeEventship(Eventship $eventship)
    {
        $this->em->remove($eventship);
        $this->em->flush();
        
        $this->meteor->removeEventship($eventship);
    }  
}