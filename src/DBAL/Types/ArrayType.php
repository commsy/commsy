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

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ArrayType as BaseType;
use Doctrine\DBAL\Types\ConversionException;

class ArrayType extends BaseType
{
    public const MBARRAY = 'mb_array';

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return [];
        }

        $value = preg_replace_callback('/s:(\d+):"(.*?)";(?=\}|i|s|a)/s', function ($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, $value);

        $val = @unserialize($value);
        if (false === $val && 'b:0;' != $value) {
            // TODO: this is temporary, we need to fix db entries
            return [];

            // throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::MBARRAY;
    }
}
