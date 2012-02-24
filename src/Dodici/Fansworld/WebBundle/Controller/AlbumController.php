<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Album;
use Symfony\Component\HttpFoundation\Request;

/**
 * Album controller.
 * @Route("/album")
 */
class AlbumController extends SiteController
{
    
    /**
     * @Route("/{id}/{slug}", name= "album_show", requirements = {"id" = "\d+"})
     */
    public function showAction($id)
    {
        // TODO: album show action, list album photos + comments (masonry)
    	return new Response('ok');
    }

}
