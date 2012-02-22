<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Symfony\Component\DependencyInjection\Container;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Liking;
use Dodici\Fansworld\WebBundle\Entity\Friendship;
use Dodici\Fansworld\WebBundle\Entity\Idolship;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

class ScoreHandler
{
    const SCORE_ADD_IDOL = 1;
    const SCORE_GET_LIKED = 2;
    const SCORE_NEW_SHARE = 3;
    const SCORE_NEW_PHOTO = 5;
    const SCORE_NEW_FRIENDSHIP = 10;
    const SCORE_NEW_VIDEO = 25;
    
	protected $em;
	
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$this->em = $em;
		
		if ($entity instanceof Idolship) {
			$this->addScore($entity->getAuthor(), self::SCORE_ADD_IDOL);
		}
		
    	if ($entity instanceof Photo) {
    		if ($entity->getAuthor()) {
    			$this->addScore($entity->getAuthor(), self::SCORE_NEW_PHOTO);
    		}
		}
		
    	if ($entity instanceof Video) {
			if ($entity->getAuthor()) {
    			$this->addScore($entity->getAuthor(), self::SCORE_NEW_VIDEO);
			}
		}
		
    	if ($entity instanceof Comment) {
			if ($entity->getType() == Comment::TYPE_SHARE) {
				$this->addScore($entity->getAuthor(), self::SCORE_NEW_SHARE);
			}
		}
		
    	if ($entity instanceof Liking) {
			$likedthing = null;
    		$likedthing = $likedthing ?: $entity->getComment();
			$likedthing = $likedthing ?: $entity->getAlbum();
			$likedthing = $likedthing ?: $entity->getVideo();
			$likedthing = $likedthing ?: $entity->getPhoto();
			
			if ($likedthing->getAuthor()) {
				$this->addScore($likedthing->getAuthor(), self::SCORE_GET_LIKED);
			}
		}
    }
    
	public function postUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$this->em = $em;
		
    	if ($entity instanceof Friendship) {
            if ($entity->getActive() == true) {
                $this->addScore($entity->getAuthor(), self::SCORE_NEW_FRIENDSHIP);
                $this->addScore($entity->getTarget(), self::SCORE_NEW_FRIENDSHIP);
            }
        }
        
    }
    
    private function addScore(User $user, $score)
    {
    	$user->setScore($user->getScore() + $score);
    	
    	$level = $this->em->getRepository('DodiciFansworldWebBundle:Level')->byScore($user->getScore());
        
    	if ($user->getLevel() == null || $user->getLevel()->getId() != $level->getId()) {
        	$user->setLevel($level);
        }
    	
    	$this->em->persist($user);
    	$this->em->flush();
    }
}