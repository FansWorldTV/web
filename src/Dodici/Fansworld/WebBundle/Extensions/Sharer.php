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
	protected $appstate;

    function __construct(EntityManager $em, $appstate)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appstate = $appstate;
    }

    /**
     * Share an entity on user's wall
     * @param User $user
     * @param $entity
     */
    public function share(User $user, $entity, $content=null)
    {
    	$classname = $this->appstate->getType($entity);
    	
    	$comment = new Comment();
		$comment->setType(Comment::TYPE_SHARE);
		$comment->setAuthor($user);
		$comment->setTarget($user);
		$comment->setContent($content);
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