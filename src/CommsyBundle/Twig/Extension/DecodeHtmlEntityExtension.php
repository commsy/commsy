<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 24.06.17
 * Time: 00:59
 */

namespace CommsyBundle\Twig\Extension;

/**
 * Class DecodeHtmlEntityExtension
 *
 * Simple Twig filter for decoding html entities.
 *
 * @package CommsyBundle\Twig\Extension
 */
class DecodeHtmlEntityExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('decodeHtmlEntity', [$this, 'decodeHtmlEntity']),
        ];
    }

    public function decodeHtmlEntity($arg)
    {
        return html_entity_decode($arg);
    }

    public function getName()
    {
        return 'decodeHtmlEntity_extension';
    }
}