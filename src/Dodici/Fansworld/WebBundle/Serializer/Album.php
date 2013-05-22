<?php

namespace Dodici\Fansworld\WebBundle\Serializer;
 
/**
 * Album serializer
 */
class Album
{
    protected $router;
    
    function __construct($router)
    {
        $this->router = $router;
    }
    
    public function values($entity, $imageFormat = 'medium')
    {
        return array(
            'url'=> $this->router->generate('user_showalbum', array('id' => $entity->getId(), 'username' => $entity->getAuthor()->getUsername())),
            'photoCount' => $entity->getPhotoCount()
        );
    }
}