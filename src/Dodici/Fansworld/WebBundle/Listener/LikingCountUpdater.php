<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Liking;
use Dodici\Fansworld\WebBundle\Entity\Share;
use Dodici\Fansworld\WebBundle\Entity\Privacy;

/**
 * Updates Likecounts in entities
 */
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
			$proposal = $entity->getProposal();
			
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
			if ($proposal) {
				$proposal->likeUp();
				$em->persist($newspost);
			}
			
			$this->createLikesComment($em, $entity);
			
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
			$proposal = $entity->getProposal();
			
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
			if ($proposal) {
				$proposal->likeUp();
				$em->persist($newspost);
			}
			
			$em->flush();
		}
    }
    
    private function createLikesComment(EntityManager $em, Liking $entity)
    {
    	// wall: a ... le gusta ...
		$comment = new Comment();
		$comment->setType(Comment::TYPE_LIKES);
		$comment->setAuthor($entity->getAuthor());
		$comment->setTarget($entity->getAuthor());
		$comment->setPrivacy(Privacy::FRIENDS_ONLY);
		
		$share = new Share();
    	if ($entity->getComment()) $share->setComment($entity->getComment());
    	if ($entity->getAlbum()) $share->setAlbum($entity->getAlbum());
    	if ($entity->getPhoto()) $share->setPhoto($entity->getPhoto());
    	if ($entity->getVideo()) $share->setVideo($entity->getVideo());
    	if ($entity->getContest()) $share->setContest($entity->getContest());
    	if ($entity->getNewspost()) $share->setNewspost($entity->getNewspost());
    	if ($entity->getProposal()) $share->setProposal($entity->getProposal());
    		
    	$comment->setShare($share);
			
    	$em->persist($comment);
    }
}