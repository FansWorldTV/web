<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\HttpFoundation\Request;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Doctrine\ORM\EntityManager;

class Commenter
{
	protected $request;
	protected $em;
	protected $appfacebook;

    function __construct(EntityManager $em, $appfacebook)
    {
        $this->request = Request::createFromGlobals();
        $this->em = $em;
        $this->appfacebook = $appfacebook;
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
    	$classname = $this->getType($entity);
    	
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
		
		if ($entity instanceof Video || $entity instanceof Photo) {
		    $this->appfacebook->comment($entity, $user);
		}
		
		if ($team) $comment->setTeam($team->getId());
		
    	$this->em->persist($comment);
			
		$this->em->flush();
		
		return $comment;
    }    
    
    private function getType($entity)
    {
        $name = $this->em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
}