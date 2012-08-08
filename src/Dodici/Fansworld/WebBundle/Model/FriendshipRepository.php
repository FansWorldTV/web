<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

/**
 * FriendshipRepository
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
    	(fs.author = :userone) AND (fs.target = :usertwo)
    	')
    		->setParameter('userone', $userone->getId(), Type::BIGINT)
    		->setParameter('usertwo', $usertwo->getId(), Type::BIGINT)
    		->getOneOrNullResult();
    }
    
	/**
     * Get pending friendship requests for user
     */
    public function Pending(\Application\Sonata\UserBundle\Entity\User $user, $limit = null, $offset = null)
    {
    	$query = $this->_em->createQuery('
    	SELECT fs, a
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	JOIN fs.author a
    	WHERE
    	fs.target = :userid AND fs.active=false
    	')
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($limit !== null)
    	$query = $query->setMaxResults($limit);
    	
    	if ($offset !== null)
    	$query = $query->setFirstResult($offset);
    		
    	return $query->getResult();
    }
    
	/**
     * Count pending friendships for user
     */
    public function CountPending(\Application\Sonata\UserBundle\Entity\User $user)
    {
    	$query = $this->_em->createQuery('
    	SELECT COUNT(fs.id)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	WHERE
    	fs.target = :userid AND fs.active=false
    	')
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	return (int)$query->getSingleScalarResult();
    }
    
	/**
     * Get whether the users are friends or not
     * @param \Application\Sonata\UserBundle\Entity\User $userone
     * @param \Application\Sonata\UserBundle\Entity\User $usertwo
     */
	public function UsersAreFriends(\Application\Sonata\UserBundle\Entity\User $userone, \Application\Sonata\UserBundle\Entity\User $usertwo) 
    {
    	return count($this->findOneBy(array('author' => $userone, 'target' => $usertwo, 'active' => true))) > 0;
    	
    	/*$rsm = new ResultSetMapping;
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
    	return intval($res[0]['count']) > 0;*/
    }
    
}