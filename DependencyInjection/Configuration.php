<?php

namespace RevisionTen\Calendar\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('calendar');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('event_form_type')->end()
                ->scalarNode('event_solr_serializer')->end()
                ->scalarNode('rule_form_type')->end()
            ->end();

        return $treeBuilder;
    }
}
