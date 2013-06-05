<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\TeamCategory;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * IdolRepository
 */
class IdolRepository extends CountBaseRepository
{

    /**
     * Search for idols with optional search term and pagination
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isidol (null|true|false)
     * @param string $filtername
     * @param int $limit
     * @param int $offset
     */
    public function SearchFront(\Application\Sonata\UserBundle\Entity\User $user = null, $filtername = null, $isidol = null, $limit = null, $offset = null)
    {
        $terms = array();
        $xp = explode(' ', $filtername);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);

        $querystring = '
    	SELECT i
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	WHERE
    	i.active = true
    	';

        if ($terms) {

            foreach ($terms as $k => $t) {
                $querystring .=
                        '
            	AND
            	(
            		(i.nicknames LIKE :term'.$k.')
            		OR
            		(i.content LIKE :term'.$k.')
            		OR
            		(i.firstname LIKE :term'.$k.')
            		OR
            		(i.lastname LIKE :term'.$k.')
            	)
            	';

            }
        }

        if ($isidol !== null)
            $querystring .=
                    '
    	AND
    	(
    	:userid IS NULL OR
    	((SELECT COUNT(iss.id) FROM \Dodici\Fansworld\WebBundle\Entity\Idolship iss WHERE (iss.author = :userid AND iss.idol = i.id))
    	' . (($isidol === true) ? '>= 1' : ' = 0') . '
    	)
    	)
    	';



        $querystring .= ' ORDER BY i.fanCount DESC';

        $query = $this->_em->createQuery($querystring);

        if ($isidol !== null)
            $query = $query->setParameter('userid', $user ? $user->getId() : null);

        if ($terms) {
            foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');
        }

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Count searched idols with optional search term
     * @param \Application\Sonata\UserBundle\Entity\User $user
     * @param boolean $isfriend (null|true|false)
     * @param string $filtername
     */
    public function CountSearchFront(\Application\Sonata\UserBundle\Entity\User $user = null, $filtername = null, $isidol = null, $limit = null, $offset = null)
    {
        $terms = array();
        $xp = explode(' ', $filtername);
        foreach ($xp as $x) if (trim($x)) $terms[] = trim($x);

        $querystring = '
    	SELECT COUNT(i)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	WHERE
    	i.active = true
    	';

        if ($terms) {

            foreach ($terms as $k => $t) {
                $querystring .=
                        '
            	AND
            	(
            		(i.nicknames LIKE :term'.$k.')
            		OR
            		(i.content LIKE :term'.$k.')
            		OR
            		(i.firstname LIKE :term'.$k.')
            		OR
            		(i.lastname LIKE :term'.$k.')
            	)
            	';

            }
        }

        if ($isidol !== null)
            $querystring .=
                    '
    	AND
    	(
    	:userid IS NULL OR
    	((SELECT COUNT(iss.id) FROM \Dodici\Fansworld\WebBundle\Entity\Idolship iss WHERE (iss.author = :userid AND iss.idol = i.id))
    	' . (($isidol === true) ? '>= 1' : ' = 0') . '
    	)
    	)
    	';

        $query = $this->_em->createQuery($querystring);

        if ($isidol !== null)
            $query = $query->setParameter('userid', $user ? $user->getId() : null);

        if ($terms) {
            foreach ($terms as $k => $t) $query = $query->setParameter('term'.$k, '%' . $t . '%');
        }

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getSingleScalarResult();
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
     * Returns the Idols that have the team as a current career
     * @param Team $team
     */
    public function byTeam(Team $team, $limit = null)
    {
        $query = $this->_em->createQuery('
    	SELECT i
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	INNER JOIN i.idolcareers ic
    	WHERE i.active = true
    	AND ic.active = true
    	AND ic.actual = true
    	AND ic.team = :team
    	')
            ->setParameter('team', $team->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        return $query->getResult();
    }

	/**
     * Returns the Idols that have a team belonging to a $teamcategory as a current career
     * @param TeamCategory $teamcategory
     */
    public function byTeamCategory(TeamCategory $teamcategory, $limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
    	SELECT i
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	INNER JOIN i.idolcareers ic
    	INNER JOIN ic.team t
    	INNER JOIN t.teamcategories tc WITH tc = :teamcategory
    	WHERE i.active = true
    	AND ic.active = true
    	AND ic.actual = true
    	')
            ->setParameter('teamcategory', $teamcategory->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Count the Idols that have a team belonging to a $teamcategory as a current career
     * @param TeamCategory $teamcategory
     */
    public function countByTeamCategory(TeamCategory $teamcategory)
    {
        $query = $this->_em->createQuery('
    	SELECT COUNT(i)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	INNER JOIN i.idolcareers ic
    	INNER JOIN ic.team t
    	INNER JOIN t.teamcategories tc WITH tc = :teamcategory
    	WHERE i.active = true
    	AND ic.active = true
    	AND ic.actual = true
    	')
            ->setParameter('teamcategory', $teamcategory->getId());

        return $query->getSingleScalarResult();
    }


    /**
     * Return the teams related to Idol
     * @param Idol $idol
     */
    public function relatedTeams($idol)
    {
        return null;
    }

    /**
     * Return common idols
     * @param Idol $idol
     */
    public function commonIdols($idol)
    {
        return null;
    }

    /**
     * Return the next idol
     * @param Idol $idol
     */
    public function next($idol)
    {
        $query = $this->_em->createQuery('
            SELECT i
            FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
            WHERE i.id > :idolId
            ORDER BY i.id DESC
        ')
        ->setParameter('idolId', $idol->getId())
        ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * Return previous idol
     * @param Idol $idol
     */
    public function previous($idol)
    {
        $query = $this->_em->createQuery('
            SELECT i
            FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
            WHERE i.id < :idolId
            ORDER BY i.id DESC
        ')
            ->setParameter('idolId', $idol->getId())
            ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }

    /**
     * Returns the Idols related to $genre(if genre given) order by popularity
     * @param Genre entity or Genre_id (Int)  $genre
     * @param int|null $limit
     * @param int|null $offset
     */
    public function byGenre($genre=null, $limit=null, $offset=null)
    {
        $query = $this->_em->createQuery('
            SELECT i
            FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
            LEFT JOIN i.genre igen
            WHERE i.active = true
            AND
            ((:genre IS NULL OR (igen = :genre OR igen.parent = :genre)))
            ORDER BY i.fanCount DESC
        ')
            ->setParameter('genre', ($genre instanceof Genre) ? $genre->getId() : $genre);

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }
}