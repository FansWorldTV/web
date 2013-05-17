<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Team serializer
 */
class Team
{
    protected $appmedia;

    function __construct($appmedia)
    {
        $this->appmedia = $appmedia;
    }

    public function values($entity, $imageformat='small')
    {
        return array(
            'id' => $entity->getId(),
            'fanCount' => $entity->getFanCount(),
            'videoCount' => $entity->getVideoCount(),
            'image' => $this->appmedia->getImageUrl($entity->getImage(), $imageformat)
        );
    }
}