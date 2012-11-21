<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Event serializer
 */
class Event
{
    protected $serializer;
    
    function __construct($serializer)
    {
        $this->serializer = $serializer;
    }
    
    public function values($entity)
    {
        $collection = array(
            'stadium' => $entity->getStadium(),
            'finished' => $entity->getFinished()
        );
        
        foreach($entity->getHasTeams() as $ht){
            $collection['teams'][] = $this->serializer->values($ht->getTeam());
        }
        
        return $collection;
    }
}