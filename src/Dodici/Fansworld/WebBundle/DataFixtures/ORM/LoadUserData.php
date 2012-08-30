<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadUserData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const IMAGE_FILE_PATH = '../Files/users';
	const YAML_PATH = '../users.yml';
	
	private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
	
	function load(ObjectManager $manager)
    {
    	$usertypes = array('fan' => User::TYPE_FAN, 'staff' => User::TYPE_STAFF);
    	
    	if (file_exists(__DIR__.'/'.self::YAML_PATH)) {
	    	$loader = Yaml::parse(__DIR__.'/'.self::YAML_PATH);
	    	
	        foreach ($loader as $ct) {
	        	$user = new User();
		    	$user->setEmail($ct['email']);
		    	$user->setUsername($ct['username']);
		    	$user->setFirstname($ct['firstname']);
		    	$user->setLastname($ct['lastname']);
		    	$user->setPlainPassword($ct['password']);
		    	$user->setType($usertypes[$ct['type']]);
		    	$user->setEnabled(true);
		    	
	        	if (isset($ct['roles']) && $ct['roles']) {
	        		foreach ($ct['roles'] as $cy) {
		        		$user->addRole($cy);
	        		}
	        	}
		        
		        if (isset($ct['image']) && $ct['image']) {
		        	$imagepath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$ct['image'];
		        	if (is_file($imagepath)) {
			        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                    $media = new Media();
	                    $media->setBinaryContent($imagepath);
	                    $media->setContext('default');
	                    $media->setProviderName('sonata.media.provider.image');
	                    $mediaManager->save($media);
	                    
	                    $user->setImage($media);
		        	}
		        }
		        
	            if (isset($ct['splash']) && $ct['splash']) {
		        	$splashpath = __DIR__.'/'.self::IMAGE_FILE_PATH.'/'.$ct['splash'];
		        	if (is_file($splashpath)) {
			        	$mediaManager = $this->container->get("sonata.media.manager.media");
	                    $media = new Media();
	                    $media->setBinaryContent($splashpath);
	                    $media->setContext('default');
	                    $media->setProviderName('sonata.media.provider.image');
	                    $mediaManager->save($media);
	                    
	                    $user->setSplash($media);
		        	}
		        }
		
		        $manager->persist($user);
		        $this->addReference('user-'.$ct['id'], $user);
	        }
	        
	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }
    
	public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}