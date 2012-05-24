<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Dodici\Fansworld\WebBundle\Entity\InterestCategory;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadInterestCategoryData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../interestcategories.yml';
	
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
	        	$interestcategory = new InterestCategory();
	        	$interestcategory->setTitle($ct['title']);
				
		        $manager->persist($interestcategory);
		        $this->addReference('interestcategory-'.$ct['id'], $interestcategory);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 7; // the order in which fixtures will be loaded
    }
}