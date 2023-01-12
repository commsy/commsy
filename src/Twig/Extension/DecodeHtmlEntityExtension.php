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

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class DecodeHtmlEntityExtension.
 *
 * Simple Twig filter for decoding html entities.
 */
class DecodeHtmlEntityExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('decodeHtmlEntity', [$this, 'decodeHtmlEntity']),
        ];
    }

    public function decodeHtmlEntity($arg)
    {
        return html_entity_decode($arg);
    }
}
