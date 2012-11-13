<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * Team serializer
 */
class Team
{
    public function values($entity)
    {
        return array(
            'id' => $entity->getId(),
            'fanCount' => $entity->getFanCount()
        );
    }
}