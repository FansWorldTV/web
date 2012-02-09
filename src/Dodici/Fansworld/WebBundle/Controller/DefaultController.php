<?php

namespace Dodici\Fansworld\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("/hello/{name}")
     * @Template()
     */
    public function indexAction($name)
    {
        return array('name' => $name);
    }
    
	/**
     * Sidebar controller action
     * @Template
     */
    public function sidebarAction()
    {
    	return array();
    }
    
	/**
     * Leftbar controller action
     * @Template
     */
    public function leftbarAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
    	return array('user' => $user);
    }
    
	/**
     * Rightbar controller action
     * @Template
     */
    public function rightbarAction()
    {
    	$user = $this->get('security.context')->getToken()->getUser();
    	return array('user' => $user);
    }
    
	/**
     * Top controller action
     * @Template
     */
    public function topAction()
    {
    	return array();
    }
    
	/**
     * force mobile
     * 
     * @Route("/mobile/{value}", requirements={"value"="yes|no"}, name="force_mobile")
     */
    public function forcemobileAction($value)
    {
        $request = $this->getRequest();
        $host = $request->getHost();
        if (strpos($host, 'm.') === 0)
        	$host = substr($host, 2);
        	
    	if ($value == 'yes') {
    		$url = 'http://m.'.$host.$this->generateUrl('homepage');
    	} else { 
    		$url = 'http://'.$host.$this->generateUrl('homepage');
    	}
    	
    	$response = new RedirectResponse($url);
        $cookie = new Cookie('force'.$value.'mobile', '1', time() + (3600 * 48), '/', $host);
		$response->headers->setCookie($cookie);
		if ($value == 'yes') {
			$cookie = new Cookie('forcenomobile', '0', time() + (3600 * 48), '/', $host);
			$response->headers->setCookie($cookie);
		} else {
			$cookie = new Cookie('forceyesmobile', '0', time() + (3600 * 48), '/', $host);
			$response->headers->setCookie($cookie);
		}
		return $response;
    }
}
