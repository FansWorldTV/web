<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\Sport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadSportData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../sports.yml';
	
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
	        	$sport = new Sport();
	        	$sport->setTitle($ct['title']);
				
		        $manager->persist($sport);
		        $this->addReference('sport-'.$ct['id'], $sport);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}