<?php

namespace App\Metrics;

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

    protected function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}
