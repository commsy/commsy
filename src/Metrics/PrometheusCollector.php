<?php

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use Prometheus\Storage\APC;

class PrometheusCollector
{
    public static function getCollectorRegistry(): RegistryInterface
    {
        return new CollectorRegistry(new APC());
    }
}
