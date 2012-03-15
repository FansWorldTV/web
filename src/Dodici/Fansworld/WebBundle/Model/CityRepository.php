<?php
namespace Dodici\Fansworld\WebBundle\Model;

use Dodici\Fansworld\WebBundle\Entity\Country;

use Doctrine\ORM\EntityRepository;

class CityRepository extends CountBaseRepository
{
	/**
     * Get form choices (profile edit)
     */
	public function formChoices($country=null)
    {
        return $this->_em->createQuery('
    	SELECT c
    	FROM \Dodici\Fansworld\WebBundle\Entity\City c
    	WHERE 
    	((:country IS NULL) OR (c.country = :country))
    	ORDER BY c.title ASC')
        	->setParameter('country', ($country instanceof Country) ? $country->getId() : $country)
    		->getResult();
    }
    
}