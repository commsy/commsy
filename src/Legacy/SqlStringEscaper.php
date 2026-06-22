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

namespace App\Legacy;

/**
 * Escapes a value for interpolation into a raw MySQL query string.
 *
 * Interim Legacy->App bridge: holds the body of the former global function
 * mysql_escape_mimic() from legacy/functions/security_functions.php so that
 * file can be removed. Only the legacy db connector (which still concatenates
 * SQL) calls this — modern code uses parameterised queries and must not.
 */
final class SqlStringEscaper
{
    public static function escape(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'escape'], $value);
        }

        if (!empty($value) && is_string($value)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $value);
        }

        return $value;
    }
}
