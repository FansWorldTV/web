<?php

namespace Bazinga\ExposeTranslationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * AddLoadersPass.
 *
 * @package ExposeTranslationBundle
 * @subpackage DependencyInjection
 * @author William DURAND <william.durand1@gmail.com>
 */
class AddLoadersPass implements CompilerPassInterface
{
    protected $container;

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('bazinga.exposetranslation.controller')) {
            return;
        }

        $this->container = $container;

        foreach ($container->findTaggedServiceIds('translation.loader') as $loaderId => $attributes) {
            $this->registerLoader($loaderId);
        }
    }

    protected function registerLoader($loaderId)
    {
        $split = explode('.', $loaderId);
        $id    = end($split);

        $this->container
            ->getDefinition('bazinga.exposetranslation.controller')
            ->addMethodCall('addLoader', array($id, new Reference($loaderId)));
    }
}
