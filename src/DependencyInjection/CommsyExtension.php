<?php

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CommsyExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // mediawiki
        $container->setParameter('commsy.mediawiki.enabled', $config['mediawiki']['enabled']);
        $container->setParameter('commsy.mediawiki.url', $config['mediawiki']['url']);
        $container->setParameter('commsy.mediawiki.roomWikiUrl', $config['mediawiki']['roomWikiUrl']);
        $container->setParameter('commsy.mediawiki.apiUrl', $config['mediawiki']['apiUrl']);
        $container->setParameter('commsy.mediawiki.consumerKey', $config['mediawiki']['consumerKey']);
        $container->setParameter('commsy.mediawiki.consumerSecret', $config['mediawiki']['consumerSecret']);
        $container->setParameter('commsy.mediawiki.accessToken', $config['mediawiki']['accessToken']);
        $container->setParameter('commsy.mediawiki.accessSecret', $config['mediawiki']['accessSecret']);

        // dates
        $container->setParameter('commsy.dates.timezone', $config['dates']['timezone']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
