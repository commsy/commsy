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

namespace App\Metrics\Adapter;

use APCUIterator;
use Prometheus\Storage\APC;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class WipeableAPC extends APC
{
    public function __construct(
        private readonly string $prometheusPrefix
    ) {
        parent::__construct($prometheusPrefix);
    }

    public function wipeData(string $type, string $name): void
    {
        $match = sprintf('/^%1$s:%2$s:%1$s_%3$s.+/', $this->prometheusPrefix, $type, $name);

        foreach (new APCuIterator($match) as $key => $value) {
            apcu_delete($key);
        }
    }
}
