<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Video serializer
 */
class Video
{
    protected $serializer;
    
    function __construct($serializer)
    {
        $this->serializer = $serializer;
    }
    
    public function values($entity)
    {
        return array(
            'author' => $this->serializer->values($entity->getAuthor()),
            'likeCount' => $entity->getLikeCount(),
            'visitCount' => $entity->getVisitCount(),
            'commentCount' => $entity->getCommentCount(),
            'videocategory' => $entity->getVideocategory() ? (int)$entity->getVideocategory()->getId() : null,
            'weight' => $entity->getWeight(),
            'duration' => $entity->getDuration()
        );
    }
}