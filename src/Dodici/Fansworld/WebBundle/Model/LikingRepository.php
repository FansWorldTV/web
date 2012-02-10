<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Dodici\Fansworld\WebBundle\Entity;

/**
 * LikingRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LikingRepository extends CountBaseRepository
{
	public function byUserAndEntity(\Application\Sonata\UserBundle\Entity\User $user, $entity) {
		$exp = explode('\\', get_class($entity));
		$relation = strtolower(end($exp));
		return $this->_em->createQuery('
    	SELECT lk
    	FROM \Dodici\Fansworld\WebBundle\Entity\Liking lk
    	WHERE
    	lk.author = :user
    	AND
    	lk.'.$relation.' = :entity
    	')
    		->setParameter('user', $user->getId())
    		->setParameter('entity', $entity->getId())
    		->getResult();
	}
}