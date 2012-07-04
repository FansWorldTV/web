<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Idol;

use Application\Sonata\UserBundle\Entity\User;

use Dodici\Fansworld\WebBundle\Entity\Team;

use Doctrine\ORM\EntityRepository;

/**
 * EventRepository
 */
class EventRepository extends CountBaseRepository
{
	/**
	 * Get events where the team was tagged
	 * @param Team $team
	 * @param int $limit
	 * @param int $offset
	 */
    public function byTeam(Team $team, $limit=null, $offset=null)
	{
		$query = $this->_em->createQuery('
    	SELECT e, ht
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	JOIN e.hasteams ht
    	WHERE
    	e.active = true AND ht.team = :team
    	ORDER BY e.userCount DESC, e.fromtime ASC
    	')
    		->setParameter('team', $team->getId());
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
	
	/**
	 * Get events where the idol was tagged
	 * @param Idol $idol
	 * @param int $limit
	 * @param int $offset
	 */
	public function byIdol(Idol $idol, $limit=null, $offset=null)
	{
		$query = $this->_em->createQuery('
    	SELECT e, hu
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	JOIN e.hasidols hu
    	WHERE
    	e.active = true AND hu.idol = :idol
    	ORDER BY e.userCount DESC, e.fromtime ASC
    	')
    		->setParameter('idol', $idol->getId());
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
	
}