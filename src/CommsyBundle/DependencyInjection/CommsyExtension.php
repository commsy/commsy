<?php

namespace CommsyBundle\DependencyInjection;

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

        // expose autosave
        $container->setParameter('commsy.autosave.mode', $config['autosave']['mode']);
        $container->setParameter('commsy.autosave.limit', $config['autosave']['limit']);

        // expose beluge
        $container->setParameter('commsy.beluga.url_for_beluga_system', $config['beluga']['url_for_beluga_system']);
        $container->setParameter('commsy.beluga.url_for_beluga_upload', $config['beluga']['url_for_beluga_upload']);

        // clamscan
        $container->setParameter('commsy.clamscan.virus_scan', $config['clamscan']['virus_scan']);
        $container->setParameter('commsy.clamscan.virus_use_php', $config['clamscan']['virus_use_php']);
        $container->setParameter('commsy.clamscan.virus_scan_cron', $config['clamscan']['virus_scan_cron']);
        $container->setParameter('commsy.clamscan.virus_scan_path', $config['clamscan']['virus_scan_path']);
        $container->setParameter('commsy.clamscan.virus_scan_bin', $config['clamscan']['virus_scan_bin']);

        // cookie
        $container->setParameter('commsy.cookie.path', $config['cookie']['path']);
        $container->setParameter('commsy.cookie.domain', $config['cookie']['domain']);

        // db
        $container->setParameter('commsy.db.backup_prefix', $config['db']['backup_prefix']);

        // login
        $container->setParameter('commsy.login.shibboleth_direct_login', $config['login']['shibboleth_direct_login']);
        $container->setParameter('commsy.login.shibboleth_redirect_url', $config['login']['shibboleth_redirect_url']);
        $container->setParameter('commsy.login.shibboleth_deactivate_direct_login_by_portal_id', $config['login']['shibboleth_deactivate_direct_login_by_portal_id']);

        // security
        $container->setParameter('commsy.security.privacy_disable_overwriting', $config['security']['privacy_disable_overwriting']);
        $container->setParameter('commsy.security.allow_moderator_takeover', $config['security']['allow_moderator_takeover']);

        // themes
        $container->setParameter('commsy.themes.default', $config['themes']['default']);

        // wordpress
        $container->setParameter('commsy.wordpress.enabled', $config['wordpress']['enabled']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
