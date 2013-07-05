<?php
namespace Dodici\Fansworld\WebBundle\DataFixtures\ORM;

use Dodici\Fansworld\WebBundle\Entity\Genre;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadGenreData extends AbstractFixture implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
{
	const YAML_PATH = '../genres.yml';

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
	        	$genre = new Genre();

	            if (is_array($ct['title'])) {
	        		foreach ($ct['title'] as $locale => $title) {
	        			$genre->setTranslatableLocale($locale);
                        $genre->setTitle($title);
	        			$manager->persist($genre);
	        			$manager->flush();
	        		}
	        	} else {
                    $genre->setTitle($ct['title']);
	        		$manager->persist($genre);
	        	}

                foreach ($ct['children'] as $child) {
                    $genreChild = new Genre();
                    if (is_array($child['title'])) {
                        foreach ($child['title'] as $locale => $title) {
                            $genreChild->setTranslatableLocale($locale);
                            $genreChild->setTitle($title);
                            $manager->persist($genreChild);
                            $manager->flush();
                        }
                    } else {
                        $genreChild->setTitle($child['title']);
                        $manager->persist($genreChild);
                    }
                    $genre->addChildren($genreChild);
                    $manager->persist($genre);
                    $this->addReference('genre-'.$child['id'], $genreChild);
                }

		        $this->addReference('genre-'.$ct['id'], $genre);
	        }

	        $manager->flush();
        } else {
        	throw new \Exception('Fixture file does not exist');
        }
    }

	public function getOrder()
    {
        return 4; // the order in which fixtures will be loaded
    }
}