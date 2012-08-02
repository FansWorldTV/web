<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\HasIdol;

use Dodici\Fansworld\WebBundle\Entity\HasTeam;

use Dodici\Fansworld\WebBundle\Entity\QuizAnswer;

use Dodici\Fansworld\WebBundle\Entity\Teamship;

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

/**
 * Updates user scores when they perform a score-worthy action
 */
class ScoreHandler
{
    const SCORE_ADD_IDOL = 1;
    const SCORE_ADD_TEAM = 1;
    const SCORE_GET_LIKED = 2;
    const SCORE_NEW_SHARE = 3;
    const SCORE_NEW_PHOTO = 5;
    const SCORE_NEW_FRIENDSHIP = 10;
    const SCORE_NEW_VIDEO = 25;
    const SCORE_INVITE_FRIEND = 20;
    
    //Idolship/Teamship scores
    const SCORE_TAG_TEAM_PHOTO = 5;
    const SCORE_TAG_TEAM_VIDEO = 10;
    const SCORE_TAG_IDOL_PHOTO = 5;
    const SCORE_TAG_IDOL_VIDEO = 10;
    
	protected $em;
	
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$this->em = $em;
		
		if ($entity instanceof Idolship) {
			$this->addScore($entity->getAuthor(), self::SCORE_ADD_IDOL);
			
        	$author = $entity->getAuthor();
            $target = $entity->getIdol(); 
            $author->setIdolCount($author->getIdolCount() + 1);
            $target->setFanCount($target->getFanCount() + 1);
            $em->persist($author);
            $em->persist($target);
            $em->flush();
		}
		
    	if ($entity instanceof Teamship) {
			$this->addScore($entity->getAuthor(), self::SCORE_ADD_TEAM);
			
            $team = $entity->getTeam();
            $team->setFanCount($team->getFanCount() + 1);
            $em->persist($team);
            $em->flush();
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
		
        if ($entity instanceof QuizAnswer) {
			$options = $entity->getOptions();
			foreach ($options as $option) {
			    if ($option->getCorrect() && $entity->getQuizquestion()->getScore()) {
			        $this->addScore($entity->getAuthor(), $entity->getQuizquestion()->getScore());
			        break;
			    }
			}
		}
		
    	if ($entity instanceof Liking) {
			$likedthing = null;
    		$likedthing = $likedthing ?: $entity->getComment();
			$likedthing = $likedthing ?: $entity->getAlbum();
			$likedthing = $likedthing ?: $entity->getVideo();
			$likedthing = $likedthing ?: $entity->getPhoto();
			
			if ($likedthing && $likedthing->getAuthor() && $likedthing->getAuthor() != $entity->getAuthor()) {
				$this->addScore($likedthing->getAuthor(), self::SCORE_GET_LIKED);
			}
		}
		
		if ($entity instanceof Friendship && $entity->getActive()) {
			//$this->addScore($entity->getAuthor(), self::SCORE_NEW_FRIENDSHIP);
			$scoreadd = self::SCORE_NEW_FRIENDSHIP;
			if ($entity->getInvitation()) $scoreadd += self::SCORE_INVITE_FRIEND;
            $this->addScore($entity->getTarget(), $scoreadd);
                
            $author = $entity->getAuthor();
            $target = $entity->getTarget(); 
            $author->setFriendCount($author->getFriendCount() + 1);
            $target->setFriendCount($target->getFriendCount() + 1);
            $em->persist($author);
            $em->persist($target);
            $em->flush();
		}
		
		if ($entity instanceof HasTeam || $entity instanceof HasIdol) {
		    if ($entity instanceof HasTeam) $thingname = 'team';
		    else $thingname = 'idol';
		    
		    $author = $entity->getAuthor();
		    $video = $entity->getVideo();
		    $photo = $entity->getPhoto();
		    
		    $getmethod = 'get'.ucfirst($thingname);
		    $thing = $entity->$getmethod();
		    
		    if ($author && ($video || $photo)) {
		        $tsrepo = $this->em->getRepository('Dodici\Fansworld\WebBundle\Entity\\'.ucfirst($thingname).'ship');
		        $tship = $tsrepo->findOneBy(array('author' => $author->getId(), $thingname => $thing->getId()));
		        if ($tship) {
    		        $score = constant('self::SCORE_TAG_'.strtoupper($thingname).'_'. ($video ? 'VIDEO' : 'PHOTO'));
		            $this->addScore($author, $score);
		            $tship->setScore($tship->getScore() + $score);
		            $this->em->persist($tship);
		            $this->em->flush();
		        }
		    }
		}
    }
    
