<?php

namespace BinSoul\Symfony\Bundle\Routing\DependencyInjection\Compiler;

use BinSoul\Symfony\Bundle\Routing\ChainRouter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideRouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(ChainRouter::class)) {
            return;
        }

        $definition = $container->getDefinition(ChainRouter::class);

        if ($container->hasAlias('router')) {
            $alias = $container->getAlias('router');
            $definition->addMethodCall('addRouter', [new Reference($alias), 0]);
        }

        $container->setAlias('router', ChainRouter::class);

        foreach ($container->findTaggedServiceIds('router') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;

            $definition->addMethodCall('addRouter', [new Reference($id), $priority]);
        }
    }
}
