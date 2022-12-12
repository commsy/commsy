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

function logToFile($message)
{
    if (is_array($message)) {
        logArrayToFile($message);
    }
}

function debugToFile($message)
{
    if (!is_array($message)) {
        logToFile('DEBUG --- '.$message);
    } else {
        debugArrayToFile($message);
    }
}

function logArrayToFile($array, $var_name = 'ARRAY')
{
    $keys = array_keys($array);
    foreach ($keys as $key) {
        $value = $array[$key];
        $name_length = strlen($var_name);
        $temp_name = '';
        for ($index = 0; $index < $name_length; ++$index) {
            $temp_name .= ' ';
        }
        if (is_array($value)) {
            logToFile($var_name);
            logArrayToFile($value, $temp_name.' [\''.$key.'\']');
        } else {
            logToFile($var_name.' [\''.$key.'\'] => '.$value);
        }
    }
}

function debugArrayToFile($array, $var_name = 'ARRAY')
{
    $keys = array_keys($array);
    foreach ($keys as $key) {
        $value = $array[$key];
        $name_length = strlen($var_name);
        $temp_name = '';
        for ($index = 0; $index < $name_length; ++$index) {
            $temp_name .= ' ';
        }
        if (is_array($value)) {
            logToFile('DEBUG --- '.$var_name);
            logArrayToFile($value, $temp_name.' [\''.$key.'\']');
        } else {
            logToFile('DEBUG --- '.$var_name.' [\''.$key.'\'] => '.$value);
        }
    }
}
