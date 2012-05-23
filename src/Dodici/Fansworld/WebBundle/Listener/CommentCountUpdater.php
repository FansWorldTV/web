<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class CommentCountUpdater
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Comment) {
			$comment = $entity->getComment();
			$album = $entity->getAlbum();
			$contest = $entity->getContest();
			$video = $entity->getVideo();
			$photo = $entity->getPhoto();
			$newspost = $entity->getNewspost();
			$proposal = $entity->getProposal();
			
			if ($comment) {
				$this->increaseCount($em, $comment);
			}
			if ($album) {
				$this->increaseCount($em, $album);
			}
			if ($contest) {
				$this->increaseCount($em, $contest);
			}
			if ($video) {
				$this->increaseCount($em, $video);
			}
			if ($photo) {
				$this->increaseCount($em, $photo);
			}
			if ($newspost) {
				$this->increaseCount($em, $newspost);
			}
			if ($proposal) {
				$this->increaseCount($em, $proposal);
			}
						
			$em->flush();
		}
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
    	$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Comment) {
			$comment = $entity->getComment();
			$album = $entity->getAlbum();
			$contest = $entity->getContest();
			$video = $entity->getVideo();
			$photo = $entity->getPhoto();
			$newspost = $entity->getNewspost();
			$proposal = $entity->getProposal();
			
			if ($comment) {
				$this->increaseCount($em, $comment, -1);
			}
			if ($album) {
				$this->increaseCount($em, $album, -1);
			}
			if ($contest) {
				$this->increaseCount($em, $contest, -1);
			}
			if ($video) {
				$this->increaseCount($em, $video, -1);
			}
			if ($photo) {
				$this->increaseCount($em, $photo, -1);
			}
			if ($newspost) {
				$this->increaseCount($em, $newspost, -1);
			}
			if ($proposal) {
				$this->increaseCount($em, $proposal, -1);
			}
						
			$em->flush();
		}
    }
    
    private function increaseCount(EntityManager $em, $entity, $amount = 1)
    {
    	$entity->setCommentCount($entity->getCommentCount() + $amount);
    	if ($entity->getCommentCount() < 0) $entity->setCommentCount(0);	
    	$em->persist($entity);
    }
}