<?php

namespace Dodici\Fansworld\WebBundle\Services;

use Doctrine\ORM\EntityManager;

class UserLocation
{
	protected $em;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Parse a location string/array and set country and city on the user object
     * @param mixed $location
     */
    public function parseLocation($location)
    {
    	$country = null; $city = null;
    	$locname = null;
    	
    	$countryrepo = $this->em->getRepository('DodiciFansworldWebBundle:Country');
    	$cityrepo = $this->em->getRepository('DodiciFansworldWebBundle:City');
    	
    	if (is_array($location)) {
    		if (isset($location['name'])) $locname = $location['name'];
    	} else {
    		$locname = $location;
    	}
    	
    	$exp = explode(',', $locname);
    	foreach ($exp as $x) {
    		$x = trim($x);
    		if (!$city) {
    			if (!$country) {
    				$countries = $countryrepo->findBy(array('title' => $x));
    				if (count($countries) == 1) {
    					$country = $countries[0];
    				}
    			}
    			$params = array('title' => $x);
    			if ($country) $params['country'] = $country->getId();
    			$cities = $cityrepo->findBy($params);
    			if (count($cities) == 1) {
    				$city = $cities[0];
    				$country = $city->getCountry();
    				break;
    			}
    		}
    	}
    	
    	return array(
    		'country' => $country,
    		'city' => $city
    	);
    }    
}