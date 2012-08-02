<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

/**
 * TagRepository
 */
class TagRepository extends CountBaseRepository
{
	/**
	 * Get matching
	 * @param string $text
	 * @param int|null $limit
	 * @param int|null $offset
	 */
	public function matching($text=null, $limit=null, $offset=null)
	{
		$query = $this->_em->createQuery('
    	SELECT t
    	FROM \Dodici\Fansworld\WebBundle\Entity\Tag t
    	WHERE
    	(
    	  (:text IS NULL OR (t.title LIKE :textlike))
    	)
    	ORDER BY t.useCount DESC
    	')
    		->setParameter('text', $text)
    		->setParameter('textlike', '%'.$text.'%');
    		
    	if ($limit !== null)
    	$query = $query->setMaxResults($limit);
    	
    	if ($offset !== null)
    	$query = $query->setFirstResult($offset);
    		
    	return $query->getResult();
	}
}