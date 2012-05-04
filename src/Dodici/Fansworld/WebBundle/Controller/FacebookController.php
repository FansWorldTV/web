<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Application\Sonata\UserBundle\Entity\User;

use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dodici\Fansworld\WebBundle\Controller\SiteController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Facebook controller.
 * @Route("/facebook")
 */
class FacebookController extends SiteController
{
	/**
     * Set access token by client js from uri hash
     * @Route("/jstoken", name="facebook_jstoken")
     * @Template
     */
    public function jsTokenAction()
    {
    	return array();
    }
    
	/**
     * Set access token
     * @Route("/settoken", name="facebook_settoken")
     */
    public function setTokenAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
    	$signed_request = $this->getRequest()->get('signed_request');
    	if ($signed_request && ($user instanceof User)) {
	    	$facebook = $this->get('fos_facebook.api');
	    	
	    	$fbuid = $facebook->getUser();
			$fbtoken = $facebook->getAccessToken();
			
			if ($fbuid && $fbtoken) {
				if ($fbuid != $user->getFacebookId()) {
					$user->setFacebookId($fbuid);
					$em = $this->getDoctrine()->getEntityManager();
					$em->persist($user);
					$em->flush();
				}
				
		    	return $this->jsonResponse(array(
		    		'uid' => $fbuid
		    	));
			} else {
				throw new HttpException(400);
			}
    	} else {
    		throw new HttpException(400);
    	}
    }
}
