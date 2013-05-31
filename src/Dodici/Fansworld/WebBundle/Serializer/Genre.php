<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Genre serializer
 */
class Genre
{
    protected $serializer;

    function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    public function values($entity)
    {
        return array(
            'type' => $entity->getType(),
            'children' => $this->serializer->values($entity->getChildren())
        );
    }
}