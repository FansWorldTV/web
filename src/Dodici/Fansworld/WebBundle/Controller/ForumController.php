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

    const threadPerPage = 10;

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

        $threads = $this->getRepository('ForumThread')->findBy(array(), array('postCount' => 'desc'), self::threadPerPage);
        $countAll = $this->getRepository('ForumThread')->countBy(array());
        return array(
            'threads' => $threads,
            'addMore' => $countAll > self::threadPerPage ? true : false
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
//        for ($i = 0; $i <= 10; $i++) {
//            $user = $this->getRepository('User')->find($id);
//            $em = $this->getDoctrine()->getEntityManager();
//            $thread = new ForumThread();
//            $thread->setAuthor($user);
//            $thread->setTitle('asd' + $i);
//            $thread->setContent('asdsdaf asd fasd fasdfasd asd fasdfas da sdasdf asdfasd asdf asdfa sd fasd fasdf asd a');
//            $thread->setPostCount(10);
//            $em->persist($thread);
//            $em->flush();
//        }

        $user = $this->getRepository('User')->find($id);
        if (!$user instanceof User)
            throw new HttpException(404, 'Usuario no encontrado');
        if ($user->getType() != User::TYPE_IDOL)
            throw new HttpException(400, 'Usuario no es ídolo');

        $threads = $this->getRepository('ForumThread')->findBy(array('author' => $user->getId()), array('postCount' => 'desc'), self::threadPerPage);
        $countAll = $this->getRepository('ForumThread')->countBy(array('author' => $user->getId()));

        return array(
            'threads' => $threads,
            'user' => $user,
            'addMore' => $countAll > self::threadPerPage ? true : false
        );
    }

    /**
     * @Route("/ajax/search-threads", name="forum_ajaxsearchthreads")
     */
    public function ajaxSearchThreadsAction()
    {
        $request = $this->getRequest();
        $page = (int) $request->get('page', 1);
        $byUser = $request->get('userId', false);
        
        if($byUser !== 'false' && $byUser){
            $criteria = array('author'=>$byUser);
        }else{
            $criteria = array();
        }

        $offset = ($page - 1) * self::threadPerPage;
        $threadsRepo = $this->getRepository('ForumThread')->findBy($criteria, array('postCount' => 'desc'), self::threadPerPage, $offset);
        $countAll = $this->getRepository('ForumThread')->countBy($criteria);

        $threads = array();
        foreach ($threadsRepo as $thread) {
            $threads[] = array(
                'id' => $thread->getId(),
                'title' => $thread->getTitle(),
                'content' => $thread->getContent(),
                'author' => array(
                    'id' => $thread->getAuthor()->getId(),
                    'name' => (string) $thread->getAuthor()
                ),
                'createdAt' => $thread->getCreatedAt()->format('c'),
                'postCount' => $thread->getPostCount(),
                'slug' => $thread->getSlug()
            );
        }

        return $this->jsonResponse(array(
                    'addMore' => $countAll > $offset * $page ? true : false,
                    'threads' => $threads
                ));
    }

    /**
     * @Route("/ajax/thread-response", name="forum_ajaxthreadresponse")
     */
    public function ajaxThreadResponseAction()
    {
        $request = $this->getRequest();
        $threadId = $request->get('id', false);
        $content = $request->get('content', false);
        $user = $this->get('security.context')->getToken()->getUser();
        $response = array(
            'error' => false
        );

        if ($user instanceof User) {
            try {
                $thread = $this->getRepository('ForumThread')->find($threadId);
                $post = new ForumPost();
                $post->setAuthor($user);
                $post->setContent($content);
                $post->setActive(true);
                $post->setCreatedAt();
                $post->setForumThread($thread);
                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($post);
                $em->flush();
            } catch (Exception $exc) {
                $response['error'] = $exc->getMessage();
            }
        } else {
            $response['error'] = "User not logged";
        }

        return $this->jsonResponse($response);
    }

}

