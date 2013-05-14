<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Video serializer
 */
class Video
{
    protected $serializer;
    protected $router;
    
    function __construct($serializer, $router)
    {
        $this->serializer = $serializer;
        $this->router = $router;
    }
    
    public function values($entity)
    {
        return array(
            'author' => $this->serializer->values($entity->getAuthor(), 'small_square'),
            'likeCount' => $entity->getLikeCount(),
            'visitCount' => $entity->getVisitCount(),
            'commentCount' => $entity->getCommentCount(),
            'videocategory' => $entity->getVideocategory() ? (int)$entity->getVideocategory()->getId() : null,
            'weight' => $entity->getWeight(),
            'duration' => date("i:s", $entity->getDuration()),
            'url' => $this->router->generate('video_show', array('id' => $entity->getId(), 'slug' => $entity->getSlug())),
            'modalUrl' => $this->router->generate('modal_media', array('type' => 'video', 'id' => $entity->getId()))
        );
    }
}