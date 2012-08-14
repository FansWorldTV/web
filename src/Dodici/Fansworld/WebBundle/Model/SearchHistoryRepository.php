<?php

namespace Dodici\Fansworld\WebBundle\Model;

use Application\Sonata\UserBundle\Entity\User;

use Doctrine\ORM\EntityRepository;

/**
 * SearchHistoryRepository
 */
class SearchHistoryRepository extends CountBaseRepository
{
	/**
	 * Get top searched terms
	 * 
	 * @param string|null $match - String to match terms against, term%
	 * @param User|null $user - filter by user
	 * @param string|null $ip - filter by ip
	 * @param (int)Extensions\Search::TYPE_*|null $type - filter by type of search
	 * @param int|null $limit
	 * @param int|null $offset
	 */
    public function topTerms($match=null, $user=null, $ip=null, $type=null, $limit=null, $offset=null)
	{
		$dql = '
    	SELECT sh.term, COUNT(sh.id) cnt
    	FROM \Dodici\Fansworld\WebBundle\Entity\SearchHistory sh';
    	
		$criterias = array(); $params = array();
		
		if ($match) {
		    $criterias[] = 'sh.term LIKE :match';
		    $params['match'] = $match.'%';
		}
		
		if ($user instanceof User) {
		    $criterias[] = 'sh.author = :user';
		    $params['user'] = $user->getId();
		}
		
		if ($ip) {
		    $criterias[] = 'sh.ip = :ip';
		    $params['ip'] = $ip;
		}
		
		if ($type) {
		    $criterias[] = 'sh.type = :type';
		    $params['type'] = $type;
		}
		
		if ($criterias) {
		    $dql .= ' WHERE ' . join(' AND ', $criterias);
		}
		
		$dql .= '
		GROUP BY sh.term
		ORDER BY cnt DESC';
		
		$query = $this->_em->createQuery($dql);
		
		foreach ($params as $k => $p) {
		    $query = $query->setParameter($k, $p);
		}
    		
    	if ($limit !== null) $query = $query->setMaxResults($limit);
    	if ($offset !== null) $query = $query->setFirstResult($offset);
    	
    	return $query->getResult();
	}
}