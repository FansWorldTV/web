<?php

namespace Dodici\Fansworld\WebBundle\Listener;

use Dodici\Fansworld\WebBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Dodici\Fansworld\WebBundle\Entity\Album;
use Dodici\Fansworld\WebBundle\Entity\Photo;

class AlbumImageSetter
{
    
	public function postPersist(LifecycleEventArgs $eventArgs)
    {
		$entity = $eventArgs->getEntity();
		$em = $eventArgs->getEntityManager();
		
		if ($entity instanceof Photo) {
			$album = $entity->getAlbum();
			if ($album) {
				$album->setImage($entity->getImage());
				$album->setPhotoCount($album->getPhotoCount()+1);
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
				$photos = $album->getPhotos();
				if ($photos) {
					$album->setImage(end($photos)->getImage());
				}
				if ($album->getPhotoCount() > 1) {
					$album->setPhotoCount($album->getPhotoCount()-1);
				}
				$em->persist($album);
				$em->flush();
			}
		}
    }
    
}