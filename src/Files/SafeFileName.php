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

declare(strict_types=1);

namespace App\Files;

/**
 * Turns a title or a file name into a single path segment: no separators,
 * no control characters, no leading dots, so it can neither climb out of
 * nor reach into another directory.
 */
final class SafeFileName
{
    private const int MAX_BYTES = 200;

    public static function of(string $name, string $fallback = 'entry'): string
    {
        $name = (string) preg_replace('/[\/\\\\\x00-\x1f\x7f]+/u', '_', $name);
        $name = ltrim(trim($name), '.');
        $name = trim(mb_strcut($name, 0, self::MAX_BYTES, 'UTF-8'));

        return '' === $name ? $fallback : $name;
    }
}
