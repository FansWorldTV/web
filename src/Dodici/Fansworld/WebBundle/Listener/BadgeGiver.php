<?php

namespace Dodici\Fansworld\WebBundle\Listener;

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

class BadgeGiver
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		$user = null;
		if (property_exists($entity, 'author')) {
		    $user = $entity->getAuthor();
		}
		
		array(
    		self::TYPE_IDOLSHIP => 'Ídolos seguidos',
            self::TYPE_TEAMSHIP => 'Equipos seguidos',
            self::TYPE_FRIENDSHIP => 'Usuarios seguidos',
            self::TYPE_VIDEO => 'Vídeos subidos',
            self::TYPE_PHOTO => 'Fotos subidas',
            self::TYPE_EVENTSHIP => 'Check-ins',
            self::TYPE_CONTESTPARTICIPANT => 'Participaciones Concursos',
            self::TYPE_QUIZANSWER => 'Respuestas Encuestas',
            self::TYPE_COMMENT => 'Comentarios',
            self::TYPE_PROFILEVIEWS => 'Vistas perfil+fotos+videos',
    	);
		
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
		
        /*if ($entity instanceof QuizAnswer) {
			$type = Badge::TYPE_QUIZANSWER;
			$amount = $this->getAmount($user, $entity, $em);
		}
		TODO: photo/video/user views
		*/
		
		/* Add whichever badge steps apply*/
		
		if ($this->addBadgeStepByTypeAndScore($user, $type, $amount, $em)) {
		    $em->flush();
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
        $classname = ucfirst($this->getType($entity));
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
    
    private function getType($entity)
    {
        $exp = explode('\\', get_class($entity));
        $classname = strtolower(end($exp));
        if (strpos($classname, 'proxy') !== false) {
            $classname = str_replace(array('dodicifansworldwebbundleentity', 'proxy'), array('', ''), $classname);
        }
        return $classname;
    }
}