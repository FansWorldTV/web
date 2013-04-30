<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Idol;
use Doctrine\ORM\EntityRepository;

/**
 * IdolshipRepository
 */
class IdolshipRepository extends CountBaseRepository
{

    /**
     * Gets the ranking of users for an idol
     * @param Idol $idol
     * @param int $limit
     * @param int $offset
     */
    public function rankedUsersScore(Idol $idol, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT iship, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\Idolship iship
    	JOIN iship.author u
    	WHERE
    	u.enabled = true AND iship.idol = :idol
    	ORDER BY iship.score DESC, u.score DESC
    	')
                ->setParameter('idol', $idol->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get idols where the user had top activity, and his ranking compared to other users
     * @param User $user
     * @param int $limit
     */
    public function userTopRankedIn(User $user, $limit = null)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('Dodici\Fansworld\WebBundle\Entity\Idol', 'idl');
        $rsm->addScalarResult('total', 'score');
        $rsm->addScalarResult('rank', 'rank');
        $rsm->addScalarResult('maxpos', 'maxpos');

        $query = $this->_em->createNativeQuery('
            SELECT
            	idl.*,
            	idsh1.idol_id,
            	idsh1.score AS total,
            	COUNT(idsh2.id)+1 AS rank,
            	(SELECT COUNT(*) FROM idolship WHERE idol_id = idsh1.idol_id) AS maxpos
            FROM idolship idsh1
            INNER JOIN idol idl ON idl.id = idsh1.idol_id
            LEFT JOIN idolship idsh2 ON
            	((idsh1.score < idsh2.score) OR (idsh1.score = idsh2.score AND (
            		(SELECT score from fos_user_user fsx1 where fsx1.id = idsh1.author_id)
            		<
            		(SELECT score from fos_user_user fsx2 where fsx2.id = idsh2.author_id)
            	)))
            	AND idsh1.idol_id = idsh2.idol_id
            WHERE idsh1.author_id = :user
            GROUP BY idsh1.idol_id
            ORDER BY total DESC, rank ASC

            '.
            (($limit !== null) ? 'LIMIT :limit' : '')
	    , $rsm)
                ->setParameter('user', $user->getId());

        if ($limit !== null)
            $query = $query->setParameter('limit', $limit);


        $result = $query->getResult();
        foreach ($result as $k => $v) {
            $v['idol'] = $v[0];
            unset($v[0]);
            $result[$k] = $v;
        }

        return $result;
    }

     /**
     * Get idolship->idols the user is a fan of
     * @param User $user
     * @param int $limit
     * @param int $offset
     */
    public function byUser(User $user, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
        SELECT iss, i
        FROM \Dodici\Fansworld\WebBundle\Entity\Idolship iss
        JOIN iss.idol i
        WHERE
        i.active = true AND iss.author = :user
        ORDER BY iss.score DESC
        ')
            ->setParameter('user', $user->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

}