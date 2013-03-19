<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\HomeVideo;

use Dodici\Fansworld\WebBundle\Entity\HasTag;
use Dodici\Fansworld\WebBundle\Entity\Tag;
use Dodici\Fansworld\WebBundle\Entity\HasTeam;
use Dodici\Fansworld\WebBundle\Entity\HasIdol;
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
use Gedmo\Sluggable\Util\Urlizer as GedmoUrlizer;

class LoadVideoData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../videos.yml';
	const IMAGE_FILE_PATH = '../Files/videos';
	
	private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
	
	function load(ObjectManager $manager)
    {
    	if (file_exists(__DIR__.'/'.self::YAML_PATH)) {
	    	$loader = Yaml::parse(__DIR__.'/'.self::YAML_PATH);
	    	
	    	set_time_limit(36000);
	    	
	    	$uploader = $this->container->get('video.uploader');
	    	$toprocess = array();
	    	$cnt = count($loader);
	    	$x = 0;
	    	
	        foreach ($loader as $ct) {
	            $x++;
	            echo "Creating video $x / $cnt ... ";
	            
	            try {
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
    	        	echo 'a';
    	        	
    	        	$videocategory = $manager->merge($this->getReference('videocategory-'.$ct['videocategory']));
    	        	$video->setVideocategory($videocategory);
    	        	if (isset($ct['title']) && $ct['title']) $video->setTitle($ct['title']);
    	        	if (isset($ct['content']) && $ct['content']) $video->setContent($ct['content']);
    	        	
    	        	if (isset($ct['createdAt']) && $ct['createdAt']) {
    	        	    $date = \DateTime::createFromFormat('Y-m-d', $ct['createdAt']);
    	        	    if ($date) $video->setCreatedAt($date);
    	        	}
    	        	echo 'b';
    	        	
    	        	$video->setPrivacy(Privacy::EVERYONE);
    	        	$video->setHighlight($ct['highlight']);
    	        	
    	        	if (isset($ct['stream']) && $ct['stream']) {
    	        	    $video->setStream($ct['stream']);
    	        	    $video->setActive(false);
    	        	}
    	        	
    	            if (isset($ct['splash']) && $ct['splash']) {
    		        	$imagepath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$ct['splash'];
    		        	if (is_file($imagepath)) {
    			        	$mediaManager = $this->container->get("sonata.media.manager.media");
    	                    $media = new Media();
    	                    $media->setBinaryContent($imagepath);
    	                    $media->setContext('default');
    	                    $media->setProviderName('sonata.media.provider.image');
    	                    $mediaManager->save($media);
    	                    
    	                    $video->setSplash($media);
    		        	}
    		        }
    	            
    		        $manager->persist($video);
    		        
    		        if (isset($ct['home']) && $ct['home']) {
    		            $hv = new HomeVideo();
    		            $hv->setVideo($video);
    		            $hv->setVideoCategory($videocategory);
    		            $manager->persist($hv);
    		        }
    		        
    		        echo 'c';
    		        
    		        if ($user) {
        		        if (isset($ct['tagtexts']) && $ct['tagtexts']) {
        		            foreach ($ct['tagtexts'] as $tid) {
        		                echo 'd';
        		                echo '*'.$tid.'*';
        		                
        		                $slug = GedmoUrlizer::urlize($tid);
        		                if ($this->hasReference('tag-'.$slug)) {
        		                    $tag = $manager->merge($this->getReference('tag-'.$slug));
        		                } else {
        		                    $tag = new Tag();
            		    			$tag->setTitle($tid);
            		    			$manager->persist($tag);
            		    			$this->addReference('tag-'.$slug, $tag);
        		                }
        		                echo 'e';
        		                $hastag = new HasTag();
        			    		$hastag->setAuthor($user);
        			    		$hastag->setTag($tag);
        			    		$hastag->setVideo($video);
        			    		
        			    		$video->addHasTag($hastag);
        			    		$manager->persist($video);
        			    		echo 'f';
        		            }
        		        }
        		        
    		            if (isset($ct['tagteams']) && $ct['tagteams']) {
        		            foreach ($ct['tagteams'] as $tid) {
        		                echo 'g';
        		                echo '*'.$tid.'*';
        		                
        		                if ($this->hasReference('team-'.$tid)) {
            		                $team = $manager->merge($this->getReference('team-'.$tid));
            		                
            		                $hasteam = new HasTeam();
                		    		$hasteam->setAuthor($user);
                		    		$hasteam->setTeam($team);
                		    		$hasteam->setVideo($video);
                		    		
                		    		$video->addHasTeam($hasteam);
                		    		$manager->persist($video);
                		    		echo 'h';
        		                }
        		            }
        		        }
        		        
    		            if (isset($ct['tagidols']) && $ct['tagidols']) {
    		                foreach ($ct['tagidols'] as $tid) {
        		                echo 'i';
        		                echo '*'.$tid.'*';
        		                
        		                if ($this->hasReference('idol-'.$tid)) {
        		                    $idol = $manager->merge($this->getReference('idol-'.$tid));
            		                
            		                $hasidol = new HasIdol();
                		    		$hasidol->setAuthor($user);
                		    		$hasidol->setIdol($idol);
                		    		$hasidol->setVideo($video);
                		    		
                		    		$video->addHasIdol($hasidol);
                		    		$manager->persist($video);
                		    		echo 'j';
        		                }
        		            }
        		        }
    		        }
    		        
    		        if ($video->getStream()) $toprocess[] = $video;
    		        echo 'k';
	            } catch(\Exception $e) {
	                echo "(error!) " . $e->getMessage();
	            }
	            echo "\n";
	            $manager->flush();
	        }
	        
	        
	        
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