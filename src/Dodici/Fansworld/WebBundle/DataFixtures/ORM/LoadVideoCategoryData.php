<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\VideoCategory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadVideoCategoryData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../videocategories.yml';
	
	private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
	
	function load(ObjectManager $manager)
    {
    	if (file_exists(__DIR__.'/'.self::YAML_PATH)) {
	    	$loader = Yaml::parse(__DIR__.'/'.self::YAML_PATH);
	    	
	        foreach ($loader as $ct) {
	        	$videocategory = new VideoCategory();
	        	
	            if (is_array($ct['title'])) {
	        		foreach ($ct['title'] as $locale => $title) {
	        			$videocategory->setTranslatableLocale($locale);
	        			$videocategory->setTitle($title);
	        			$manager->persist($videocategory);
	        			$manager->flush();
	        		}
	        	} else {
	        		$videocategory->setTitle($ct['title']);
	        		$manager->persist($videocategory);
	        	}
	        	
		        $this->addReference('videocategory-'.$ct['id'], $videocategory);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 9; // the order in which fixtures will be loaded
    }
}