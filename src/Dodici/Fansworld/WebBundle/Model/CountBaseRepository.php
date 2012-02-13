<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

class CountBaseRepository extends EntityRepository
{
    /**
     * Count by parameters
     * @param array $params
     */
	public function countBy($params = null)
    {
        $qb = $this->_em->createQueryBuilder();
        
        $qb
        ->add('select', 'count(t.id)')
        ->add('from', $this->_entityName.' t');
    	
    	if ($params) {
	        $and = $qb->expr()->andx();
	        foreach ($params as $key => $val) {
	        	if ($val === null) {
	        		$and->add($qb->expr()->isNull('t.'.$key));
	        	} else {
	        		$and->add($qb->expr()->eq('t.'.$key, $val));
	        	}
	        }
	        $qb = $qb->add('where', $and);
        }
    	
    	$query = $qb->getQuery();
    	//var_dump($query); exit;
    	return $query->getSingleScalarResult();
    }
}