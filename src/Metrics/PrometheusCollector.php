<?php

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use Prometheus\Storage\APC;

class PrometheusCollector
{
    /**
     * @var MetricInterface[]
     */
    private iterable $metrics;

    public function __construct(iterable $metrics)
    {
        $this->metrics = $metrics;
    }

    public function updateMetrics()
    {
        foreach ($this->metrics as $metric) {
            $metric->update();
        }
    }

    public static function getCollectorRegistry(): RegistryInterface
    {
        return new CollectorRegistry(new APC());
    }
}
