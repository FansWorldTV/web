<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\DBAL\Types\Type;

use Doctrine\ORM\EntityRepository;

/**
 * NotificationRepository
 */
class NotificationRepository extends CountBaseRepository
{
	/**
	 * Get notifications for user
	 * @param User $user
	 * @param boolean|null $readed
	 * @param int|null $limit
	 * @param int|null $offset
	 */
    public function latest(\Application\Sonata\UserBundle\Entity\User $user, $readed = null, $limit = null, $offset = null)
    {
    	$query = $this->_em->createQuery('
    	SELECT n
    	FROM \Dodici\Fansworld\WebBundle\Entity\Notification n
    	WHERE
    	n.target = :userid AND n.active=true
    	'
    	. ($readed !== null ? ' AND n.readed = :readed ' : '') .
    	'
    	ORDER BY  n.createdAt DESC
    	'
    	)
    		->setParameter('userid', $user->getId(), Type::BIGINT);

    	if ($readed !== null)
		$query = $query->setParameter('readed', $readed, Type::BOOLEAN);
    	
    	if ($limit !== null)
    	$query = $query->setMaxResults($limit);
    	
    	if ($offset !== null)
    	$query = $query->setFirstResult($offset);
    		
    	return $query->getResult();
    }
    
	/**
	 * Count notifications for user
	 * @param User $user
	 * @param boolean|null $readed
	 */
    public function countLatest(\Application\Sonata\UserBundle\Entity\User $user, $readed = null)
    {
    	$query = $this->_em->createQuery('
    	SELECT COUNT(n.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Notification n
    	WHERE
    	n.target = :userid AND n.active=true
    	'
    	. ($readed !== null ? ' AND n.readed = :readed ' : '')
    	)
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($readed !== null)
		$query = $query->setParameter('readed', $readed, Type::BOOLEAN);
    		
    	return (int)$query->getSingleScalarResult();
    }
    
	/**
	 * Count notifications for user grouped by type
	 * @param User $user
	 * @param boolean|null $readed
	 */
    public function typeCounts(\Application\Sonata\UserBundle\Entity\User $user, $readed = null)
    {
    	$query = $this->_em->createQuery('
    	SELECT n.type, COUNT(n.id) AS cnt
    	FROM \Dodici\Fansworld\WebBundle\Entity\Notification n
    	WHERE
    	n.target = :userid AND n.active=true '
    	. ($readed !== null ? ' AND n.readed = :readed ' : '') .
    	'GROUP BY n.type'
    	)
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($readed !== null)
		$query = $query->setParameter('readed', $readed, Type::BOOLEAN);
    		
    	return $query->getResult();
    }
}