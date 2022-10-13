<?php

namespace App\Metrics;

interface MetricInterface
{
    public function update(): void;
}
