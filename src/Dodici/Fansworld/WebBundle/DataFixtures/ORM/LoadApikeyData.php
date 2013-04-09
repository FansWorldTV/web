<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Dodici\Fansworld\WebBundle\Entity\Apikey;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadApikeyData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{
	const YAML_PATH = '../apikeys.yml';
	
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
	        	$key = new Apikey();
	        	$key->setTitle($ct['title']);
	        	$key->setApikey($ct['apikey']);
	        	$key->setSecret($ct['secret']);
		        
		        $manager->persist($key);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
}