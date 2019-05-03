<?php

namespace App\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('commsy');

        $rootNode
            ->children()
                ->arrayNode('etherpad')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('api_key')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('api_url')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('base_url')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mediawiki')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('url')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('roomWikiUrl')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('apiUrl')
                            ->defaultValue('/api.php')
                        ->end()
                        ->scalarNode('consumerKey')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('consumerSecret')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('accessToken')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('accessSecret')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dates')
                    ->children()
                        ->scalarNode('timezone')
                        ->defaultValue('UTC')
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
