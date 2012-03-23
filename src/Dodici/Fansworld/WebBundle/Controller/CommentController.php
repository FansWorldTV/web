<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * @Template
     */
    public function showAction($id)
    {
        // TODO: comment show action, list all responses (nested comments), allow answering root comment
        $comment = $this->getRepository('Comment')->find($id);
        $this->securityCheck($comment);
        if ($comment->getComment()) throw new HttpException(400, 'Comentario es subcomentario');
    	
    	return array(
    		'comment' => $comment
    	);
    }
    
	/**
     * 
     * @Route("/ajax/comment/post", name="comment_ajaxpost")
     */
    public function ajaxPostAction()
    {
        try {
	    	$request = $this->getRequest();
	    	$id = intval($request->get('id'));
	        $type = $request->get('type');
	        $privacy = $request->get('privacy', null);
	        $content = $request->get('content', null);
	        $ispin = $request->get('ispin') == 'true';
	        $translator = $this->get('translator');
	        $appstate = $this->get('appstate');
	        
	        if (!in_array($type, array('newspost','photo','video','album','contest','comment','user')))
	        throw new \Exception('Invalid type');
	        
	        if (!$content) throw new \Exception('You must enter a message');
	        
	        $repo = $this->getRepository($type);
	        $entity = $repo->find($id);
	        
	        if (!$entity) throw new \Exception('Entity does not exist');
	        if (!$appstate->canComment($entity)) throw new \Exception('Unauthorized');
	        
	        $message = null;
	        $user = $this->get('security.context')->getToken()->getUser();
	        $em = $this->getDoctrine()->getEntityManager();
	        
	        $comment = $this->get('commenter')->comment($user, $entity, $content, $privacy);
	        
	        if ($entity instanceof Comment) {
	        	$message = $translator->trans('You have replied to a comment');
	        } else {
        		$message = $translator->trans('You have commented on') . ' ' . (string)$entity;
	        }
	
	        $templatename = ($ispin ? 'pin_comment.html.twig' : 'comment.html.twig');
	        
	        $response = new Response(json_encode(array(
	        	'commenthtml' => $this->renderView('DodiciFansworldWebBundle:Comment:'.$templatename, array('comment' => $comment)),
	        	'message' => $message
	        )));
	        $response->headers->set('Content-Type', 'application/json');
	        return $response;
        } catch (\Exception $e) {
        	return new Response($e->getMessage(), 400);
        }
    }

}
