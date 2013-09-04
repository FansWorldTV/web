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
use Dodici\Fansworld\WebBundle\Entity\Liking;
use Symfony\Component\HttpFoundation\Request;

/**
 * Like controller.
 */
class LikeController extends SiteController
{

    /**
     * 
     * @Route("/ajax/like/toggle", name="like_ajaxtoggle")
     */
    public function ajaxToggleAction()
    {
        try {
	    	$request = $this->getRequest();
	    	$id = intval($request->get('id'));
	        $type = $request->get('type');
	        $translator = $this->get('translator');

	        if (!in_array($type, array('newspost','photo','video','album','contest','comment','proposal')))
	        throw new \Exception('Invalid type');
	        
	        $repo = $this->getRepository($type);
	        $entity = $repo->find($id);
	        //$likecount = $entity->getLikeCount();
	        $buttontext = null;
	        $message = null;
	        $liker = $this->get('liker');
	        
	        if ($liker->isLiking($entity)) {
	        	$liker->unlike($entity);
	        	$message = $translator->trans('You no longer like') . ' "' . (string)$entity.'"';
	        	$buttontext = $translator->trans('like');
	        	//$likecount--;
	        	$liked = false;
	        } else {
	        	$liker->like($entity);
	        	$message = $translator->trans('You now like') . ' "' . (string)$entity.'"';
	        	$buttontext = $translator->trans('unlike');
	        	//$likecount++;
	        	$liked = true;
	        }
	
	        $response = new Response(json_encode(array(
	        	'buttontext'	=> $buttontext,
	        	//'likecount' 	=> $likecount,
	        	'message' 		=> $message,
	        	'liked'			=> $liked,
	        )));
	        $response->headers->set('Content-Type', 'application/json');
	        return $response;
        } catch (\Exception $e) {
        	return new Response($e->getMessage(), 400);
        }
    }

}
