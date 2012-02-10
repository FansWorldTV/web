<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class AppState
{

    protected $session;
    protected $request;
    protected $em;

    function __construct(Session $session, EntityManager $em)
    {
        $this->session = $session;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    public function getMobile()
    {
        return (strpos($this->request->getHost(), 'm.') === 0);
    }

    public function getCulture($locale)
    {
    	switch ($locale) {
    		case 'es':
    			return 'es_LA'; break;
    		case 'en':
    		default:
    			return 'en_US'; break;
    	}
    }
    
    public function canLike(\Application\Sonata\UserBundle\Entity\User $user, $entity) 
    {
    	$rep = $this->em->getRepository('DodiciFansworldWebBundle:Liking');
    	$liking = $rep->byUserAndEntity($user, $entity);
    	
    	if (count($liking) >= 1) return false;
    	
    	if (method_exists($entity, 'getPrivacy')) {
    		if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
    			if (method_exists($entity, 'getAuthor')) {
	    			$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    			if (!$frep->UsersAreFriends($user, $entity->getAuthor())) return false;
    			}
    		}
    	}
    	
    	return true;
    }
    
	public function canDislike(\Application\Sonata\UserBundle\Entity\User $user, $entity) 
    {
    	$rep = $this->em->getRepository('DodiciFansworldWebBundle:Liking');
    	$liking = $rep->byUserAndEntity($user, $entity);
    	
    	if (count($liking) >= 1) return true;
    	else return false;
    }
}