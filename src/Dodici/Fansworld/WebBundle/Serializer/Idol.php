<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Idol serializer
 */
class Idol
{
    public function values($entity)
    {
        return array(
            'firstname' => $entity->getFirstname(),
            'lastname' => $entity->getLastname(),
            'fanCount' => $entity->getFanCount()
        );
    }
}