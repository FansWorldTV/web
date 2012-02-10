<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Liking;

class LikingCountUpdater
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Liking) {
			$comment = $entity->getComment();
			$album = $entity->getAlbum();
			$contest = $entity->getContest();
			$video = $entity->getVideo();
			$photo = $entity->getPhoto();
			$newspost = $entity->getNewspost();
			
			if ($comment) {
				$comment->likeUp();
				$em->persist($comment);
			}
			if ($album) {
				$album->likeUp();
				$em->persist($album);
			}
			if ($contest) {
				$contest->likeUp();
				$em->persist($contest);
			}
			if ($video) {
				$video->likeUp();
				$em->persist($video);
			}
			if ($photo) {
				$photo->likeUp();
				$em->persist($photo);
			}
			if ($newspost) {
				$newspost->likeUp();
				$em->persist($newspost);
			}
			
			$em->flush();
		}
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Liking) {
			$comment = $entity->getComment();
			$album = $entity->getAlbum();
			$contest = $entity->getContest();
			$video = $entity->getVideo();
			$photo = $entity->getPhoto();
			$newspost = $entity->getNewspost();
			
			if ($comment) {
				$comment->likeDown();
				$em->persist($comment);
			}
			if ($album) {
				$album->likeDown();
				$em->persist($album);
			}
			if ($contest) {
				$contest->likeDown();
				$em->persist($contest);
			}
			if ($video) {
				$video->likeDown();
				$em->persist($video);
			}
			if ($photo) {
				$photo->likeDown();
				$em->persist($photo);
			}
			if ($newspost) {
				$newspost->likeDown();
				$em->persist($newspost);
			}
			
			$em->flush();
		}
    }
}