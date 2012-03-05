<?php

namespace Dodici\Fansworld\WebBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Doctrine\ORM\EntityManager;

class Commenter
{
	protected $request;
	protected $em;

    function __construct(EntityManager $em)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
    }

    /**
     * Create a comment on entity, authored by user, with text content, and privacy_type
     * @param User $user
     * @param $entity
     * @param string $content
     * @param Privacy::* $privacy_type
     */
    public function comment(User $user, $entity, $content, $privacy_type = Privacy::EVERYONE)
    {
    	$exp = explode('\\', get_class($entity));
    	$classname = end($exp);
    	
    	$comment = new Comment();
		$comment->setType(Comment::TYPE_COMMENT);
		$comment->setAuthor($user);
		$comment->setPrivacy($privacy_type);
		$comment->setContent($content);
		
		if ($entity instanceof User) {
			$comment->setTarget($entity);
		} else {
			$methodname = 'set'.$classname;
			$comment->$methodname($entity);
		}
		
    	$this->em->persist($comment);
			
		$this->em->flush();
		
		return $comment;
    }    
}