<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LogarithmExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('log', [$this, 'logarithmFilter']),
        ];
    }

    public function logarithmFilter($arg, $base = M_E)
    {
        return log($arg, $base);
    }
}