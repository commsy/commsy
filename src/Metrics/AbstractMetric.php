<?php

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use Prometheus\Storage\APC;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractMetric
{
    private string $cacheKey;

    /**
     * @required
     */
    public function setCacheKey(ParameterBagInterface $params)
    {
        $this->cacheKey = $params->get('commsy.metrics.cache_namespace');
    }

    /**
     * @return string
     */
    protected function getNamespace(): string
    {
        return 'commsy';
    }

    /**
     * @return RegistryInterface
     */
    public function getCollectorRegistry(): RegistryInterface
    {
        return new CollectorRegistry(new APC($this->cacheKey));
    }
}
