<?php

declare(strict_types=1);

namespace BinSoul\Symfony\Bundle\Routing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('binsoul_routing');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('prefix')
                    ->defaultValue('')
                    ->info('will be prepended to table, index and sequence names')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
