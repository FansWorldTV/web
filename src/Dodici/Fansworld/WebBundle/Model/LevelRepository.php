<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * LevelRepository
 */
class LevelRepository extends CountBaseRepository
{
	/**
	 * Get the corresponding level for a score
	 * @param int $minscore
	 */
    public function byScore($minscore) 
	{
		return $this->_em->createQuery('
    	SELECT l
    	FROM \Dodici\Fansworld\WebBundle\Entity\Level l
    	WHERE
    	:score >= l.minimum AND l.active = true
    	ORDER BY l.minimum DESC
    	')
    		->setParameter('score', $minscore)
    		->setMaxResults(1)
    		->getOneOrNullResult();
	}
}