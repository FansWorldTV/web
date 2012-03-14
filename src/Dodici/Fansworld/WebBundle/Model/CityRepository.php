<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Doctrine\ORM\EntityRepository;

class CityRepository extends CountBaseRepository
{
	/**
     * Get form choices (profile edit)
     */
	public function formChoices()
    {
        return $this->_em->createQuery('
    	SELECT c, cc
    	FROM \Dodici\Fansworld\WebBundle\Entity\City c
    	JOIN c.country cc
    	ORDER BY c.title ASC')
    		->getResult();
    }
    
}