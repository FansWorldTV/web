<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\IdolCareer;

use Dodici\Fansworld\WebBundle\Entity\Idol;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadIdolData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const IMAGE_FILE_PATH = '../Files/idols';
	const YAML_PATH = '../idols.yml';
	const SPLASH_FILENAME = 'idolo_%d_portada';
	const IMAGE_FILENAME = 'idolo_%d_avatar';
	
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
	        	echo $ct['id'] . "\n";
	            $idol = new Idol();
	        	$idol->setFirstname($ct['firstname']);
	        	$idol->setLastname($ct['lastname']);
	        	if (isset($ct['birthday']) && $ct['birthday']) {
	        	    $date = \DateTime::createFromFormat('Y-m-d', $ct['birthday']);
	        	    if ($date) $idol->setBirthday($date);
	        	}
	        	$idol->setNicknames($ct['nicknames']);
	        	$idol->setTwitter($ct['twitter']);
	        	$idol->setJobname($ct['jobname']);
	        	$idol->setContent($ct['content']);
	        	if (isset($ct['country'])) {
	        	    if (is_numeric($ct['country'])) {
    	        		$country = $manager->merge($this->getReference('country-'.$ct['country']));
    	        		$idol->setCountry($country);
	        	    } else {
	        	        $idol->setOrigin($ct['country']);
	        	    }
	        	}
                if (isset($ct['genre']) && $ct['genre']) {
                    $genre = $manager->merge($this->getReference('genre-'.$ct['genre']));
                    $idol->setGenre($genre);
                }
	        	
	        	/* TEAMS */
	        	if (isset($ct['teams']) && $ct['teams']) {
	        		foreach ($ct['teams'] as $cy) {
		        		$career = new IdolCareer();
		        		
	        			if (isset($cy['id'])) {
		        			$team = $manager->merge($this->getReference('team-'.$cy['id']));
		        			$career->setTeam($team);
		        		} elseif (isset($cy['name'])) {
		        			$career->setTeamname($cy['name']);
		        		}
		        		$career->setManager($cy['manager']);
		        		$career->setDebut($cy['debut']);
		        		$career->setActual($cy['actual']);
		        		$career->setHighlight($cy['highlight']);
		        		$idol->addIdolCareer($career);
	        		}
	        	}
	        	/* END TEAMS */
	        	
	        	/* IMAGES */
	        	$image = null; $splash = null; $ireal = null; $sreal = null;
	        	$path = __DIR__.'/'.self::IMAGE_FILE_PATH.'/';
	        	$imagefn = sprintf(self::IMAGE_FILENAME, $ct['id']);
	        	$splashfn = sprintf(self::SPLASH_FILENAME, $ct['id']);
	        	
	        	if (is_file($path . $imagefn . '.png')) $ireal = $path . $imagefn . '.png';
	        	elseif (is_file($path . $imagefn . '.jpg')) $ireal = $path . $imagefn . '.jpg';
	        	if (is_file($path . $splashfn . '.png')) $sreal = $path . $splashfn . '.png';
	        	elseif (is_file($path . $splashfn . '.jpg')) $sreal = $path . $splashfn . '.jpg';
	        	
		        if ($ireal) {
		        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                $media = new Media();
	                $media->setBinaryContent($ireal);
	                $media->setContext('default');
	                $media->setProviderName('sonata.media.provider.image');
	                $mediaManager->save($media);
	                   
	                $idol->setImage($media);
		        }
		        
	        	if ($sreal) {
		        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                $media = new Media();
	                $media->setBinaryContent($sreal);
	                $media->setContext('default');
	                $media->setProviderName('sonata.media.provider.image');
	                $mediaManager->save($media);
	                   
	                $idol->setSplash($media);
		        }
		        /* END IMAGES */
		
		        $manager->persist($idol);
		        $this->addReference('idol-'.$ct['id'], $idol);
		        $manager->flush();
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}