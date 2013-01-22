<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Symfony\Component\HttpFoundation\Request;

use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Doctrine\ORM\EntityManager;

class Commenter
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
     * Create a comment on entity, authored by user, with text content, and privacy_type
     * @param User $user
     * @param $entity
     * @param string $content
     * @param Privacy::* $privacy_type
     */
    public function comment(User $user, $entity, $content, $privacy_type = Privacy::EVERYONE, $team=null)
    {
    	$classname = $this->appstate->getType($entity);
    	
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
		
		if ($team) $comment->setTeam($team->getId());
		
    	$this->em->persist($comment);
			
		$this->em->flush();
		
		return $comment;
    }    
}