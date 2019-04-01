<?php

namespace App\Twig\Extension;

class LogarithmExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('log', [$this, 'logarithmFilter']),
        ];
    }

    public function logarithmFilter($arg, $base = M_E)
    {
        return log($arg, $base);
    }

    public function getName()
    {
        return 'logarithm_extension';
    }
}