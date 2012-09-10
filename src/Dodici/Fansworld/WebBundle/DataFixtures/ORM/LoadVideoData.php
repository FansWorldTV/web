<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\Privacy;
use Dodici\Fansworld\WebBundle\Entity\Video;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadVideoData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../videos.yml';
	
	private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
	
	function load(ObjectManager $manager)
    {
    	if (file_exists(__DIR__.'/'.self::YAML_PATH)) {
	    	$loader = Yaml::parse(__DIR__.'/'.self::YAML_PATH);
	    	
	    	set_time_limit(1800);
	    	
	    	$uploader = $this->container->get('video.uploader');
	    	$toprocess = array();
	    	$cnt = count($loader);
	    	$x = 0;
	    	
	        foreach ($loader as $ct) {
	            $x++;
	            echo "Creating video $x / $cnt ... \n";
	            
	            $user = null;
	            if (isset($ct['author'])) {
	        		$user = $manager->merge($this->getReference('user-'.$ct['author']));
	        	}
	            if ($user && isset($ct['url']) && $ct['url']) {
	        	    $video = $uploader->createVideoFromUrl($ct['url'], $user);
	        	} else {
	        	    $video = new Video();
	        	}
	        	if ($user) $video->setAuthor($user);
	        	
	        	$videocategory = $manager->merge($this->getReference('videocategory-'.$ct['videocategory']));
	        	$video->setVideocategory($videocategory);
	        	if (isset($ct['title']) && $ct['title']) $video->setTitle($ct['title']);
	        	if (isset($ct['content']) && $ct['content']) $video->setContent($ct['content']);
	        	
	        	if (isset($ct['createdAt']) && $ct['createdAt']) {
	        	    $date = \DateTime::createFromFormat('Y-m-d', $ct['createdAt']);
	        	    if ($date) $video->setCreatedAt($date);
	        	}
	        	
	        	$video->setPrivacy(Privacy::EVERYONE);
	        	$video->setHighlight($ct['highlight']);
	        	
	        	if (isset($ct['stream']) && $ct['stream']) {
	        	    $video->setStream($ct['stream']);
	        	    $video->setActive(false);
	        	}
	            
		        $manager->persist($video);
		        
		        if ($video->getStream()) $toprocess[] = $video;
	        }
	        
	        $manager->flush();
	        
	        $x = 0;
	        $cnt = count($toprocess);
	        /*
	        foreach ($toprocess as $vidp) {
	            $x++;
	            echo "Processing video $x / $cnt ... \n";
	            $uploader->process($vidp);
	        }*/
	        if ($cnt) echo "Please run /batch/videoprocessing \n";
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 10; // the order in which fixtures will be loaded
    }
}