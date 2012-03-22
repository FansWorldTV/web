<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delete controller.
 */
class DeleteController extends SiteController
{

    /**
     * 
     * @Route("/ajax/delete", name="delete_ajax")
     */
    public function ajaxDeleteAction()
    {
        try {
	    	$request = $this->getRequest();
	    	$id = intval($request->get('id'));
	        $type = $request->get('type');
	        $translator = $this->get('translator');
	        $appstate = $this->get('appstate');
	        
	        if (!in_array($type, array('photo','video','album','forumpost','comment')))
	        throw new \Exception('Invalid type');
	        
	        $repo = $this->getRepository($type);
	        $entity = $repo->find($id);
	        $user = $this->get('security.context')->getToken()->getUser();
	        $em = $this->getDoctrine()->getEntityManager();
	        
	        if (!$entity->getActive()) throw new \Exception('Entity already deleted'); 
	        
	        if ($appstate->canDelete($entity)) {
	        	$entity->setActive(false);
	        	$em->persist($entity);
	        	$em->flush();
	        	
	        	$message = $translator->trans('You have deleted') . ' ' . (((string)$entity) ? ('"' . (string)$entity.'"') : $translator->trans('the item'));
	        } else {
	        	if (!($user instanceof User)) {
	        		throw new \Exception('User not logged in');
	        	} else {
	        		throw new \Exception('Error deleting entity');
	        	}
	        }
	
	        $response = new Response(json_encode(array(
	        	'message' => $message
	        )));
	        $response->headers->set('Content-Type', 'application/json');
	        return $response;
        } catch (\Exception $e) {
        	return new Response($e->getMessage(), 400);
        }
    }

}