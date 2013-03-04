<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Application\Sonata\UserBundle\Entity\User;
use Dodici\Fansworld\WebBundle\Entity\Team;
use Doctrine\ORM\EntityRepository;

/**
 * TeamshipRepository
 */
class TeamshipRepository extends CountBaseRepository
{

    /**
     * Get teamship->teams the user is a fan of
     * @param User $user
     * @param int $limit
     * @param int $offset
     */
    public function byUser(User $user, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT ts, t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Teamship ts
    	JOIN ts.team t
    	WHERE
    	t.active = true AND ts.author = :user
    	ORDER BY t.fanCount DESC, ts.score DESC
    	')
                ->setParameter('user', $user->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }

    /**
     * Get ranking of users for a team
     * @param Team $team
     * @param int $limit
     * @param int $offset
     */
    public function rankedUsersScore(Team $team, $limit = null, $offset = null)
    {
        $query = $this->_em->createQuery('
    	SELECT ts, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\Teamship ts
    	JOIN ts.author u
    	WHERE
    	u.enabled = true AND ts.team = :team
    	ORDER BY ts.score DESC, u.score DESC
    	')
                ->setParameter('team', $team->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);
        if ($offset !== null)
            $query = $query->setFirstResult($offset);

        return $query->getResult();
    }
    
    /**
     * Get teams where the user had top activity, and his ranking compared to other users
     * @param User $user
     * @param int $limit
     */
    public function userTopRankedIn(User $user, $limit = null)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata('Dodici\Fansworld\WebBundle\Entity\Team', 'tm');
        $rsm->addScalarResult('total', 'score');
        $rsm->addScalarResult('rank', 'rank');
        $rsm->addScalarResult('maxpos', 'maxpos');

        $query = $this->_em->createNativeQuery('
            SELECT 
            	tm.*,
            	tmsh1.team_id, 
            	tmsh1.score AS total, 
            	COUNT(tmsh2.id)+1 AS rank, 
            	(SELECT COUNT(*) FROM teamship WHERE team_id = tmsh1.team_id) AS maxpos
            FROM teamship tmsh1
            INNER JOIN team tm ON tm.id = tmsh1.team_id
            LEFT JOIN teamship tmsh2 ON 
            	((tmsh1.score < tmsh2.score) OR (tmsh1.score = tmsh2.score AND (
            		(SELECT score from fos_user_user fsx1 where fsx1.id = tmsh1.author_id)
            		<
            		(SELECT score from fos_user_user fsx2 where fsx2.id = tmsh2.author_id)
            	)))
            	AND tmsh1.team_id = tmsh2.team_id
            WHERE tmsh1.author_id = :user
            GROUP BY tmsh1.team_id
            ORDER BY total DESC, rank ASC
            
            '. 
            (($limit !== null) ? 'LIMIT :limit' : '')
	    , $rsm)
                ->setParameter('user', $user->getId());
                
        if ($limit !== null)
            $query = $query->setParameter('limit', $limit);
        
        
        $result = $query->getResult();
        foreach ($result as $k => $v) {
            $v['team'] = $v[0];
            unset($v[0]);
            $result[$k] = $v;
        }

        return $result;
    }

}