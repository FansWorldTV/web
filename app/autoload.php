<?php

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'          => array(__DIR__.'/../vendor/symfony/src', __DIR__.'/../vendor/bundles'),
    'Sensio'           => __DIR__.'/../vendor/bundles',
    'JMS'              => __DIR__.'/../vendor/bundles',
	'Doctrine\\Common\\DataFixtures' => __DIR__.'/../vendor/doctrine-fixtures/lib',
    'Doctrine\\Common' => __DIR__.'/../vendor/doctrine-common/lib',
    'Doctrine\\DBAL'   => __DIR__.'/../vendor/doctrine-dbal/lib',
    'Doctrine'         => __DIR__.'/../vendor/doctrine/lib',
    'Monolog'          => __DIR__.'/../vendor/monolog/src',
    'Assetic'          => __DIR__.'/../vendor/assetic/src',
    'Metadata'         => __DIR__.'/../vendor/metadata/src',
	'FOS'			   => __DIR__.'/../vendor/bundles',
	'Sonata'     => __DIR__.'/../vendor/bundles',
    'Knp\Bundle' => __DIR__.'/../vendor/bundles',
    'Knp\Menu'   => __DIR__.'/../vendor/knp/menu/src',
    'Application'   => __DIR__,
	'Imagine'          => __DIR__.'/../vendor/imagine/lib',
    'Avalanche'        => __DIR__.'/../vendor/bundles',
    'Stof'  => __DIR__.'/../vendor/bundles',
    'Gedmo' => __DIR__.'/../vendor/gedmo-doctrine-extensions/lib',
    'Gaufrette'     => __DIR__.'/../vendor/gaufrette/src',
	'Dodici'   		   => __DIR__.'/../src',
	'DataFactory'   	   => __DIR__.'/../src',
	'BaseFacebook'     => __DIR__.'/../vendor/facebook/src',
	'Artseld' => __DIR__.'/../vendor/bundles',
	'Bazinga' => __DIR__.'/../vendor/bundles',
    'Kaltura' => __DIR__.'/../src',
    'Snc' => __DIR__.'/../vendor/bundles',    
    //'Predis' => __DIR__.'/../vendor/predis/lib',
));
$loader->registerPrefixes(array(
    'Twig_Extensions_' => __DIR__.'/../vendor/twig-extensions/lib',
    'Twig_'            => __DIR__.'/../vendor/twig/lib',
    'Elastica' => __DIR__.'/../vendor/elastica/lib',
));

// intl
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->registerPrefixFallbacks(array(__DIR__.'/../vendor/symfony/src/Symfony/Component/Locale/Resources/stubs'));
}

$loader->registerNamespaceFallbacks(array(
    __DIR__.'/../src',
));
$loader->register();

AnnotationRegistry::registerLoader(function($class) use ($loader) {
    $loader->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(__DIR__.'/../vendor/doctrine/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

// Swiftmailer needs a special autoloader to allow
// the lazy loading of the init file (which is expensive)
require_once __DIR__.'/../vendor/swiftmailer/lib/classes/Swift.php';
Swift::registerAutoload(__DIR__.'/../vendor/swiftmailer/lib/swift_init.php');

