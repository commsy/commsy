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

use function Symfony\Component\String\u;

/**
 * Locale-independent upper-/lower-casing.
 *
 * Replaces the legacy global functions cs_strtoupper()/cs_strtolower() from
 * legacy/functions/text_functions.php. Uses Symfony's String component, whose
 * case mapping is Unicode-aware and locale-independent — the property the
 * legacy strtr()/mb_*case() dance hand-rolled.
 */
final class StringCase
{
    public static function toUpper(string $value): string
    {
        return u($value)->upper()->toString();
    }

    public static function toLower(string $value): string
    {
        return u($value)->lower()->toString();
    }
}
