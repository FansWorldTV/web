<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Team;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Entity\User;

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

}