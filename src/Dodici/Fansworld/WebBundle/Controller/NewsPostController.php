<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\NewsPost;
use Symfony\Component\HttpFoundation\Request;

/**
 * NewsPost controller.
 * @Route("/news")
 */
class NewsPostController extends SiteController
{
    
    /**
     * @Route("/show/{id}/{slug}", name= "newspost_show", requirements = {"id" = "\d+"})
     */
    public function showAction($id)
    {
        // TODO: newspost show action, show newspost + comments, allow commenting + answering root comments
    	return new Response('ok');
    }

}
