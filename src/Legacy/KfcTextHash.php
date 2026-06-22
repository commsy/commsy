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
 * Wraps editor content in the legacy "KFC TEXT" integrity markers.
 *
 * Interim Legacy->App bridge: holds the bodies of the former global functions
 * renewSecurityHash()/getSecurityHash() from legacy/functions/security_functions.php
 * so that file can be removed. Only the legacy cs_manager link-refresh path calls
 * this. The security key is read from the legacy global $c_security_key, defaulting
 * to "commsy" exactly as the original did.
 */
final class KfcTextHash
{
    /**
     * Strips any existing KFC markers from $value and re-wraps it with a marker
     * carrying the current content hash, at both ends.
     */
    public static function renew(string $value): string
    {
        $value = preg_replace('~<!-- KFC TEXT -->~u', '', $value);
        $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u', '', $value);

        $marker = '<!-- KFC TEXT '.self::hash($value).' -->';

        return $marker.$value.$marker;
    }

    private static function hash(string $value): string
    {
        $key = $GLOBALS['c_security_key'] ?? '';
        if (empty($key)) {
            $key = 'commsy';
        }

        return md5($key.$value.$key);
    }
}
