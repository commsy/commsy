<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.06.17
 * Time: 00:59
 */

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class DecodeHtmlEntityExtension
 *
 * Simple Twig filter for decoding html entities.
 *
 * @package App\Twig\Extension
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