<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * TeamRepository
 */
class TeamRepository extends CountBaseRepository
{
    /**
     * Get matching, also returns idolcareers -> idol if $user is provided:
     * idols the user is a fan of, having an actual career in the team
     * 
     * @param TeamCategory|null $category
     * @param string|null $text
     * @param User|null $user (joins idols)
     * @param int|null $limit
     * @param int|null $offset
     */
    public function matching($category = null, $text = null, $user = null, $limit = null, $offset = null)
    {
        /* FIXME: does not return all teamcategories properly when filtering by one */
        
        $dql = '
    	SELECT t, tc, ti, ts'. ($user ? ', ic, i' : '') . '
    	FROM \Dodici\Fansworld\WebBundle\Entity\Team t
        LEFT JOIN t.teamcategories tc
        LEFT JOIN t.image ti
        LEFT JOIN t.splash ts
        '.
        ($user ? ('
        LEFT JOIN t.idolcareers ic WITH ic.actual = true AND ic.idol IN (SELECT ix.id FROM \Dodici\Fansworld\WebBundle\Entity\Idolship isx JOIN isx.idol ix WHERE isx.author = :user)
        LEFT JOIN ic.idol i
        ') : '')
        .'
        WHERE t.active = true
        ';
        
        if ($text)
            $dql .= '
            AND t.title LIKE :textlike
            ';
            
        $dql .= ' GROUP BY t, tc ';
        
        $havings = array();
        
        if ($category) $havings[] = ' tc = :category ';
        
        if ($havings) $dql .= ' HAVING ' . join(' AND ', $havings);
        
        $dql .= ' ORDER BY t.fanCount DESC ';
        
        $query = $this->_em->createQuery($dql);
        
        if ($category)
            $query = $query->setParameter('category', $category);
                
        if ($text)
            $query = $query->setParameter('textlike', '%' . $text . '%');
            
        if ($user)
            $query = $query->setParameter('user', $user->getId());

        if ($limit !== null)
            $query = $query->setMaxResults($limit);

        if ($offset !== null)
            $query = $query->setFirstResult($offset);
            
        return $query->getResult();
    }

	/**
     * Get matching count
     * 
     * @param TeamCategory|null $category
     * @param string|null $text
     */
    public function countMatching($category = null, $text = null)
    {
        $dql = '
    	SELECT COUNT(t)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Team t
        '.
        ($category ? '
        	JOIN t.teamcategories tc WITH tc = :category
        ' : '')
        .'
        WHERE t.active = true
        ';
        
        if ($text)
            $dql .= '
            AND t.title LIKE :textlike
            ';
           
        
        $query = $this->_em->createQuery($dql);
        
        if ($category)
            $query = $query->setParameter('category', $category);
                
        if ($text)
            $query = $query->setParameter('textlike', '%' . $text . '%');

        return $query->getSingleScalarResult();
    }
    
	/**
     * Get teams that have active, non-finished events going, and have a twitter set
     * 
     * @param int|null $daysadvance - amount of days before the event since we're open for tweets
     */
    public function withEvents($daysadvance = 2)
    {
        if ($daysadvance)
            $daysbefore = new \DateTime('+'.$daysadvance.' days');
        
        $dql = '
    	SELECT ht, t, e
    	FROM \Dodici\Fansworld\WebBundle\Entity\HasTeam ht
        JOIN ht.team t
        JOIN ht.event e
        WHERE e.active = true AND e.finished = false AND t.active = true AND (t.twitter IS NOT NULL)
        '. ($daysadvance ? 'AND e.fromtime < :daysbefore' : '') .'
        ORDER BY e.fromtime DESC
        ';
        
        $query = $this->_em->createQuery($dql);
        if ($daysadvance) $query = $query->setParameter('daysbefore', $daysbefore);
            
        $result = $query->getResult();
        
        $teams = array();
        foreach ($result as $r) {
            $teams[$r->getTeam()->getId()] = array('team' => $r->getTeam(), 'event' => $r->getEvent());
        }
        
        return $teams;
    }
}