<?php

namespace Dodici\Fansworld\WebBundle\Model;

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
        $querystring = '
    	SELECT i, it
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	JOIN i.team it
    	WHERE
    	i.active = true
    	';

        if ($filtername)
            $querystring .=
                    '
    	AND
    	(
    		(i.firstname LIKE :filtername)
    		OR
    		(i.lastname LIKE :filtername)
    		OR
    		(i.nicknames LIKE :filtername)
    		OR
    		(i.content LIKE :filtername)
    		OR
    		(it.title LIKE :filtername)
    	)
    	';

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

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

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
    public function CountSearchFront(\Application\Sonata\UserBundle\Entity\User $user = null, $filtername = null, $isidol = null)
    {
        $querystring = '
    	SELECT COUNT(i)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idol i
    	JOIN i.team it
    	WHERE
    	i.active = true
    	';

        if ($filtername)
            $querystring .=
                    '
    	AND
    	(
    		(i.firstname LIKE :filtername)
    		OR
    		(i.lastname LIKE :filtername)
    		OR
    		(i.nicknames LIKE :filtername)
    		OR
    		(i.content LIKE :filtername)
    		OR
    		(it.title LIKE :filtername)
    	)
    	';

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

        if ($filtername)
            $query = $query->setParameter('filtername', '%' . $filtername . '%');

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
    public function search($text, User $user = null, $limit = null, $offset = null)
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
    public function countSearch($text, User $user = null)
    {
        return $this->CountSearchFront($user, $text, null);
    }

}