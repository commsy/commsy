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

// security functions to prevent session riding
// following the example of django
// http://code.djangoproject.com/browser/django/trunk/django/contrib/csrf/middleware.py

function getSecurityHash($value)
{
    global $c_security_key;
    if (empty($c_security_key)) {
        $c_security_key = 'commsy';
    }
    $retour = md5($c_security_key.$value.$c_security_key);

    return $retour;
}

function renewSecurityHash($value)
{
    $value = preg_replace('~<!-- KFC TEXT -->~u', '', (string) $value);
    $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u', '', $value);
    $fck_text = '<!-- KFC TEXT '.getSecurityHash($value).' -->';
    $value = $fck_text.$value.$fck_text;

    return $value;
}

function mysql_escape_mimic($inp)
{
    if (is_array($inp)) {
        return array_map(__METHOD__, $inp);
    }

    if (!empty($inp) && is_string($inp)) {
        return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $inp);
    }

    return $inp;
}
