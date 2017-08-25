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

        // email
        $container->setParameter('commsy.email.from', $config['email']['from']);
        $container->setParameter('commsy.email.upload.enabled', $config['upload']['enabled']);
        $container->setParameter('commsy.email.upload.server', $config['upload']['server']);
        $container->setParameter('commsy.email.upload.port', $config['upload']['port']);
        $container->setParameter('commsy.email.upload.options', $config['upload']['options']);
        $container->setParameter('commsy.email.upload.account', $config['upload']['account']);
        $container->setParameter('commsy.email.upload.password', $config['upload']['password']);

        // login
        $container->setParameter('commsy.login.shibboleth_direct_login', $config['login']['shibboleth_direct_login']);
        $container->setParameter('commsy.login.shibboleth_redirect_url', $config['login']['shibboleth_redirect_url']);
        $container->setParameter('commsy.login.shibboleth_deactivate_direct_login_by_portal_id', $config['login']['shibboleth_deactivate_direct_login_by_portal_id']);

        // security
        $container->setParameter('commsy.security.privacy_disable_overwriting', $config['security']['privacy_disable_overwriting']);
        $container->setParameter('commsy.security.allow_moderator_takeover', $config['security']['allow_moderator_takeover']);

        // settings
        $container->setParameter('commsy.settings.export_temp_folder', $config['settings']['export_temp_folder']);
        $container->setParameter('commsy.settings.delete_days', $config['settings']['delete_days']);
        $container->setParameter('commsy.settings.session_lifetime', $config['settings']['session_lifetime']);
        $container->setParameter('commsy.settings.proxy_ip', $config['settings']['proxy_ip']);
        $container->setParameter('commsy.settings.proxy_port', $config['settings']['proxy_port']);
        $container->setParameter('commsy.settings.single_cat_selection', $config['settings']['single_cat_selection']);
        $container->setParameter('commsy.settings.item_locking', $config['settings']['item_locking']);
        $container->setParameter('commsy.settings.return_path_mail_address', $config['settings']['return_path_mail_address']);

        // themes
        $container->setParameter('commsy.themes.default', $config['themes']['default']);
        $container->setParameter('commsy.themes.cs_date_color_no_color', $config['themes']['cs_date_color_no_color']);
        $container->setParameter('commsy.themes.cs_date_color_01', $config['themes']['cs_date_color_01']);
        $container->setParameter('commsy.themes.cs_date_color_02', $config['themes']['cs_date_color_02']);
        $container->setParameter('commsy.themes.cs_date_color_03', $config['themes']['cs_date_color_03']);
        $container->setParameter('commsy.themes.cs_date_color_04', $config['themes']['cs_date_color_04']);
        $container->setParameter('commsy.themes.cs_date_color_05', $config['themes']['cs_date_color_05']);
        $container->setParameter('commsy.themes.cs_date_color_06', $config['themes']['cs_date_color_06']);
        $container->setParameter('commsy.themes.cs_date_color_07', $config['themes']['cs_date_color_07']);
        $container->setParameter('commsy.themes.cs_date_color_08', $config['themes']['cs_date_color_08']);
        $container->setParameter('commsy.themes.cs_date_color_09', $config['themes']['cs_date_color_09']);
        $container->setParameter('commsy.themes.cs_date_color_10', $config['themes']['cs_date_color_10']);

        // wordpress
        $container->setParameter('commsy.wordpress.enabled', $config['wordpress']['enabled']);

        // limesurvey
        $container->setParameter('commsy.limesurvey.enabled', $config['limesurvey']['enabled']);
        
        // mediawiki
        $container->setParameter('commsy.mediawiki.enabled', $config['mediawiki']['enabled']);
        $container->setParameter('commsy.mediawiki.url', $config['mediawiki']['url']);
        $container->setParameter('commsy.mediawiki.roomWikiUrl', $config['mediawiki']['roomWikiUrl']);
        $container->setParameter('commsy.mediawiki.apiPath', $config['mediawiki']['apiPath']);

        // etherpad
        $container->setParameter('commsy.etherpad.enabled', $config['etherpad']['enabled']);
        $container->setParameter('commsy.etherpad.api_key', $config['etherpad']['api_key']);
        $container->setParameter('commsy.etherpad.api_url', $config['etherpad']['api_url']);
        $container->setParameter('commsy.etherpad.base_url', $config['etherpad']['base_url']);

        // dates
        $container->setParameter('commsy.dates.timezone', $config['dates']['timezone']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }
}
