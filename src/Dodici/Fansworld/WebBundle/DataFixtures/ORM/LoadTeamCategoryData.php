<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\TeamCategory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadTeamCategoryData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../teamcategories.yml';
	
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
	        	$teamcategory = new TeamCategory();
	        	$teamcategory->setTitle($ct['title']);
	        	if (isset($ct['external']) && $ct['external']) $teamcategory->setExternal($ct['external']);
	        	
	        	$sport = $manager->merge($this->getReference('sport-'.$ct['sport']));
	        	$teamcategory->setSport($sport);
				
		        $manager->persist($teamcategory);
		        $this->addReference('teamcategory-'.$ct['id'], $teamcategory);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 4; // the order in which fixtures will be loaded
    }
}