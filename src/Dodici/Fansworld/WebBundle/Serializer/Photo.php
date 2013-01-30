<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Photo serializer
 */
class Photo
{
    protected $serializer;
    
    function __construct($serializer)
    {
        $this->serializer = $serializer;
    }
    
    public function values($entity)
    {
        return array(
            'author' => $this->serializer->values($entity->getAuthor(), 'small_square'),
            'likeCount' => $entity->getLikeCount(),
            'visitCount' => $entity->getVisitCount(),
            'commentCount' => $entity->getCommentCount(),
            'weight' => $entity->getWeight()
        );
    }
}