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
    	SELECT e, ht, t, ti
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	JOIN e.hasteams ht
    	JOIN ht.team t
    	LEFT JOIN t.image ti
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
    	SELECT e, ht, t, ti
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	JOIN e.hasteams ht
    	JOIN ht.team t
    	LEFT JOIN t.image ti
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
	
	/**
	 * Get events for calendar according to criteria/sort 
	 * @param User|null $user
	 * @param boolean|null $finished - only finished/unfinished events
	 * @param boolean|null $checkedin - ($user required, whether to select checked in events only, or non-checked-in)
	 * @param DateTime|null $datefrom
	 * @param DateTime|null $dateto
	 * @param Sport|null $sport
	 * @param TeamCategory|null $teamcategory
	 * @param array|null $sort - see below
	 * @param int|null $limit
	 * @param int|null $offset
	 * @throws \Exception
	 * 
	 * sort types (can use an array as parameter, e.g. array('isfan', 'popular')):
	 * isfan - show the events that involve a team of which the user is fan first
	 * popular - show most popular (and upcoming) events first
	 * upcoming - show most impending events first, regardless of popularity (note that mixing popular and upcoming makes no sense)
	 */
	public function calendar(User $user=null, $finished=null, $checkedin=null, $datefrom=null, $dateto=null,
	    $sport=null, $teamcategory=null, $sort=null, $limit=null, $offset=null)
	{
	    if ($sort && !is_array($sort)) $sort = array($sort);
	    if (!$sort) {
	        $sort = array();
	        if ($user) $sort[] = 'isfan';
	        $sort[] = 'popular';
	    }
	    
	    $orders = array(
	        'isfan' => 'isfan DESC',
	        'popular' => (($finished === true) ? 'e.weight DESC' : 'e.weight ASC'),
	        'upcoming' => 'e.fromtime ASC'
	    );
	    
	    if (in_array('isfan', $sort) && !$user) throw new \Exception('Need a user to sort by fandom');
	    
	    $dql = 
	    'SELECT e, ht, t, ti '. 
	    (($user && in_array('isfan', $sort)) ? ', COUNT(tts) isfan' : '') .'
    	FROM \Dodici\Fansworld\WebBundle\Entity\Event e
    	LEFT JOIN e.hasteams ht
    	LEFT JOIN ht.team t
    	LEFT JOIN t.image ti
    	'
    	.
    	($sport ? ' JOIN e.teamcategory tc JOIN tc.sport sp WITH sp = :sport ' : '')
    	.
    	(($user && in_array('isfan', $sort)) ? 'LEFT JOIN t.teamships tts WITH tts.author = :user' : '')
    	.
    	'
    	WHERE
    	e.active = true';
	    
	    if ($datefrom) $dql .= ' AND e.fromtime >= :datefrom ';
	    if ($dateto) $dql .= ' AND e.fromtime <= :dateto ';
	    if ($teamcategory) $dql .= ' AND e.teamcategory = :teamcategory ';
	    
	    if ($user && ($checkedin !== null)) $dql .= ' AND e.id ' . ($checkedin ?: 'NOT') . ' IN (SELECT esx.event FROM \Dodici\Fansworld\WebBundle\Entity\Eventship esx WHERE esx.author = :user) ';
	    if ($finished !== null) $dql .= ' AND e.finished = :finished ';
	    
	    $dql .= ' GROUP BY e, ht, t ORDER BY ';
	    
	    $ordersdql = array();
	    foreach ($sort as $s) $ordersdql[] = $orders[$s];
	    
	    $dql .= join(', ', $ordersdql);
	    //var_dump($dql);
	    $query = $this->_em->createQuery($dql);

	    if ($user !== null) $query = $query->setParameter('user', $user->getId());
	    if ($finished !== null) $query = $query->setParameter('finished', $finished);
	    if ($datefrom !== null) $query = $query->setParameter('datefrom', $datefrom);
	    if ($dateto !== null) $query = $query->setParameter('dateto', $dateto);
	    if ($sport !== null) $query = $query->setParameter('sport', $sport->getId());
	    if ($teamcategory !== null) $query = $query->setParameter('teamcategory', $teamcategory->getId());
	    
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	if ($user && in_array('isfan', $sort)) {
        	$results = array();
        	$qr = $query->getResult();
        	foreach ($qr as $r) $results[] = $r[0];
        	return $results;
    	} else {
    	    return $query->getResult();
    	}
	}
}