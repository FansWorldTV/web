<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\ForumThread;
use Dodici\Fansworld\WebBundle\Entity\ForumPost;
use Symfony\Component\HttpFoundation\Request;

/**
 * Forum controller.
 * @Route("/forum")
 */
class ForumController extends SiteController
{
    /**
     * @Route("/", name="forum_index")
     */
    public function indexAction()
    {
        // TODO: list all (latest, most popular) threads
    	return new Response('ok');
    }

    /**
     * @Route("/thread/{id}/{slug}", name= "forum_thread", requirements = {"id" = "\d+"})
     */
    public function threadAction($id)
    {
        // TODO: show thread, list all posts
    	return new Response('ok');
    }

}
