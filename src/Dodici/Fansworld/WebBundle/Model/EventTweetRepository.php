<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Team;
use Doctrine\ORM\EntityRepository;

/**
 * EventTweetRepository
 *
 */
class EventTweetRepository extends CountBaseRepository
{
	/**
     * Get max external by team
     * 
     * @param Team $team
     */
    public function maxExternal(Team $team)
    {
        return $this->_em->createQuery('
        	SELECT MAX(et.external)
        	FROM \Dodici\Fansworld\WebBundle\Entity\EventTweet et
            WHERE et.team = :team
            ')
            ->setParameter('team', $team->getId())
            ->getSingleScalarResult();
    }
}