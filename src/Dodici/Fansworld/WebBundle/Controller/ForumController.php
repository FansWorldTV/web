<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Application\Sonata\UserBundle\Entity\User;
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
     * 
     * @Template
     */
    public function indexAction()
    {
//        $user = $this->get('security.context')->getToken()->getUser();
//        $em = $this->getDoctrine()->getEntityManager();
//        $thread = new ForumThread();
//        $thread->setAuthor($user);
//        $thread->setTitle('asd');
//        $thread->setContent('asd');
//        $thread->setPostCount(10);
//        $em->persist($thread);
//        $em->flush();
        
        $threads = $this->getRepository('ForumThread')->findBy(array(), array('postCount' => 'desc'));
        return array(
            'threads' => $threads
        );
    }

    /**
     * @Route("/thread/{id}/{slug}", name= "forum_thread", requirements = {"id" = "\d+"}, defaults = {"slug" = null})
     * @Template
     */
    public function threadAction($id)
    {
        $thread = $this->getRepository('ForumThread')->find($id);
        return array(
            'thread' => $thread
        );
    }

    /**
     * @Route("/user/{id}", name= "forum_user", requirements = {"id" = "\d+"})
     * @Template
     */
    public function userThreadsAction($id)
    {
        // TODO: show user threads (idol only)
        $user = $this->getRepository('User')->find($id);
        if (!$user instanceof User)
            throw new HttpException(404, 'Usuario no encontrado');
        if ($user->getType() != User::TYPE_IDOL)
            throw new HttpException(400, 'Usuario no es ídolo');

        $threads = $this->getRepository('ForumThread')->findBy(array('author' => $user->getId()), array('createdAt' => 'desc'));
        
        return array(
            'threads' => $threads,
            'user' => $user
        );
    }

}
 