<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

class UserRepository extends CountBaseRepository
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
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR  
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
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
    	
    	'.
        (($limit !== null) ? ' LIMIT :limit ' : '').
        (($offset !== null) ? ' OFFSET :offset ' : '')
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	if ($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int)$offset, Type::INTEGER);
    	
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
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
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
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
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
		$rsm->addScalarResult('isfriend', 'isfriend');

        $query = $this->_em->createNativeQuery('
    	SELECT
		u.*,
		(SELECT COUNT(id) FROM friendship WHERE
			((author_id = u.id AND target_id IN (SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid))
			OR
			(target_id = u.id AND author_id IN (SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid)))
			AND active
		) as commonfriends,
		(SELECT (SELECT COUNT(id) FROM friendship WHERE active AND ((author_id = :userid AND target_id = u.id) OR (author_id = u.id AND target_id = :userid)) >= 1)) AS isfriend
		FROM fos_user_user u
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		u.type = :fantype AND
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isfriend IS NULL OR (
			:isfriend = (SELECT (SELECT COUNT(id) FROM friendship WHERE active AND ((author_id = :userid AND target_id = u.id) OR (author_id = u.id AND target_id = :userid)) >= 1))
		))
		
		ORDER BY isfriend DESC, commonfriends DESC, u.lastname ASC, u.firstname ASC, u.username ASC
		
    	'.
        (($limit !== null) ? ' LIMIT :limit ' : '').
        (($offset !== null) ? ' OFFSET :offset ' : '')
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('fantype', User::TYPE_FAN, Type::INTEGER)
    		->setParameter('isfriend', $isfriend, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	if ($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int)$offset, Type::INTEGER);
    	
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
		u.type = :fantype AND
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isfriend IS NULL OR (
			:isfriend = (SELECT (SELECT COUNT(id) FROM friendship WHERE active AND ((author_id = :userid AND target_id = u.id) OR (author_id = u.id AND target_id = :userid)) >= 1))
		))
		'
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('fantype', User::TYPE_FAN, Type::INTEGER)
    		->setParameter('isfriend', $isfriend, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	$res = $query->getResult();
    	return intval($res[0]['count']);
    }
    
	/**
     * Get all users who have one or more of the given idols
     * @param array_of_users|user $idols
     */
    public function byIdols($idols)
    {
        if (!is_array($idols)) $idols = array($idols);
        $idarr = array();
        foreach ($idols as $idol) $idarr[] = $idol->getId();
        
    	return $this->_em->createQuery('
    	SELECT DISTINCT u
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	INNER JOIN u.idolships iss
    	WHERE u.enabled = true
    	AND iss.target IN ('.join(',',$idarr).')
    	')
        	->getResult();
    }
    
	/**
     * Get all users who have posted in the thread
     * @param \Dodici\Fansworld\WebBundle\Entity\ForumThread $thread
     * @param User::TYPE_* $user_type
     */
    public function byThread($thread, $user_type = \Application\Sonata\UserBundle\Entity\User::TYPE_FAN)
    {
        $result = $this->_em->createQuery('
    	SELECT fp, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\ForumPost fp
    	INNER JOIN fp.author u
    	WHERE u.enabled = true
    	AND fp.forumthread = :threadid
    	AND u.type = :type
    	')
        	->setParameter('threadid', $thread->getId())
        	->setParameter('type', $user_type)
        	->getResult();
        	
        $arr = array();
        foreach ($result as $r) $arr[] = $r->getAuthor();
        return $arr;
    }
    
	/**
     * 
     * Search for idol type users with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isidol (null|true|false)
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
	public function SearchIdolFront(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null, $isidol=null, $limit=null, $offset=null) 
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
		// Amount of user's friends that also have the idol
		$rsm->addScalarResult('commonfriends', 'commonfriends');
		// Whether the user already has the idol or not
		$rsm->addScalarResult('isidol', 'isidol');

        $query = $this->_em->createNativeQuery('
    	SELECT
		u.*,
		(SELECT COUNT(id) FROM idolship WHERE
			(target_id = u.id AND author_id IN (SELECT author_id FROM friendship WHERE active AND target_id = :userid UNION SELECT target_id FROM friendship WHERE active AND author_id = :userid)))
		as commonfriends,
		(SELECT (SELECT COUNT(id) FROM idolship WHERE author_id = :userid AND target_id = u.id) >= 1) AS isidol
		FROM fos_user_user u
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		u.type = :idoltype AND
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isidol IS NULL OR (
			:isidol = (SELECT (SELECT COUNT(id) FROM idolship WHERE author_id = :userid AND target_id = u.id) >= 1)
		))
		
		ORDER BY isidol DESC, commonfriends DESC, u.lastname ASC, u.firstname ASC, u.username ASC
		
    	'.
        (($limit !== null) ? ' LIMIT :limit ' : '').
        (($offset !== null) ? ' OFFSET :offset ' : '')
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('idoltype', User::TYPE_IDOL, Type::INTEGER)
    		->setParameter('isidol', $isidol, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	if ($limit !== null)
            $query = $query->setParameter('limit', (int)$limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int)$offset, Type::INTEGER);
    	
    	return $query->getResult();
    }
    
	/**
     * 
     * Count searched users with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isfriend (null|true|false)
     * @param string $filtername
     */
	public function CountSearchIdolFront(\Application\Sonata\UserBundle\Entity\User $user, $filtername=null, $isidol=null) 
    {
    	$rsm = new ResultSetMapping;
    	$rsm->addScalarResult('countusers', 'count');

        $query = $this->_em->createNativeQuery('
    	SELECT
		COUNT(u.id) as countusers
		FROM fos_user_user u
		'.($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '').'
		WHERE
		u.type = :idoltype AND
		u.enabled AND u.id <> :userid
		'.($filtername ? '
			AND (
			country.title LIKE :filtername OR 
			city.title LIKE :filtername OR 
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR 
			u.lastname LIKE :filtername
		)' : '').'
		
		AND
		(:isidol IS NULL OR (
			:isidol = (SELECT (SELECT COUNT(id) FROM idolship WHERE author_id = :userid AND target_id = u.id) >= 1)
		))
		'
    	, $rsm)
    		->setParameter('userid', $user->getId(), Type::BIGINT)
    		->setParameter('idoltype', User::TYPE_IDOL, Type::INTEGER)
    		->setParameter('isidol', $isidol, Type::BOOLEAN);
    		
    	if ($filtername)
    		$query = $query->setParameter('filtername', '%'.$filtername.'%');
    	
    	$res = $query->getResult();
    	return intval($res[0]['count']);
    }
}