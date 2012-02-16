<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Doctrine\ORM\EntityManager;

class Sharer
{
	protected $request;
	protected $em;

    function __construct(EntityManager $em)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    /**
     * Share an entity on user's wall
     * @param User $user
     * @param $entity
     */
    public function share(User $user, $entity)
    {
    	$exp = explode('\\', get_class($entity));
    	$classname = end($exp);
    	
    	$comment = new Comment();
		$comment->setType(Comment::TYPE_SHARE);
		$comment->setAuthor($user);
		$comment->setTarget($user);
		$comment->setPrivacy(Privacy::FRIENDS_ONLY);
		
		$share = new Share();
    	$methodname = 'set'.$classname;
    	$share->$methodname($entity);
    	if (method_exists($entity, 'getAuthor')) {
    		$share->setAuthor($entity->getAuthor());
    	}
    	
    	$comment->setShare($share);
		
    	$this->em->persist($comment);
			
		$this->em->flush();
    }    
}