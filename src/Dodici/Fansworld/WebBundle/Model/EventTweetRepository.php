<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Event;
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
    
	/**
	 * Get tweets for event wall
	 * @param Event|int $event
	 * @param DateTime|null $maxdate
	 * @param DateTime|null $mindate
	 */
	public function eventWall($event, $maxdate=null, $mindate=null)
	{
		$dql = '
		SELECT et, t
    	FROM \Dodici\Fansworld\WebBundle\Entity\EventTweet et
    	LEFT JOIN et.team t
		WHERE et.event = :event
		';
	    
		if ($maxdate) $dql .= ' AND et.createdAt < :maxdate ';
		if ($mindate) $dql .= ' AND et.createdAt >= :mindate ';
		
		$dql .= ' ORDER BY et.createdAt DESC';
		
	    $query = $this->_em->createQuery($dql)
    		->setParameter('event', ($event instanceof Event) ? $event->getId() : $event);
    		
    	if ($maxdate) $query = $query->setParameter('maxdate', $maxdate);
    	if ($mindate) $query = $query->setParameter('mindate', $mindate);
    	
    	return $query->getResult();
	}
}