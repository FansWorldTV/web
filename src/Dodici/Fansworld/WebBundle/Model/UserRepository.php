<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\DBAL\Types\Type;

/**
 * UserRepository
 */
class UserRepository extends CountBaseRepository
{

	/**
     * Get ranking position in the site
     * @param User $user
     */
    public function rankingPosition(User $user)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addScalarResult('total', 'score');
        $rsm->addScalarResult('rank', 'rank');
        $rsm->addScalarResult('maxpos', 'maxpos');

        $query = $this->_em->createNativeQuery('
            SELECT
            	sh1.score AS total,
            	COUNT(sh2.id)+1 AS rank,
            	(SELECT COUNT(*) FROM fos_user_user WHERE enabled = true AND type = '.User::TYPE_FAN.') AS maxpos
            FROM fos_user_user sh1
            LEFT JOIN fos_user_user sh2 ON
            	(sh1.score < sh2.score)
            	OR
            	((sh1.score = sh2.score) AND (sh1.id < sh2.id))
            WHERE sh1.id = :user
            '
	    , $rsm)
                ->setParameter('user', $user->getId());

        $result = $query->getResult();

        return $result;
    }

    /**
     * Get the user's friends (followers) with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
    public function FriendUsers(\Application\Sonata\UserBundle\Entity\User $user, $filtername = null, $limit = null, $offset = null, $sortby=null)
    {

        $dql = '
      SELECT fs, u
      FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
      JOIN fs.author u
      WHERE fs.active = true AND fs.target = :user AND u.enabled = true
      ' . ($filtername ? '
      AND (
      u.username LIKE :filtername OR
      u.email LIKE :filtername OR
      u.firstname LIKE :filtername OR
      u.lastname LIKE :filtername
    )' : '') . '
      ';

      if ($sortby !== null) {
          $orderby = 'ORDER BY u.'.$sortby.' DESC';
          $dql = $dql.$orderby;
      }

        $query = $this->_em->createQuery($dql)
                ->setParameter('user', $user->getId());

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        $results = array();
        foreach ($query->getResult() as $r) {
            $results[] = $r->getAuthor();
        }
        return $results;

        /* $rsm = new ResultSetMapping;
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

          return $query->getResult(); */
    }

    /**
     * Count the user's friends with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param string $filtername
     */
    public function CountFriendUsers(\Application\Sonata\UserBundle\Entity\User $user, $filtername = null)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(fs)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fs
    	JOIN fs.author u
    	WHERE fs.active = true AND fs.target = :user AND u.enabled = true
    	' . ($filtername ? '
			AND (
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR
			u.lastname LIKE :filtername
		)' : '') . '
    	')
                ->setParameter('user', $user->getId())
        ;

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

        return $query->getSingleScalarResult();
        /*
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
          return intval($res[0]['count']); */
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
    public function SearchFront(\Application\Sonata\UserBundle\Entity\User $user = null, $filtername = null, $isfriend = null, $limit = null, $offset = null)
    {

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('Application\Sonata\UserBundle\Entity\User', 'u');
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
		' . ($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '') . '
		WHERE
		u.type = :fantype AND
		u.enabled AND (:userid IS NULL OR (u.id <> :userid))
		' . ($filtername ? '
			AND (
			country.title LIKE :filtername OR
			city.title LIKE :filtername OR
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR
			u.lastname LIKE :filtername
		)' : '') . '

		AND
		(
		:userid IS NULL OR
		(:isfriend IS NULL OR (
			:isfriend = (SELECT (SELECT COUNT(id) FROM friendship WHERE active AND ((author_id = :userid AND target_id = u.id) OR (author_id = u.id AND target_id = :userid)) >= 1))
		))
		)

		ORDER BY isfriend DESC, commonfriends DESC, u.lastname ASC, u.firstname ASC, u.username ASC

    	' .
                        (($limit !== null) ? ' LIMIT :limit ' : '') .
                        (($offset !== null) ? ' OFFSET :offset ' : '')
                        , $rsm)
                ->setParameter('userid', $user ? $user->getId() : null, Type::BIGINT)
                ->setParameter('fantype', User::TYPE_FAN, Type::INTEGER)
                ->setParameter('isfriend', $isfriend, Type::BOOLEAN);

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);

        return $query->getResult();
    }

    /**
     *
     * Count searched users with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isfriend (null|true|false)
     * @param string $filtername
     */
    public function CountSearchFront(\Application\Sonata\UserBundle\Entity\User $user = null, $filtername = null, $isfriend = null, $limit = null, $offset = null)
    {
        $rsm = new ResultSetMapping;
        $rsm->addScalarResult('countusers', 'count');

        $query = $this->_em->createNativeQuery('
    	SELECT
		COUNT(u.id) as countusers
		FROM fos_user_user u
		' . ($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '') . '
		WHERE
		u.type = :fantype AND
		u.enabled AND (:userid IS NULL OR (u.id <> :userid))
		' . ($filtername ? '
			AND (
			country.title LIKE :filtername OR
			city.title LIKE :filtername OR
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR
			u.lastname LIKE :filtername
		)' : '') . '

		AND
		(
		:userid IS NULL OR
		(:isfriend IS NULL OR (
			:isfriend = (SELECT (SELECT COUNT(id) FROM friendship WHERE active AND ((author_id = :userid AND target_id = u.id) OR (author_id = u.id AND target_id = :userid)) >= 1))
		))
		)
		'
                        , $rsm)
                ->setParameter('userid', $user ? $user->getId() : null, Type::BIGINT)
                ->setParameter('fantype', User::TYPE_FAN, Type::INTEGER)
                ->setParameter('isfriend', $isfriend, Type::BOOLEAN);

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        $res = $query->getResult();
        return intval($res[0]['count']);
    }

    /**
     * Get all users who have one or more of the given idols
     * @param array_of_idols|idol $idols
     */
    public function byIdols($idols, $limit=null, $sortby=null, $offset = null)
    {
        if (!is_array($idols))
            $idols = array($idols);
        $idarr = array();
        foreach ($idols as $idol)
            $idarr[] = $idol->getId();

        if (!$idarr) throw new \Exception('No idols provided for byIdols');

        $dql = '
                SELECT DISTINCT u
                FROM \Application\Sonata\UserBundle\Entity\User u
                INNER JOIN u.idolships iss
                WHERE u.enabled = true
                AND iss.idol IN (:idarr)';
        if ($sortby !== null) {
          $orderby = 'ORDER BY u.'.$sortby.' DESC';
          $dql = $dql.$orderby;
        }

        $query = $this->_em->createQuery($dql)
                ->setParameter('idarr', $idarr);

        if ($limit !== null)
          $query = $query->setMaxResults($limit);
        if ($offset !== null)
          $query = $query->setFirstResult($offset);

        return $query->getResult();
    }


    /**
     * Get all users who have one or more of the given teams
     * @param array_of_teams|team $teams
     */
    public function byTeams($teams, $limit=null, $sortby=null, $offset = null)
    {
        if (!is_array($teams))
            $teams = array($teams);
        $tmarr = array();
        foreach ($teams as $team)
            $tmarr[] = $team->getId();

        if (!$tmarr) throw new \Exception('No teams provided for byTeams');

        $dql = '
                SELECT DISTINCT u
                FROM \Application\Sonata\UserBundle\Entity\User u
                INNER JOIN u.teamships tms
                WHERE u.enabled = true
                AND tms.team IN (:tmarr)
                ';
        if ($sortby !== null) {
            $orderby = 'ORDER BY u.'.$sortby.' DESC';
            $dql = $dql.$orderby;
        }

        $query = $this->_em->createQuery($dql)
            ->setParameter('tmarr', $tmarr);

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
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
        foreach ($result as $r)
            $arr[] = $r->getAuthor();
        return $arr;
    }

    /**
     * Get matching friends with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
    public function matching(\Application\Sonata\UserBundle\Entity\User $user, $filtername = null, $limit = null, $offset = null)
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
		' . ($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '') . '
		WHERE
		fs.author_id = :userid
		AND fs.active AND u.enabled
		' . ($filtername ? '
			AND (
			country.title LIKE :filtername OR
			city.title LIKE :filtername OR
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR
			u.lastname LIKE :filtername
		)' : '') . '

		UNION

		SELECT
		u.*
		FROM friendship fs
		INNER JOIN fos_user_user u ON u.id = fs.author_id
		' . ($filtername ? 'LEFT JOIN country ON country.id = u.country_id LEFT JOIN city ON city.id = u.city_id' : '') . '
		WHERE
		fs.target_id = :userid
		AND fs.active AND u.enabled
		' . ($filtername ? '
			AND (
			country.title LIKE :filtername OR
			city.title LIKE :filtername OR
			u.username LIKE :filtername OR
			u.email LIKE :filtername OR
			u.firstname LIKE :filtername OR
			u.lastname LIKE :filtername
		)' : '') . '

    	' .
                        (($limit !== null) ? ' LIMIT :limit ' : '') .
                        (($offset !== null) ? ' OFFSET :offset ' : '')
                        , $rsm)
                ->setParameter('userid', $user->getId(), Type::BIGINT);

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);
        if ($offset !== null)
            $query = $query->setParameter('offset', (int) $offset, Type::INTEGER);

        return $query->getResult();
    }

    /**
     * Search
     *
     * term to search for:
     * @param string $text
     *
     * current logged in user, or null:
     * @param User|null $user
     *
     * @param int|null $limit
     * @param int|null $offset
     */
    public function search($text = null, $user = null, $limit = null, $offset = null)
    {
        return $this->SearchFront($user, $text, null, $limit, $offset);
    }

    /**
     * Count Search
     *
     * term to search for:
     * @param string $text
     *
     * current logged in user, or null:
     * @param User|null $user
     */
    public function countSearch($text = null, $user = null, $limit = null, $offset = null)
    {
        return $this->CountSearchFront($user, $text, null, $limit, $offset);
    }

	/**
     * Get fans of the user
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     * @param int|null $limit
     * @param int|null $offset
     */
    public function fans(User $user, $direction = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT u
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	ORDER BY u.lastname ASC, u.firstname ASC
    	')
                ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get fans of the user that share his location
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     * @param int|null $limit
     * @param int|null $offset
     */
    public function fansNearby(User $user, $direction = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT u
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	((u.city = :usercity) OR (u.country = :usercountry))
    	AND
    	'.$this->getDirectionCondition($direction).'

    	ORDER BY u.lastname ASC, u.firstname ASC
    	')
                ->setParameter('user', $user->getId())
                ->setParameter('usercountry', $user->getCountry() ? $user->getCountry()->getId() : null)
                ->setParameter('usercity', $user->getCity() ? $user->getCity()->getId() : null)
        ;

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get fans of the user that share his choice(s) of favorite team
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     * @param int|null $limit
     * @param int|null $offset
     */
    public function fansSameFavoriteTeam(User $user, $direction = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT u, uts
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	JOIN u.teamships uts WITH uts.favorite = true
    	AND uts.team IN
    	(
    		SELECT utx.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship utsx JOIN utsx.team utx
    		WHERE utsx.author = :user AND utsx.favorite = true
    	)
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	ORDER BY u.lastname ASC, u.firstname ASC
    	')
                ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get fans of the user that share the most teamships and idolships
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     * @param int|null $limit
     * @param int|null $offset
     */
    public function fansMostSimilar(User $user, $direction = null, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT u, COUNT(u) as common, COUNT(uts) as commonteams, COUNT(uis) as commonidols
    	FROM \Application\Sonata\UserBundle\Entity\User u

    	LEFT JOIN u.teamships uts
    	WITH uts.team IN
    	(
    		SELECT utx.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship utsx JOIN utsx.team utx
    		WHERE utsx.author = :user
    	)

    	LEFT JOIN u.idolships uis
    	WITH uis.idol IN
    	(
    		SELECT uix.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship uisx JOIN uisx.idol uix
    		WHERE uisx.author = :user
    	)

    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	GROUP BY u.id
    	HAVING (commonteams > 0 OR commonidols > 0)

    	ORDER BY common DESC
    	')
                ->setParameter('user', $user->getId())
        ;

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        $res = $query->getResult();
        $users = array();
        foreach ($res as $r) $users[] = $r[0];
        return $users;
    }

	/**
     * Get count of fans of the user
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     */
    public function countFans(User $user, $direction = null)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(u)
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	')
                ->setParameter('user', $user->getId())
        ;

        return (int)$query->getSingleScalarResult();
    }

    /**
     * Get count of fans of the user that share his location
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     */
    public function countFansNearby(User $user, $direction = null)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(u)
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	((u.city = :usercity) OR (u.country = :usercountry))
    	AND
    	'.$this->getDirectionCondition($direction).'

    	')
                ->setParameter('user', $user->getId())
                ->setParameter('usercountry', $user->getCountry() ? $user->getCountry()->getId() : null)
                ->setParameter('usercity', $user->getCity() ? $user->getCity()->getId() : null)
        ;

        return (int)$query->getSingleScalarResult();
    }

    /**
     * Get count of fans of the user that share his choice(s) of favorite team
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     */
    public function countFansSameFavoriteTeam(User $user, $direction = null)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(u)
    	FROM \Application\Sonata\UserBundle\Entity\User u
    	JOIN u.teamships uts WITH uts.favorite = true
    	AND uts.team IN
    	(
    		SELECT utx.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship utsx JOIN utsx.team utx
    		WHERE utsx.author = :user AND utsx.favorite = true
    	)
    	WHERE u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	')
                ->setParameter('user', $user->getId())
        ;

        return (int)$query->getSingleScalarResult();
    }

    /**
     * Get count of fans of the user that share teamships and idolships
     *
     * @param User $user
     * @param true|false|null $direction - true: user follows them; false: they follow user; null: both ways
     */
    public function countFansMostSimilar(User $user, $direction = null)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(u)
    	FROM \Application\Sonata\UserBundle\Entity\User u

    	WHERE
    	(
        	u IN (
        		SELECT uxi.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship uxits
        		JOIN uxits.author uxi
        		WHERE uxits.team IN
        		(
            		SELECT utx.id FROM \Dodici\Fansworld\WebBundle\Entity\Teamship utsx JOIN utsx.team utx
            		WHERE utsx.author = :user
        		)
        	)

        	OR

        	u IN (
        		SELECT uxib.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship uxiis
        		JOIN uxiis.author uxib
        		WHERE uxiis.idol IN
        		(
            		SELECT uix.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship uisx JOIN uisx.idol uix
            		WHERE uisx.author = :user
        		)
        	)
    	)

    	AND u.enabled = true
    	AND
    	u.id <> :user
    	AND
    	'.$this->getDirectionCondition($direction).'

    	')
                ->setParameter('user', $user->getId())
        ;

        return (int)$query->getSingleScalarResult();
    }

	/**
     * Returns latest video/photo activity filtered by user
     * Please use Userfeed service if possible
     *
     * @param User $user
     * @param array|null $filters - 'fans', 'idols', 'teams' possible elements
     * @param array $resulttypes - 'video', 'photo' possible elements
     * @param int $limit (default 10)
     * @param DateTime|null $maxdate
     * @param DateTime|null $mindate
     * @param array $order
     */
    public function latestActivity(
        User $user,
        $filters = array('fans', 'idols', 'teams'),
        $resulttype = array('video', 'photo'),
        $limit = 10,
        $maxdate = null,
        $mindate = null,
        $order = array(array('created' => 'DESC'))
    )
    {
        $filtertypes = array('fans', 'idols', 'teams');
        $resulttypes = array('video', 'photo');

        if (!is_array($filters)) throw new \InvalidArgumentException('Invalid filter(s)');
        if (!$resulttype || !is_array($resulttype)) throw new \InvalidArgumentException('Invalid resulttype(s)');

        foreach ($filters as $f) if (!in_array($f, $filtertypes))
            throw new \InvalidArgumentException('Invalid filter: ' . $f);

        foreach ($resulttype as $rt) if (!in_array($rt, $resulttypes))
            throw new \InvalidArgumentException('Invalid resulttype: ' . $rt);

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('id', 'id');
        $rsm->addScalarResult('type', 'type');
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('content', 'content');
        $rsm->addScalarResult('likecount', 'likecount');
        $rsm->addScalarResult('weight', 'weight');
        $rsm->addScalarResult('created', 'created');
        $rsm->addScalarResult('highlight', 'highlight');
        $rsm->addScalarResult('imageid', 'imageid');

        $authorkeys = array('authorid', 'authorusername', 'authorfirstname', 'authorlastname', 'authorimageid');

        foreach ($authorkeys as $ak) $rsm->addScalarResult($ak, $ak);

        $sqls = array();
        foreach ($resulttype as $type) {
            $filtersqls = array();

            foreach ($filters as $filter) {
                switch ($filter) {
                    case 'fans':
                        $filtersqls[] = '
                        (
                			(' . $type . '.author_id IN
            	    			(
            	    				SELECT target_id FROM friendship WHERE active=true AND author_id = :user
            	    			)
            	    		)
                    		OR
                    		(' . $type . '.id IN (SELECT ' . $type . '_id FROM hasuser WHERE target_id IN
                    			(SELECT target_id FROM friendship WHERE active=true AND author_id = :user)
                    		))
                    	)
                        ';
                        break;
                    case 'idols':
                        $filtersqls[] = '
                        (' . $type . '.id IN (SELECT ' . $type . '_id FROM hasidol WHERE idol_id IN
                    		(SELECT idol_id FROM idolship WHERE author_id = :user)
                    	))
                        ';
                        break;
                    case 'teams':
                        $filtersqls[] = '
                        (' . $type . '.id IN (SELECT ' . $type . '_id FROM hasteam WHERE team_id IN
                    		(SELECT team_id FROM teamship WHERE author_id = :user)
                    	))
                        ';
                        break;
                }
            }

            $sqls[] = '
                SELECT
                ' . $type . '.id as id,
                "' . $type . '" as type,
                ' . $type . '.title as title,
                ' . $type . '.slug as slug,
                ' . $type . '.content as content,
                COUNT(lks.id) as likecount,
                ' . $type . '.weight as weight,
                ' . $type . '.created_at as created,
                ' . $type . '.image_id as imageid,

                '.(($type == 'video') ? '
                	' . $type . '.highlight as highlight,
                ' : '
                	null as highlight,
                ').'

                author.id as authorid,
                author.username as authorusername,
                author.firstname as authorfirstname,
                author.lastname as authorlastname,
                author.image_id as authorimageid

                FROM ' . $type . '

                LEFT JOIN liking lks ON lks.' . $type . '_id = ' . $type . '.id

                LEFT JOIN fos_user_user author ON author.id = ' . $type . '.author_id

                WHERE
                ' . $type . '.active = true

                '.
                ($mindate ? '
                AND ' . $type . '.created_at >= :mindate
                ' : '')
                .
                ($maxdate ? '
                AND ' . $type . '.created_at < :maxdate
                ' : '')
                .'


                AND
            	(
            		(' . $type . '.author_id IS NULL)
            		OR
            		(' . $type . '.privacy = :everyone)
            		OR
        	    	(' . $type . '.privacy = :friendsonly AND (
        	    		(:user = ' . $type . '.author_id) OR
        	    		(' . $type . '.author_id IN
        	    			(
        	    				SELECT author_id FROM friendship WHERE active=true AND target_id = :user
        	    				UNION
        	    				SELECT target_id FROM friendship WHERE active=true AND author_id = :user
        	    			)
        	    		)
        	    	))
            	)

            	'.
                ($filtersqls ? '
                AND ('.join(' OR ', $filtersqls).')
                ' : '')
                .'

                GROUP BY ' . $type . '.id
                ';
        }

        $ordercriterias = array();
        foreach ($order as $orderitem) {
            foreach ($orderitem as $field => $direction) {
                $ordercriterias[] = $field . ' ' . $direction;
            }
        }

        $query = $this->_em->createNativeQuery(
                join(' UNION ', $sqls) . '
            ORDER BY
            '.join(', ', $ordercriterias).'
            ' .
                (($limit !== null) ? ' LIMIT :limit ' : '')
                , $rsm
        );

        if ($limit !== null)
            $query = $query->setParameter('limit', (int) $limit, Type::INTEGER);

        if ($mindate)
            $query = $query->setParameter('mindate', $mindate);

        if ($maxdate)
            $query = $query->setParameter('maxdate', $maxdate);

        $query = $query->setParameter('user', $user->getId());
        $query = $query->setParameter('everyone', Privacy::EVERYONE);
        $query = $query->setParameter('friendsonly', Privacy::FRIENDS_ONLY);

        $return = array();

        $res = $query->getResult();

        foreach ($res as $r) {
            $ret = array();
            foreach ($r as $k => $v) {
                if (in_array($k, $authorkeys)) $ret['author'][str_replace('author', '', $k)] = $v;
                else $ret[$k] = $v;
            }
            if (!isset($ret['author'])) $ret['author'] = null;
            $return[] = $ret;
        }
        return $return;
    }

    private function getDirectionCondition($direction)
    {
        $conditions = array();
        if ($direction || $direction === null) {
            $conditions[] = '
            u.id IN (SELECT fsua.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fsa JOIN fsa.target fsua WHERE fsa.author = :user)
            ';
        }
        if (!$direction || $direction === null) {
            $conditions[] = '
            u.id IN (SELECT fsub.id FROM \Dodici\Fansworld\WebBundle\Entity\Friendship fsb JOIN fsb.author fsub WHERE fsb.target = :user)
            ';
        }

        $condition = '(' . join(' OR ', $conditions) . ')';

        return $condition;
    }

    /**
     * Return the next user
     * @param User $user
     */
    public function next($user)
    {
        $query = $this->_em->createQuery('
            SELECT u
            FROM Application\Sonata\UserBundle\Entity\User u
            WHERE u.id > :userId
            ORDER BY u.id DESC
        ')
            ->setParameter('userId', $user->getId())
            ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * Return previous user
     * @param User $user
     */
    public function previous($user)
    {
        $query = $this->_em->createQuery('
            SELECT u
            FROM Application\Sonata\UserBundle\Entity\User u
            WHERE u.id < :userId
            ORDER BY u.id DESC
        ')
            ->setParameter('userId', $user->getId())
            ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }
}