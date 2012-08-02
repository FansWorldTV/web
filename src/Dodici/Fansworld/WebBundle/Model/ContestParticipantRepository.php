<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * ContestParticipantRepository
 */
class ContestParticipantRepository extends CountBaseRepository
{
	/**
     * Get all the winning users from a contest
     * 
     * @param \Dodici\Fansworld\WebBundle\Entity\Site $site
     */
	public function winnerUsers(\Dodici\Fansworld\WebBundle\Entity\Contest $contest)
    {
        $users = array();
    	$result = $this->_em->createQuery('
    	SELECT cp, u
    	FROM \Dodici\Fansworld\WebBundle\Entity\ContestParticipant cp
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
    
	/**
     * Get contests in which the user is or has participated
     */
	public function userParticipating(\Application\Sonata\Userbundle\Entity\User $user)
    {
        return $this->_em->createQuery('
                SELECT cp, c
                FROM \Dodici\Fansworld\WebBundle\Entity\ContestParticipant cp
                JOIN cp.contest c
                WHERE c.active = true AND
                cp.author = :user
                ORDER BY cp.createdAt DESC
            ')
                        ->setParameter('user', $user->getId())
                        ->getResult();
    }
}