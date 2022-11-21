<?php

namespace App\Metrics;

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
}
