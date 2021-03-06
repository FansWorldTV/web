<?php

namespace DataFactory\FeedBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * XmlDataRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class XmlDataRepository extends EntityRepository
{
	/**
	 * get the max changed date
	 */
	public function lastChangedDate($type)
	{
		
		$query = $this->_em->createQuery('
    	SELECT MAX(xd.changed)
    	FROM \DataFactory\FeedBundle\Entity\XmlData xd
    	WHERE xd.channel LIKE :likechan
    	')
        	->setParameter('likechan', '%.'.$type.'%');
        
        $date = $query->getSingleScalarResult();
        
        return $date ? (new \DateTime($date)) : null;
	}
	
	/**
	 * get changed xmls, pending process, by type (lockup redundancy)
	 */
	public function pending($type)
	{
		
		$query = $this->_em->createQuery('
    	SELECT xd
    	FROM \DataFactory\FeedBundle\Entity\XmlData xd
    	WHERE xd.channel LIKE :likechan
    	AND
    	xd.processed <= xd.changed
    	')
        	->setParameter('likechan', '%.'.$type.'%');
        
        return $query->getResult();
	}
}