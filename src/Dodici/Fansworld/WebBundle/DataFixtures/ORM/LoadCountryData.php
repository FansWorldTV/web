<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Dodici\Fansworld\WebBundle\Entity\Country;
use Dodici\Fansworld\WebBundle\Entity\City;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Persistence\ObjectManager;

class LoadCountryData implements FixtureInterface
{
    function load(ObjectManager $manager)
    {
        if (file_exists(__DIR__.'/../countries.yml')) {
	    	$loader = Yaml::parse(__DIR__.'/../countries.yml');
	    	//var_dump($loader); exit;
	        foreach ($loader as $ct) {
	        	$country = new Country();
	        	
	        	if (is_array($ct['name'])) {
	        		foreach ($ct['name'] as $locale => $title) {
	        			$country->setTranslatableLocale($locale);
	        			$country->setTitle($title);
	        			$manager->persist($country);
	        			$manager->flush();
	        		}
	        	} else {
	        		$country->setTitle($ct['name']);
	        		$manager->persist($country);
	        	}
	        	
	        	if (isset($ct['cities']) && $ct['cities']) {
	        		foreach ($ct['cities'] as $cy) {
		        		$city = new City();
		        		$city->setCountry($country);
			        	if (is_array($cy)) {
			        		foreach ($cy as $locale => $title) {
			        			$city->setTranslatableLocale($locale);
			        			$city->setTitle($title);
			        			$manager->persist($city);
			        			$manager->flush();
			        		}
			        	} else {
			        		$city->setTitle($cy);
			        		$manager->persist($city);
			        	}
	        		}
	        	}
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    	
        /*
    	$userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setPassword('test');

        $manager->persist($userAdmin);
        $manager->flush();

        $this->addReference('admin-user', $userAdmin);
        */
    }
}