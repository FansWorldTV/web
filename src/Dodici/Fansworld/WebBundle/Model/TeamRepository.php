<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * TeamRepository
 */
class TeamRepository extends CountBaseRepository
{
    /**
     * Get matching
     * 
     * @param TeamCategory|null $category
     * @param string|null $text
     * @param int|null $limit
     * @param int|null $offset
     */
    public function matching($category = null, $text = null, $limit = null, $offset = null)
    {
        /* FIXME: does not return all teamcategories properly when filtering by one */
        
        $dql = '
    	SELECT t, tc, ti, ts
    	FROM \Dodici\Fansworld\WebBundle\Entity\Team t
        LEFT JOIN t.teamcategories tc
        LEFT JOIN t.image ti
        LEFT JOIN t.splash ts
        WHERE t.active = true
        ';
        
        if ($text)
            $dql .= '
            AND t.title LIKE :textlike
            ';
            
        $dql .= ' GROUP BY t, tc ';
        
        if ($category)
            $dql .= '
            HAVING tc = :category
            ';
        
        $dql .= ' ORDER BY t.fanCount DESC ';
            
        $query = $this->_em->createQuery($dql);
        
        if ($category)
            $query = $query->setParameter('category', $category);
                
        if ($text)
            $query = $query->setParameter('textlike', '%' . $text . '%');

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
}