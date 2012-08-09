<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Album;
use Dodici\Fansworld\WebBundle\Entity\Photo;

/**
 * Sets the thumbnail of an album as the album's last uploaded photo
 */
class AlbumImageSetter
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Photo && $entity->getActive()) {
			$album = $entity->getAlbum();
			if ($album) {
				$this->setAlbumImage($album);
				$em->persist($album);
				$em->flush();
			}
		}
    }
    
	public function postRemove(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Photo) {
			$album = $entity->getAlbum();
			if ($album) {
				$this->setAlbumImage($album);
				$em->persist($album);
				$em->flush();
			}
		}
    }
    
	public function postUpdate(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Photo && $entity->getActive() == false) {
			$album = $entity->getAlbum();
			if ($album) {
				$this->setAlbumImage($album);
				$em->persist($album);
				$em->flush();
			}
		}
    }
    
    private function setAlbumImage($album) {
    	$photos = $album->getPhotos();
		$lastphoto = null;
		$count = 0;
		foreach ($photos as $ph) {
			if ($ph->getActive()) {
				$lastphoto = $ph;
				$count++;
			}
		}
		if ($lastphoto) {
			$album->setImage($lastphoto->getImage());
		}
		$album->setPhotoCount($count);
    }
    
}