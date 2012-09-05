<?php 
namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\HasIdol;
use Dodici\Fansworld\WebBundle\Entity\HasTeam;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;

/**
 * Updates user/team/idol's photo and video counts when they're created/tagged
 */
class PhotoVideoCounter
{

	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		$this->updateCounts($eventArgs->getEntity(), $eventArgs->getEntityManager());
	}
	
	public function postRemove(LifecycleEventArgs $eventArgs)
	{
	    $this->updateCounts($eventArgs->getEntity(), $eventArgs->getEntityManager());
	}
	
	private function updateCounts($entity, $em)
	{
	    if ($entity instanceof Photo || $entity instanceof Video) {
			$type = ($entity instanceof Photo) ? 'Photo' : 'Video';
			$setcountmethodname = 'set'.$type.'Count';
		    
		    if ($entity->getAuthor()) {
    		    $user = $entity->getAuthor();
    			if ($entity->getActive()){
    				$counts =  $em->getRepository('DodiciFansworldWebBundle:'.$type)->countBy(array('author' => $user->getId(), 'active' => true));
    				$user->$setcountmethodname($counts);
    				$em->persist($user);
    				$em->flush($user);
    			}
			}
		}

		if ($entity instanceof HasTeam || $entity instanceof HasIdol) {
			if ($entity->getVideo() || $entity->getPhoto()) {
			    $tagtype = ($entity instanceof HasTeam) ? 'Team' : 'Idol';
			    $taggedtype = ($entity->getPhoto()) ? 'Photo' : 'Video';
			    $taggedentity = ($entity instanceof HasTeam ? $entity->getTeam() : $entity->getIdol());
			    $setcountmethodname = 'set'.$taggedtype.'Count';
			    
			    $counts =  $em->getRepository('DodiciFansworldWebBundle:'.$tagtype)->countTagged(
			        $taggedentity,
			        strtolower($taggedtype)
			    );
			    
			    $taggedentity->$setcountmethodname($counts);
			    $em->persist($taggedentity);
			    $em->flush();
			}
		}
	}
}