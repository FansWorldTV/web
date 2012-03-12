<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

class ContestRepository extends CountBaseRepository
{

    /**
     * Get all active contests still before end date or recently past (2 weeks)
     * 
     */
    public function activeOpenAndRecentlyWon()
    {
        $intwoweeks = new \DateTime('-2 weeks');
        return $this->_em->createQuery('
    	SELECT c
    	FROM \Dodici\Fansworld\WebBundle\Entity\Contest c
    	WHERE c.active = true
    	AND
    	(c.endDate IS NULL OR (c.endDate > :intwoweeks))
    	ORDER BY c.createdAt DESC')
                        ->setParameter('intwoweeks', $intwoweeks->format('Y-m-d'))
                        ->getResult();
    }

    /**
     * Search by field
     * @param string $query 
     */
    public function searchBy($query)
    {
        return $this->_em->createQuery('
                SELECT c
                FROM \Dodici\Fansworld\WebBundle\Entity\Contest c
                WHERE c.active = true AND (c.title LIKE :query OR c.content LIKE :query)
            ')
                        ->setParameter('query', '%' . $query . '%')
                        ->getResult();
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
                ORDER BY c.createdAt DESC
            ')
                        ->setParameter('user', $user->getId())
                        ->getResult();
    }
}