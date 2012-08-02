<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * ProposalRepository
 */
class ProposalRepository extends CountBaseRepository
{
	/**
	 * Get the latest popular proposals
	 * @param User $user
	 * @param int $limit
	 * @param int $offset
	 */
    public function popular(User $user=null, $limit=null, $offset=null)
	{
		$query = $this->_em->createQuery('
    	SELECT pr, pra
    	FROM \Dodici\Fansworld\WebBundle\Entity\Proposal pr
    	JOIN pr.author pra
    	WHERE
    	pr.active = true
    	ORDER BY 
    	
    	'.
			($user ?
			'
				(pr.author = :userid) DESC, 
			'
			: ''
			)
		.'
    	
    	pr.weight DESC
    	');
		
		if ($user) $query = $query->setParameter('userid', $user->getId());
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
	
	/**
	 * Count active proposals
	 */
	public function popularCount()
	{
		$query = $this->_em->createQuery('
    	SELECT COUNT(pr)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Proposal pr
    	WHERE
    	pr.active = true
    	');
			
    	return (int)$query->getSingleScalarResult();
	}
}