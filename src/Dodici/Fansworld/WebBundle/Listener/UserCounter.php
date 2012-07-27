<?php 
namespace Dodici\Fansworld\WebBundle\Listener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Photo;
use Dodici\Fansworld\WebBundle\Entity\Video;

class UserCounter
{
	protected $container;

	public function __construct( $container)
	{
		$this->container = $container;
	}

	public function postPersist(LifecycleEventArgs $eventArgs)
	{
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();

		if ($entity instanceof Photo && $entity->getAuthor()) {
			$user = $entity->getAuthor();
			if ($entity->getActive()){
				$counts =  $em->getRepository('DodiciFansworldWebBundle:Photo')->countBy(array('author' => $user->getId()));
				$user->setphotoCount($counts);
			}
		}

		if ($entity instanceof Video && $entity->getAuthor()) {
			$user = $entity->getAuthor();
			if ($entity->getActive()){
				$counts =  $em->getRepository('DodiciFansworldWebBundle:Video')->countBy(array('author' => $user->getId()));
				$user->setVideoCount($counts);
			}
		}
	}
}