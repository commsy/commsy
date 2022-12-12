<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RegistryInterface;
use Prometheus\Storage\APC;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractMetric
{
    private string $cacheKey;

    #[Required]
    public function setCacheKey(ParameterBagInterface $params)
    {
        $this->cacheKey = $params->get('commsy.metrics.cache_namespace');
    }

    protected function getNamespace(): string
    {
        return 'commsy';
    }

    public function getCollectorRegistry(): RegistryInterface
    {
        return new CollectorRegistry(new APC($this->cacheKey));
    }
}
