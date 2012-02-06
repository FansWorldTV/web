<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

class ContestParticipantRepository extends CountBaseRepository
{
	/**
     * Get all the winning users from a contest
     * 
     * @param \Dodici\WebBundle\Entity\Site $site
     */
	public function winnerUsers(\Dodici\WebBundle\Entity\Contest $contest)
    {
        $users = array();
    	$result = $this->_em->createQuery('
    	SELECT cp, u
    	FROM \Dodici\WebBundle\Entity\ContestParticipant cp
    	JOIN cp.author u
    	WHERE cp.winner = true
    	AND cp.contest = :contestid')
    		->setParameter('contestid', $contest->getId())
    		->getResult();
    		
    	foreach ($result as $r) {
    		$users[] = $r->getAuthor();
    	}
    	
    	return $users;
    }
}