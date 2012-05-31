<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use JMS\SecurityExtraBundle\Annotation\Secure;
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
 * Twitter controller.
 * @Route("/twitter")
 */
class TwitterController extends SiteController
{
	/**
     * Set access token
     * @Route("/tokenize", name="twitter_tokenize")
     * @Secure(roles="ROLE_USER")
     * @Template
     */
    public function tokenizeAction()
    {
    	$request = $this->getRequest();
		$t = $this->get('fos_twitter.service');
    	$accesstoken = $t->getAccessToken($request);
    		
    	if ($accesstoken) {
    		$user = $this->get('security.context')->getToken()->getUser();
    		if ($user instanceof User) {
    			$user->setTwitter($accesstoken['screen_name']);
    			$user->setTwitterid($accesstoken['user_id']);
    			$user->setTwittertoken($accesstoken['oauth_token']);
    			$user->setTwittersecret($accesstoken['oauth_token_secret']);
    			$em = $this->getDoctrine()->getEntityManager();
    			$em->persist($user);
    			$em->flush($em);
    		}
    	}
    	
    	return array(
    		'token' => $accesstoken
    	);
    }
    
	/**
     * Redirect to Twitter with callback
     * @Route("/redirect", name="twitter_redirect")
     * @Secure(roles="ROLE_USER")
     */
    public function redirectAction()
    {
    	$request = $this->getRequest();
		$t = $this->get('fos_twitter.service');
    	$t->setCallbackRoute($this->get('router'), 'twitter_tokenize');
    	$url = $t->getLoginUrl($request);
    	
		return $this->redirect($url);
    }
    
}
