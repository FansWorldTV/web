<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * EventshipRepository
 */
class EventshipRepository extends CountBaseRepository
{
	/**
	 * Get eventships, events, teams, belonging to the user
	 * @param User $user
	 * @param int $limit
	 * @param int $offset
	 */
    public function byUser(User $user, $limit=null, $offset=null)
	{
		$query = $this->_em->createQuery('
    	SELECT es, e, ht, t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Eventship es
    	JOIN es.event e
    	JOIN e.hasteams ht
    	JOIN ht.team t
    	WHERE
    	e.active = true AND es.author = :user
    	ORDER BY e.fromtime ASC
    	')
    		->setParameter('user', $user->getId());
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
}