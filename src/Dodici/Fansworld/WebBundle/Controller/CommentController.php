<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\Request;

/**
 * Comment controller.
 * @Route("/comment")
 */
class CommentController extends SiteController
{
    
    /**
     * @Route("/show/{id}", name= "comment_show", requirements = {"id" = "\d+"})
     */
    public function showAction($id)
    {
        // TODO: comment show action, list all responses (nested comments), allow answering root comment
    	return new Response('ok');
    }

}
