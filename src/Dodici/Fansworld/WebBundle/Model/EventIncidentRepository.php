<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Event;
use Doctrine\ORM\EntityRepository;

/**
 * EventIncidentRepository
 */
class EventIncidentRepository extends CountBaseRepository
{
	/**
	 * Get incidents for event wall
	 * @param Event|int $event
	 * @param DateTime|null $maxdate
	 * @param DateTime|null $mindate
	 */
	public function eventWall($event, $maxdate=null, $mindate=null)
	{
		$dql = '
		SELECT ei, t
    	FROM \Dodici\Fansworld\WebBundle\Entity\EventTweet ei
    	LEFT JOIN ei.team t
		WHERE ei.event = :event
		';
	    
		if ($maxdate) $dql .= ' AND ei.createdAt < :maxdate ';
		if ($mindate) $dql .= ' AND ei.createdAt >= :mindate ';
		
		$dql .= ' ORDER BY ei.createdAt DESC';
		
	    $query = $this->_em->createQuery($dql)
    		->setParameter('event', ($event instanceof Event) ? $event->getId() : $event);
    		
    	if ($maxdate) $query = $query->setParameter('maxdate', $maxdate);
    	if ($mindate) $query = $query->setParameter('mindate', $mindate);
    	
    	return $query->getResult();
	}
}