	public function postUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$this->em = $em;
		
    	if ($entity instanceof Friendship) {
            if ($entity->getActive() == true && $entity->getTarget()->getRestricted()) {
                //$this->addScore($entity->getAuthor(), self::SCORE_NEW_FRIENDSHIP);
                $this->addScore($entity->getTarget(), self::SCORE_NEW_FRIENDSHIP);
                
                $author = $entity->getAuthor();
                $target = $entity->getTarget(); 
                $author->setFriendCount($author->getFriendCount() + 1);
                $target->setFriendCount($target->getFriendCount() + 1);
                $em->persist($author);
                $em->persist($target);
                $em->flush();
            }
        }
        
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$this->em = $em;
		
    	if ($entity instanceof Liking) {
    		$likedthing = null;
    		$likedthing = $likedthing ?: $entity->getComment();
			$likedthing = $likedthing ?: $entity->getAlbum();
			$likedthing = $likedthing ?: $entity->getVideo();
			$likedthing = $likedthing ?: $entity->getPhoto();
			
			if ($likedthing && $likedthing->getAuthor() && $likedthing->getAuthor() != $entity->getAuthor()) {
				$this->addScore($likedthing->getAuthor(), -self::SCORE_GET_LIKED);
			}
        }
        
        if ($entity instanceof Friendship) {
        	if ($entity->getActive() == true) {
        		//$this->addScore($entity->getAuthor(), -self::SCORE_NEW_FRIENDSHIP);
                $this->addScore($entity->getTarget(), -self::SCORE_NEW_FRIENDSHIP);
        		
        		$author = $entity->getAuthor();
                $target = $entity->getTarget(); 
                $author->setFriendCount($author->getFriendCount() - 1);
                $target->setFriendCount($target->getFriendCount() - 1);
                $em->persist($author);
                $em->persist($target);
                $em->flush();
        	}
        }
        
        if ($entity instanceof Idolship) {
        	$this->addScore($entity->getAuthor(), -self::SCORE_ADD_IDOL);
        	
        	$author = $entity->getAuthor();
            $target = $entity->getIdol(); 
            $author->setIdolCount($author->getIdolCount() - 1);
            $target->setFanCount($target->getFanCount() - 1);
            $em->persist($author);
            $em->persist($target);
            $em->flush();
        }
        
    	if ($entity instanceof Teamship) {
			$this->addScore($entity->getAuthor(), -self::SCORE_ADD_TEAM);
			
            $team = $entity->getTeam();
            $team->setFanCount($team->getFanCount() - 1);
            $em->persist($team);
            $em->flush();
		}
    }
    
    private function addScore(User $user, $score)
    {
    	$user->setScore($user->getScore() + $score);
    	if ($user->getScore() < 0) $user->setScore(0);
    	
    	$level = $this->em->getRepository('DodiciFansworldWebBundle:Level')->byScore($user->getScore());
        
    	if ($level) {
	    	if ($user->getLevel() == null || $user->getLevel()->getId() != $level->getId()) {
	        	$user->setLevel($level);
	        }
	    	
	    	$this->em->persist($user);
	    	$this->em->flush();
    	}
    }
}