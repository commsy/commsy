<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Liip\ThemeBundle\LiipThemeBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Lexik\Bundle\FormFilterBundle\LexikFormFilterBundle(),
            new FOS\ElasticaBundle\FOSElasticaBundle(),
            new Ivory\OrderedFormBundle\IvoryOrderedFormBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new Craue\TwigExtensionsBundle\CraueTwigExtensionsBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new Debril\RssAtomBundle\DebrilRssAtomBundle(),
            new IDCI\Bundle\ColorSchemeBundle\IDCIColorSchemeBundle(),
            new Circle\RestClientBundle\CircleRestClientBundle(),
            new WhiteOctober\BreadcrumbsBundle\WhiteOctoberBreadcrumbsBundle(),

            new CommsyBundle\CommsyBundle(),
            new Commsy\LegacyBundle\CommsyLegacyBundle(),
            new EtherpadBundle\EtherpadBundle(),
            new CommsyMediawikiBundle\CommsyMediawikiBundle(),
        ];

        if (in_array($this->getEnvironment(), array('dev', 'test'), true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        return dirname(__DIR__).'/var/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');

        // Symfony env variables are overwritten by parameters of the same name
        // in parameters.yml, see https://github.com/symfony/symfony/issues/7555
        // 
        // The following is a temporary workaround:
        $envParameters = $this->getEnvParameters();
        $loader->load(function($container) use($envParameters) {
            $container->getParameterBag()->add($envParameters);
        });
    }
}