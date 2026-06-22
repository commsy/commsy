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

namespace App\Utils;

/**
 * Locale-independent upper-/lower-casing.
 *
 * Replaces the legacy global functions cs_strtoupper()/cs_strtolower() from
 * legacy/functions/text_functions.php. The two-step conversion (a byte-wise
 * strtr() over the Latin-1 supplement range followed by mb_*case) is kept
 * identical to the legacy behaviour so callers see the same result regardless
 * of the runtime locale.
 */
final class StringCase
{
    private const string LOWER_CHARS = 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþ';
    private const string UPPER_CHARS = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞ';

    public static function toUpper(string $value): string
    {
        return mb_strtoupper(strtr($value, self::LOWER_CHARS, self::UPPER_CHARS), 'UTF-8');
    }

    public static function toLower(string $value): string
    {
        return mb_strtolower(strtr($value, self::UPPER_CHARS, self::LOWER_CHARS), 'UTF-8');
    }
}
