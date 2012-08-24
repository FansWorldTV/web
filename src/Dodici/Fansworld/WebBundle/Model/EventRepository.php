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
    	e.active = true
    	AND
    	(e.id IN (SELECT ex.id FROM \Dodici\Fansworld\WebBundle\Entity\Event ex JOIN ex.hasteams htx WITH htx.team = :team))
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
    	SELECT e, ht, t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	JOIN e.hasteams ht
    	JOIN ht.team t
    	WHERE
    	e.active = true
    	AND
    	(e.id IN (SELECT ex.id FROM \Dodici\Fansworld\WebBundle\Entity\Event ex JOIN ex.hasteams htx JOIN htx.team tx JOIN tx.idolcareers icx WITH icx.active = true AND icx.idol = :idol ))
    	ORDER BY e.userCount DESC, e.fromtime ASC
    	')
    		->setParameter('idol', $idol->getId());
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
	
}