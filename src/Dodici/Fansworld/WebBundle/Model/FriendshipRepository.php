<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

/**
 * FriendshipRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FriendshipRepository extends CountBaseRepository
{
	/**
     * Get friendship object between two users
     */
    public function BetweenUsers(\Application\Sonata\UserBundle\Entity\User $userone, \Application\Sonata\UserBundle\Entity\User $usertwo)
    {
    	return $this->_em->createQuery('
    	SELECT fs
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	WHERE
    	(
    	  (fs.target = :userone AND fs.author = :usertwo)
    	  OR
    	  (fs.author = :userone AND fs.target = :userone)
    	)
    	')
    		->setParameter('userone', $userone->getId(), Type::BIGINT)
    		->setParameter('usertwo', $usertwo->getId(), Type::BIGINT)
    		->getResult();
    }
    
	/**
     * Get pending friendship requests for user
     */
    public function Pending(\Application\Sonata\UserBundle\Entity\User $user)
    {
    	return $this->_em->createQuery('
    	SELECT fs
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	WHERE
    	fs.target = :userid AND fs.active=false
    	')
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->getResult();
    }
    
	/**
     * Get whether the users are friends or not
     * @param \Application\Sonata\UserBundle\Entity\User $userone
     * @param \Application\Sonata\UserBundle\Entity\User $usertwo
     */
	public function UsersAreFriends(\Application\Sonata\UserBundle\Entity\User $userone, \Application\Sonata\UserBundle\Entity\User $usertwo) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addScalarResult('countfriends', 'count');

        $query = $this->_em->createNativeQuery('
    	SELECT
		COUNT(fs.id) as countfriends
		FROM friendship fs
		WHERE
		((fs.author_id = :userone AND fs.target_id = :usertwo) OR (fs.target_id = :userone AND fs.author_id = :usertwo))
		AND fs.active
		'
    	, $rsm)
    		->setParameter('userone', $userone->getId(), Type::BIGINT)
    		->setParameter('usertwo', $usertwo->getId(), Type::BIGINT);
    		
    	$res = $query->getResult();
    	return intval($res[0]['count']) > 0;
    }
}