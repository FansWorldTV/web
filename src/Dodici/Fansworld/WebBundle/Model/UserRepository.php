<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

class UserRepository extends EntityRepository
{
    /**
     * Get the user's friends with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
	public function FriendUsers(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null, $limit=null, $offset=null) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addEntityResult('Application\Sonata\UserBundle\Entity\User', 'u');
		$rsm->addFieldResult('u', 'id', 'id');
    	$rsm->addFieldResult('u', 'username', 'username');
		$rsm->addFieldResult('u', 'email', 'email');
		$rsm->addFieldResult('u', 'firstname', 'firstname');
		$rsm->addFieldResult('u', 'lastname', 'lastname');
		$rsm->addMetaResult('u', 'image_id', 'image_id');
		$rsm->addMetaResult('u', 'country_id', 'country_id');
		$rsm->addMetaResult('u', 'city_id', 'city_id');

        $query = $this->_em->createNativeQuery('
    	SELECT
		u.*
		FROM friendship fs
		INNER JOIN fos_user_user u ON u.id = fs.target_id
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		fs.author_id = :userid
		AND fs.active AND u.enabled
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		UNION
		
		SELECT
		u.*
		FROM friendship fs
		INNER JOIN fos_user_user u ON u.id = fs.author_id
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		fs.target_id = :userid
		AND fs.active AND u.enabled
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
    	
    	'.
        (($limit !== null) ? 'LIMIT :limit' : '').
        (($offset !== null) ? 'OFFSET :offset' : '')
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	if ($limit !== null)
            $query = $query->setParameter('limit', $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', $offset, Type::INTEGER);
    	
    	return $query->getResult();
    }
    
	/**
     * Count the user's friends with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param string $filtername
     */
	public function CountFriendUsers(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addScalarResult('countfriends', 'count');

        $query = $this->_em->createNativeQuery('
    	SELECT (
        (SELECT
		COUNT(u.id)
		FROM friendship fs
		INNER JOIN fos_user_user u ON u.id = fs.target_id
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		fs.author_id = :userid
		AND fs.active AND u.enabled
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').')
		
		+
		
		(SELECT
		COUNT(u.id)
		FROM friendship fs
		INNER JOIN fos_user_user u ON u.id = fs.author_id
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		fs.target_id = :userid
		AND fs.active AND u.enabled
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').')
    	) as countfriends
    	'
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	$res = $query->getResult();
    	return intval($res[0]['count']);
    }
    
    /**
     * 
     * Search for users with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isfriend (null|true|false)
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
	public function SearchFront(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null, $isfriend=null, $limit=null, $offset=null) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addEntityResult('Application\Sonata\UserBundle\Entity\User', 'u');
		$rsm->addFieldResult('u', 'id', 'id');
    	$rsm->addFieldResult('u', 'username', 'username');
		$rsm->addFieldResult('u', 'email', 'email');
		$rsm->addFieldResult('u', 'firstname', 'firstname');
		$rsm->addFieldResult('u', 'lastname', 'lastname');
		$rsm->addMetaResult('u', 'image_id', 'image_id');
		$rsm->addMetaResult('u', 'country_id', 'country_id');
		$rsm->addMetaResult('u', 'city_id', 'city_id');
		$rsm->addScalarResult('commonfriends', 'commonfriends');

        $query = $this->_em->createNativeQuery('
    	SELECT
		u.*,
		(SELECT COUNT(id) FROM friendship WHERE
			((author_id = u.id AND target_id IN (SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))
			OR
			(target_id = u.id AND author_id IN (SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid)))
			AND active
		) as commonfriends
		FROM fos_user_user u
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isfriend IS NULL OR (
			(:isfriend=true AND (u.id IN ((SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))))
			OR
			(:isfriend=false AND (u.id NOT IN ((SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))))
		))
		
		ORDER BY commonfriends DESC, u.lastname ASC, u.firstname ASC, u.username ASC
		
    	'.
        (($limit !== null) ? 'LIMIT :limit' : '').
        (($offset !== null) ? 'OFFSET :offset' : '')
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('isfriend', $isfriend, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	if ($limit !== null)
            $query = $query->setParameter('limit', $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', $offset, Type::INTEGER);
    	
    	return $query->getResult();
    }
    
	/**
     * 
     * Count searched users with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isfriend (null|true|false)
     * @param string $filtername
     */
	public function CountSearchFront(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null, $isfriend=null) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addScalarResult('countusers', 'count');

        $query = $this->_em->createNativeQuery('
    	SELECT
		COUNT(u.id) as countusers
		FROM fos_user_user u
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isfriend IS NULL OR (
			(:isfriend=true AND (u.id IN ((SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))))
			OR
			(:isfriend=false AND (u.id NOT IN ((SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))))
		))
		'
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('isfriend', $isfriend, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	$res = $query->getResult();
    	return intval($res[0]['count']);
    }
}