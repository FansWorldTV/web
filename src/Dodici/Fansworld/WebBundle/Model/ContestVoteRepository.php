<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Contest;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * ContestVoteRepository
 */
class ContestVoteRepository extends CountBaseRepository
{
	/**
     * Get vote by user and contest
     */
    public function byUserAndContest(User $user, Contest $contest)
    {
        return $this->_em->createQuery('
    	SELECT cv, cp
    	FROM \Dodici\Fansworld\WebBundle\Entity\ContestVote cv
    	JOIN cv.contestparticipant cp
    	WHERE cp.contest = :contest
    	AND cv.author = :user')
            ->setParameter('user', $user->getId())            
        	->setParameter('contest', $contest->getId())
            ->getOneOrNullResult();
    }
}