<?php

namespace Dodici\Fansworld\WebBundle\Serializer;

/**
 * User serializer
 */
class User
{

    protected $router;
    protected $appstate;
    protected $appmedia;

    function __construct($router, $appstate, $appmedia)
    {
        $this->router = $router;
        $this->appstate = $appstate;
        $this->appmedia = $appmedia;
    }

    public function values($entity, $imageformat='small', $splashformat='big', $mode='url')
    {
        $location = array();
        if ($entity->getCity()) $location[] = $entity->getCity();
        if ($entity->getCountry()) $location[] = $entity->getCountry();

        return array(
            'firstname' => $entity->getFirstname(),
            'lastname' => $entity->getLastname(),
            'fanCount' => $entity->getFanCount(),
            'splash' => $entity->getSplash() ? $this->appmedia->getImageUrl($entity->getSplash(), $splashformat, $mode) : null,
            'sex' => $entity->getSex(),
            'username' => $entity->getUsername(),
            'url' => $this->router->generate('user_wall', array('username' => $entity->getUsername())),
            'location' => $location ? implode(', ', $location) : null,
            'canFriend' => $this->appstate->canFriend($entity)
        );
    }

}