<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Dodici\Fansworld\WebBundle\Entity\Badge;
use Dodici\Fansworld\WebBundle\Entity\BadgeStep;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadBadgeData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface
{
	const IMAGE_FILE_PATH = '../Files/badges';
	const YAML_PATH = '../badges.yml';
	
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
	        	$badge = new Badge();
	        	$badge->setTitle($ct['title']);
	        	$badge->setContent($ct['content']);
	        	$type = $ct['type'];
	        	$badge->setType(constant('Dodici\Fansworld\WebBundle\Entity\Badge::'.$type));
	        	
	            if (isset($ct['steps']) && $ct['steps']) {
	        		foreach ($ct['steps'] as $cy) {
		        		$bs = new BadgeStep();
		        		$bs->setMinimum(intval($cy['minimum']));
		        		
        	        	if (isset($cy['image']) && $cy['image']) {
        		        	$imagepath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$cy['image'];
        		        	if (is_file($imagepath)) {
        			        	$mediaManager = $this->container->get("sonata.media.manager.media");
        	                    $media = new Media();
        	                    $media->setBinaryContent($imagepath);
        	                    $media->setContext('default');
        	                    $media->setProviderName('sonata.media.provider.image');
        	                    $mediaManager->save($media);
        	                    
        	                    $bs->setImage($media);
        		        	}
        		        }
	        		}
	        	}
		        
		        $manager->persist($badge);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
}