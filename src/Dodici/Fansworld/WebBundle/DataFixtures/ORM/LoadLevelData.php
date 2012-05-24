<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Dodici\Fansworld\WebBundle\Entity\Level;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadLevelData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const IMAGE_FILE_PATH = '../Files/levels';
	const YAML_PATH = '../levels.yml';
	
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
	        	$level = new Level();
	        	$level->setTitle($ct['title']);
		        $level->setMinimum((int)$ct['minimum']);
		        $level->setActive(true);
		        
		        if (isset($ct['image']) && $ct['image']) {
		        	$imagepath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$ct['image'];
		        	if (is_file($imagepath)) {
			        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                    $media = new Media();
	                    $media->setBinaryContent($imagepath);
	                    $media->setContext('default');
	                    $media->setProviderName('sonata.media.provider.image');
	                    $mediaManager->save($media);
	                    
	                    $level->setImage($media);
		        	}
		        }
		
		        $manager->persist($level);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}