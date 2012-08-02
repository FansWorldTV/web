<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\QuizAnswer;

use Dodici\Fansworld\WebBundle\Entity\Visit;

use Dodici\Fansworld\WebBundle\Entity\Comment;

use Dodici\Fansworld\WebBundle\Entity\ContestParticipant;

use Dodici\Fansworld\WebBundle\Entity\Eventship;

use Dodici\Fansworld\WebBundle\Entity\Photo;

use Dodici\Fansworld\WebBundle\Entity\Video;

use Dodici\Fansworld\WebBundle\Entity\Friendship;

use Dodici\Fansworld\WebBundle\Entity\Teamship;

use Dodici\Fansworld\WebBundle\Entity\Idolship;
use Dodici\Fansworld\WebBundle\Entity\Badge;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Listens to user's badge-worthy actions, and awards badges if necessary
 */
class BadgeGiver
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$user = null; $type = null; $amount = null;
		if (property_exists($entity, 'author')) {
		    $user = $entity->getAuthor();
		}
		
    	/* Determine type/amount of badgeable thing */
    	
    	if ($entity instanceof Idolship) {
			$type = Badge::TYPE_IDOLSHIP;
			$amount = $user->getIdolcount();
		}
		
        if ($entity instanceof Teamship) {
			$type = Badge::TYPE_TEAMSHIP;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof Friendship) {
			$type = Badge::TYPE_FRIENDSHIP;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof Eventship) {
			$type = Badge::TYPE_EVENTSHIP;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof Video && $entity->getAuthor()) {
			$type = Badge::TYPE_VIDEO;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof Photo && $entity->getAuthor()) {
			$type = Badge::TYPE_PHOTO;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof ContestParticipant) {
			$type = Badge::TYPE_CONTESTPARTICIPANT;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof Comment) {
			$type = Badge::TYPE_COMMENT;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
        if ($entity instanceof QuizAnswer) {
			$type = Badge::TYPE_QUIZANSWER;
			$amount = $this->getAmount($user, $entity, $em);
		}
		
		//TODO: OFFLOAD VISIT LOAD TO BATCH?
        if ($entity instanceof Visit) {
			$photo = $entity->getPhoto();
			$video = $entity->getVideo();
			$target = $entity->getTarget();
			$author = null;
			if ($photo) {
			    $author = $photo->getAuthor();
			    if ($author) {
			        $author->setPhotoVisitCount($author->getPhotoVisitCount() + 1);
			        $em->persist($author);
			    }
			}
            elseif ($video) {
			    $author = $video->getAuthor();
			    if ($author) {
			        $author->setVideoVisitCount($author->getVideoVisitCount() + 1);
			        $em->persist($author);
			    }
			}
			elseif ($target) {
			    $author = $target;
			}
			
			if ($author) {
			    $amount = $author->getVisitCount() + $author->getPhotoVisitCount() + $author->getVideoVisitCount();
			    $type = Badge::TYPE_PROFILEVIEWS;
			    $user = $author;
			}
		}
		
		
		/* Add whichever badge steps apply*/
		if ($user && $type && $amount) {
    		if ($this->addBadgeStepByTypeAndScore($user, $type, $amount, $em)) {
    		    $em->flush();
    		}
		}
		
    }
    
    private function addBadgeStepByTypeAndScore(User $user, $type, $amount, $em)
    {
        $badgestepstogive = $em->getRepository('DodiciFansworldWebBundle:BadgeStep')->toGive($user, $type, $amount);
        
        if ($badgestepstogive) {
            foreach ($badgestepstogive as $bs) {
                $user->addBadgeStep($bs);
            }
            $em->persist($user);
        }
        
        return $badgestepstogive;
    }
    
    private function getAmount($user, $entity, $em)
    {
        return $this->getRepository($entity, $em)->countBy(array('author' => $user->getId()));
    }
    
    private function getRepository($entity, $em)
    {
        $classname = ucfirst($this->getType($entity, $em));
        if ($classname == 'Newspost') $classname = 'NewsPost';
        if ($classname == 'Forumpost') $classname = 'ForumPost';
        if ($classname == 'Contestparticipant') $classname = 'ContestParticipant';
        if ($classname == 'Quizanswer') $classname = 'QuizAnswer';
    	if (strtolower($classname) == 'user') {
            return $em->getRepository("ApplicationSonataUserBundle:User");
        } else {
            return $em->getRepository("DodiciFansworldWebBundle:" . $classname);
        }
    }
    
    private function getType($entity, $em)
    {
        $name = $em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
}