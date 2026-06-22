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
 * Decodes text loaded from the legacy message ".dat" files.
 *
 * Interim Legacy->App bridge: replaces the FROM_FILE branch of the former global
 * encode() (legacy/functions/text_functions.php), which mapped "&quot;" back to
 * '"' across the (possibly nested) message array. The other encode() modes did
 * not need the text converter either: AS_DB is just SQL escaping
 * ({@see SqlStringEscaper}) and FROM_DB is a no-op.
 */
final class LegacyTextDecode
{
    public static function fromFile(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'fromFile'], $value);
        }

        if (empty($value)) {
            return $value;
        }

        return str_replace('&quot;', '"', (string) $value);
    }
}
