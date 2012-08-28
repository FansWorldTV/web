<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\Team;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadTeamData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const IMAGE_FILE_PATH = '../Files/teams';
	const YAML_PATH = '../teams.yml';
	const SPLASH_FILENAME = 'equipo_%d_portada';
	const IMAGE_FILENAME = 'equipo_%d_avatar';
	
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
	        	$team = new Team();
	        	
	        	$teamcategory = $manager->merge($this->getReference('teamcategory-'.$ct['teamcategory']));
	        	$team->addTeamcategory($teamcategory);
	        	$team->setTitle($ct['title']);
	        	if (isset($ct['foundedAt']) && $ct['foundedAt']) {
	        	    $team->setFoundedAt(\DateTime::createFromFormat('U', $ct['foundedAt']));
	        	}
	        	$team->setNicknames($ct['nicknames']);
	        	$team->setLetters($ct['letters']);
	        	$team->setShortname($ct['shortname']);
	        	$team->setStadium($ct['stadium']);
	        	$team->setWebsite($ct['website']);
	        	$team->setTwitter($ct['twitter']);
	        	if (isset($ct['external']) && $ct['external']) {
	        	    $team->setExternal($ct['external']);
	        	}
	        	$team->setContent($ct['content']);
	        	if (isset($ct['country'])) {
	        		$country = $manager->merge($this->getReference('country-'.$ct['country']));
	        		$team->setCountry($country);
	        	}
	        	
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
	                   
	                $team->setImage($media);
		        }
		        
	        	if ($sreal) {
		        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                $media = new Media();
	                $media->setBinaryContent($sreal);
	                $media->setContext('default');
	                $media->setProviderName('sonata.media.provider.image');
	                $mediaManager->save($media);
	                   
	                $team->setSplash($media);
		        }
		
		        $manager->persist($team);
		        $this->addReference('team-'.$ct['id'], $team);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 5; // the order in which fixtures will be loaded
    }
}