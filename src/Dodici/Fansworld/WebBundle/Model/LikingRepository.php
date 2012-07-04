<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Dodici\Fansworld\WebBundle\Entity;

/**
 * LikingRepository
 */
class LikingRepository extends CountBaseRepository
{
	/**
	 * Get a Liking by affected entity and author user
	 * @param \Application\Sonata\UserBundle\Entity\User $user
	 * @param mixed $entity
	 */
    public function byUserAndEntity(\Application\Sonata\UserBundle\Entity\User $user, $entity) {
		$relation = $this->getType($entity); 
		
    	return $this->findBy(array('author' => $user->getId(), $relation => $entity->getId()));
	}
}