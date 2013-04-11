<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * User serializer
 */
class User
{

    protected $router;
    protected $appstate;

    function __construct($router, $appstate)
    {
        $this->router = $router;
        $this->appstate = $appstate;
    }

    public function values($entity)
    {
        return array(
            'firstname' => $entity->getFirstname(),
            'lastname' => $entity->getLastname(),
            'fanCount' => $entity->getFanCount(),
            'sex' => $entity->getSex(),
            'username' => $entity->getUsername(),
            'url' => $this->router->generate('user_wall', array('username' => $entity->getUsername())),
            'location' => implode(', ', array(
                $entity->getCity(),
                $entity->getCountry()
            )),
            'canFriend' => $this->appstate->canFriend($entity)
        );
    }

}