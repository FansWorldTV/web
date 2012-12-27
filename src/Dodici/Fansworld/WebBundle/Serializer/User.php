<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * User serializer
 */
class User
{

    protected $router;

    function __construct($router)
    {
        $this->router = $router;
    }

    public function values($entity)
    {
        return array(
            'firstname' => $entity->getFirstname(),
            'lastname' => $entity->getLastname(),
            'fanCount' => $entity->getFanCount(),
            'sex' => $entity->getSex(),
            'username' => $entity->getUsername(),
            'url' => $this->router->generate('user_wall', array('username' => $entity->getUsername()))
        );
    }

}