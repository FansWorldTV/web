<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * Base repository class with generic count/search methods
 */
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
    
    /**
     * Search
     * 
     * term to search for:
     * @param string $text
     * 
     * current logged in user, or null:
     * @param User|null $user
     * 
     * @param int|null $limit
     * @param int|null $offset
     */
    public function search($text, $user=null, $limit=null, $offset=null)
    {
    	if (!($user instanceof User)) $user = null;
        
        $textfields=array('title','content');
    	
    	$conditions = array();
    	foreach ($textfields as $tf) {
    		if (property_exists($this->_entityName, $tf)) $conditions[] = '(e.'.$tf.' LIKE :textlike)';
    	}
    	
    	$querystring = '
    	SELECT e FROM
    	'.$this->_entityName.' e ';
    	
    	if ($conditions) $querystring .= ' WHERE (' . join(' OR ', $conditions) .') ';
    	
    	if (property_exists($this->_entityName, 'active')) $querystring .= ' AND (e.active = true) ';
    	
    	$querystring .= ' ORDER BY e.id DESC';
    	
    	$query = $this->_em->createQuery($querystring);
    	if ($conditions) $query = $query->setParameter('textlike', '%'.$text.'%');
    	
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
    }
    
	/**
     * Count Search
     * 
     * term to search for:
     * @param string $text
     * 
     * current logged in user, or null:
     * @param User|null $user
     */
    public function countSearch($text, $user=null)
    {
    	if (!($user instanceof User)) $user = null;
        
        $textfields=array('title','content');
    	
    	$conditions = array();
    	foreach ($textfields as $tf) {
    		if (property_exists($this->_entityName, $tf)) $conditions[] = '(e.'.$tf.' LIKE :textlike)';
    	}
    	
    	$querystring = '
    	SELECT COUNT(e.id) FROM
    	'.$this->_entityName.' e ';
    	
    	if ($conditions) $querystring .= ' WHERE (' . join(' OR ', $conditions) .') ';
    	
    	if (property_exists($this->_entityName, 'active')) $querystring .= ' AND (e.active = true) ';
    	
    	$query = $this->_em->createQuery($querystring);
        
        
    	if ($conditions) $query = $query->setParameter('textlike', '%'.$text.'%');
    	
    	return (int)$query->getSingleScalarResult();
    }
    
	/**
     * Count entities tagged with the tagentity, of type
     * DO NOT INVOKE DIRECTLY
     * @param mixed $tagentity
     * @param string $type
     * 
     * @return int
     */
    public function countTagged($tagentity, $type)
    {
        $tagtype = $this->getType($tagentity);
        
        return $this->_em->createQuery('
    	SELECT COUNT(DISTINCT hse)
    	FROM \Dodici\Fansworld\WebBundle\Entity\Has'.ucfirst($tagtype).' hs
    	INNER JOIN hs.'.$type.' hse
    	WHERE hse.active = true
    	AND hs.'.$tagtype.' = :tagentity
    	')
            ->setParameter('tagentity', $tagentity->getId())
            ->getSingleScalarResult();
    }
    
    protected function getType($entity)
    {
        $name = $this->_em->getClassMetadata(get_class($entity))->getName();
        $exp = explode('\\', $name);
		return strtolower(end($exp));
    }
}