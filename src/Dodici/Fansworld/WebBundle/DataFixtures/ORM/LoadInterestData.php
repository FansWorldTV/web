<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Dodici\Fansworld\WebBundle\Entity\Interest;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadInterestData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../interests.yml';
	const IMAGE_FILE_PATH = '../Files/interests';
	
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
	        	$interest = new Interest();
	        	$interest->setTitle($ct['title']);
	        	$interest->setActive(true);
	        	
	        	$interestcategory = $manager->merge($this->getReference('interestcategory-'.$ct['interestcategory']));
	        	$interest->setInterestCategory($interestcategory);
	        	
	        	if (isset($ct['image']) && $ct['image']) {
		        	$imagepath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$ct['image'];
		        	if (is_file($imagepath)) {
			        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                    $media = new Media();
	                    $media->setBinaryContent($imagepath);
	                    $media->setContext('default');
	                    $media->setProviderName('sonata.media.provider.image');
	                    $mediaManager->save($media);
	                    
	                    $interest->setImage($media);
		        	}
		        }
				
		        $manager->persist($interest);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 8; // the order in which fixtures will be loaded
    }
}