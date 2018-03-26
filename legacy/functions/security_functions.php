<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2008 Iver Jackewitz
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

// security functions to prevent session riding
// following the example of django
// http://code.djangoproject.com/browser/django/trunk/django/contrib/csrf/middleware.py

function getToken()
{
    global $environment;
    $session_item = $environment->getSessionItem();
    $session_id = $session_item->getSessionID();
    global $c_security_key;
    if (empty($c_security_key)) {
        $c_security_key = 'commsy';
    }
    $retour = md5($c_security_key . $session_id);
    return $retour;
}

function addTokenToPost($value)
{
    if (!empty($value)) {
        $value_temp = $value;
        // ------------------
        // --->UTF8 - OK<----
        // ------------------
        $pattern = '~<form[^>]*method=[\'|"|][p|P][o|O][s|S][t|T][\'|"|][^>]*>~u';
        $replace = '$0' . LF . '<div style=\'display:none;\'><input type=\'hidden\' name=\'security_token\' value=\'' . getToken() . '\'/></div>';
        $value = preg_replace($pattern, $replace, $value);
        if (empty($value)) {
            $value = $value_temp;
        }
    }
    return $value;
}

function getSecurityHash($value)
{
    global $c_security_key;
    if (empty($c_security_key)) {
        $c_security_key = 'commsy';
    }
    $retour = md5($c_security_key . $value . $c_security_key);
    return $retour;
}

function renewSecurityHash($value)
{
    $value = preg_replace('~<!-- KFC TEXT -->~u', '', $value);
    $value = preg_replace('~<!-- KFC TEXT [a-z0-9]* -->~u', '', $value);
    $fck_text = '<!-- KFC TEXT ' . getSecurityHash($value) . ' -->';
    $value = $fck_text . $value . $fck_text;
    return $value;
}

function mysql_escape_mimic($inp)
{
    if (is_array($inp)) {
        return array_map(__METHOD__, $inp);
    }

    if (!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }

    return $inp;
}