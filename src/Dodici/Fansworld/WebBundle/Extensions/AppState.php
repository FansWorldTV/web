<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Dodici\Fansworld\WebBundle\Entity\Comment;

use Dodici\Fansworld\WebBundle\Entity\Privacy;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;

class AppState
{
	const LIMIT_WALL = 10;
	
    protected $security_context;
    protected $request;
    protected $em;
    protected $user;

    function __construct(SecurityContext $security_context, EntityManager $em)
    {
        $this->security_context = $security_context;
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->user = $security_context->getToken() ? $security_context->getToken()->getUser() : null;
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
    
    public function canLike($entity) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	$rep = $this->em->getRepository('DodiciFansworldWebBundle:Liking');
    	$liking = $rep->byUserAndEntity($user, $entity);
    	
    	if (count($liking) >= 1) return false;
    	
    	if (method_exists($entity, 'getPrivacy')) {
    		if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
    			if (method_exists($entity, 'getAuthor')) {
	    			if ($user == $entity->getAuthor()) return true;
    				$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    			if (!$frep->UsersAreFriends($user, $entity->getAuthor())) return false;
    			}
    		}
    	}
    	
    	return true;
    }
    
	public function canDislike($entity) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	$rep = $this->em->getRepository('DodiciFansworldWebBundle:Liking');
    	$liking = $rep->byUserAndEntity($user, $entity);
    	
    	if (count($liking) >= 1) return true;
    	else return false;
    }
    
	public function canShare($entity) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	if (method_exists($entity, 'getPrivacy')) {
    		if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
    			if (method_exists($entity, 'getAuthor')) {
	    			if ($user == $entity->getAuthor()) return false;
    				$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    			if (!$frep->UsersAreFriends($user, $entity->getAuthor())) return false;
    			}
    		}
    	} else {
    		if (method_exists($entity, 'getAuthor')) {
    			if ($user == $entity->getAuthor()) return false;
    		}
    	}
    	
    	return true;
    }
    
	public function canView($entity) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	if (method_exists($entity, 'getPrivacy')) {
    		if ($entity->getPrivacy() == \Dodici\Fansworld\WebBundle\Entity\Privacy::FRIENDS_ONLY) {
    			if (method_exists($entity, 'getAuthor')) {
	    			if ($user == $entity->getAuthor()) return true;
    				$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    			if (!$frep->UsersAreFriends($user, $entity->getAuthor())) return false;
    			}
    		}
    	}
    	
    	return true;
    }
    
	public function canComment($entity) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	if ($entity instanceof User) {
    		if ($user == $entity) return true;
    		
    		$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    	if (!$frep->UsersAreFriends($user, $entity)) return false;
    	} else {
    		if ($entity instanceof Comment) {
    			if ($entity->getComment() !== null) return false;
    		}
    		return $this->canView($entity);
    	}
    	
    	return true;
    }
    
	public function canFriend(User $target) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	if ($user == $target) return false;
    	$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    if ($frep->UsersAreFriends($user, $target)) return false;
    	
    	return true;
    }
    
	public function friendshipWith(User $target) 
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	if ($user == $target) return false;
    	$frep = $this->em->getRepository('DodiciFansworldWebBundle:Friendship');
	    return $frep->BetweenUsers($user, $target);
	    
    }
    
    public function getType($entity)
    {
    	$exp = explode('\\', get_class($entity));
    	$classname = end($exp);
    	return strtolower($classname);
    }
    
    public function getComments($entity)
    {
    	$comments = $this->em->getRepository('DodiciFansworldWebBundle:Comment')->wallEntity($entity, self::LIMIT_WALL, 0);
    	return $comments;
    }
    
    public function getPrivacies()
    {
    	if (!($this->user instanceof User)) return false;
    	$user = $this->user;
    	
    	return Privacy::getOptions();
    }
}