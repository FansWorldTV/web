<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * User serializer
 */
class User
{
    public function values($entity)
    {
        return array(
            'firstname' => $entity->getFirstname(),
            'lastname' => $entity->getLastname(),
            'fanCount' => $entity->getFanCount(),
        	'sex' => $entity->getSex(),
        );
    }
}