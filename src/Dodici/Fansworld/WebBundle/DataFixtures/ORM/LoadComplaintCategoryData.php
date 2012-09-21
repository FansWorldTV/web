<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\ComplaintCategory;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadComplaintCategoryData extends AbstractFixture implements FixtureInterface
{
    const YAML_PATH = '../complaintcategories.yml';
	
	function load(ObjectManager $manager)
    {
        if (file_exists(__DIR__.'/'.self::YAML_PATH)) {
	    	$loader = Yaml::parse(__DIR__.'/'.self::YAML_PATH);
	    	
	        foreach ($loader as $ct) {
	        	$complaintcategory = new ComplaintCategory();
	        	
	        	if (is_array($ct['title'])) {
	        		foreach ($ct['title'] as $locale => $title) {
	        			$complaintcategory->setTranslatableLocale($locale);
	        			$complaintcategory->setTitle($title);
	        			$manager->persist($complaintcategory);
	        			$manager->flush();
	        		}
	        	} else {
	        		$complaintcategory->setTitle($ct['title']);
	        		$manager->persist($complaintcategory);
	        	}
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
}