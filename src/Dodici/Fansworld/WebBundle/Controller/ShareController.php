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
use Dodici\Fansworld\WebBundle\Entity\Share;
use Symfony\Component\HttpFoundation\Request;

/**
 * Share controller.
 */
class ShareController extends SiteController
{

    /**
     * 
     * @Route("/ajax/share", name="share_ajax")
     */
    public function ajaxShareAction()
    {
        try {
	    	$request = $this->getRequest();
	    	$id = intval($request->get('id'));
	    	$content = $request->get('text');
	        $type = $request->get('type');
	        $translator = $this->get('translator');
	        $appstate = $this->get('appstate');
	        
	        if (!in_array($type, array('newspost','photo','video','album','contest','comment')))
	        throw new \Exception('Invalid type');
	        
	        $repo = $this->getRepository($type);
	        $entity = $repo->find($id);
	        $message = null;
	        $user = $this->getUser();
	        $em = $this->getDoctrine()->getEntityManager();
	        
	        if ($appstate->canShare($entity)) {
	        	$this->get('sharer')->share($user, $entity, $content);
	        	
	        	$message = $translator->trans('You have shared') . ' ' . (string)$entity;
	        } else {
	        	if (!($user instanceof User)) {
	        		throw new \Exception('User not logged in');
	        	} else {
	        		throw new \Exception('Error liking entity');
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
