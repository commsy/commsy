<?php

namespace CommsyBundle\DependencyInjection;

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
                ->arrayNode('autosave')
                    ->children()
                        ->integerNode('mode')
                            ->defaultValue(0)
                        ->end()
                        ->integerNode('limit')
                            ->defaultValue(6)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('beluga')
                    ->children()
                        ->scalarNode('url_for_beluga_system')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('url_for_beluga_upload')
                            ->defaultValue(null)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('clamscan')
                    ->children()
                        ->booleanNode('virus_scan')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('virus_use_php')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('virus_scan_cron')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('virus_scan_path')
                            ->defaultValue('/usr/bin')
                        ->end()
                        ->scalarNode('virus_scan_bin')
                            ->defaultValue('clamscan')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('cookie')
                    ->children()
                        ->scalarNode('domain')
                            ->defaultValue(null)
                        ->end()
                        ->scalarNode('path')
                            ->defaultValue(null)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('login')
                    ->children()
                        ->booleanNode('shibboleth_direct_login')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('shibboleth_redirect_url')
                            ->defaultValue('')
                        ->end()
                        ->scalarNode('shibboleth_deactivate_direct_login_by_portal_id')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->children()
                        ->scalarNode('privacy_disable_overwriting')
                            ->defaultFalse()
                        ->end()
                        ->booleanNode('allow_moderator_takeover')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('themes')
                    ->children()
                        ->scalarNode('default')
                            ->defaultValue('default')
                        ->end()
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
