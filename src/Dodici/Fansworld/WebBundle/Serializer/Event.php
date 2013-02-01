<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Event serializer
 */
class Event
{
    protected $serializer;
    protected $router;
    protected $em;
    protected $appstate;
    protected $securityContext;


    function __construct($serializer, $router, $em, $appstate, $securityContext)
    {
        $this->serializer = $serializer;
        $this->router = $router;
        $this->em = $em;
        $this->appstate = $appstate;
        $this->securityContext = $securityContext;
    }
    
    public function values($entity)
    {
        $checked = $this->em->getRepository('DodiciFansworldWebBundle:Eventship')->findOneBy(array('author' => $this->securityContext->getToken()->getUser(), 'event' => $entity->getId())) ? true : false;
        $now = new \DateTime();
        $started = ($entity->getFromtime() <= $now);
        $collection = array(
            'text' => $this->appstate->getEventText($entity->getId()),
            'date' => $entity->getFromtime()->format('d-m-Y'),
            'showdate' => $entity->getFromtime()->format('d/m/Y H:i'),
            'stadium' => $entity->getStadium(),
            'finished' => $entity->getFinished(),
            'url' => $this->router->generate('event_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug())),
            'started' => $started,
            'checked' => $checked
        );
        
        foreach($entity->getHasTeams() as $ht){
            $collection['teams'][] = $this->serializer->values($ht->getTeam());
        }
        
        return $collection;
    }
}