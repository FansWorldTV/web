<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;

/**
 * Video controller.
 * @Route("/video")
 */
class VideoController extends SiteController
{
    
    /**
     * @Route("/show/{id}/{slug}", name= "video_show", requirements = {"id" = "\d+"})
     */
    public function showAction($id)
    {
        // TODO: video show action, show video + comments, allow commenting + answering root comments
    	return new Response('ok');
    }

}
