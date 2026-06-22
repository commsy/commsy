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

function encode($mode, $value)
{
    $retour = $value;
    global $environment;
    if (!empty($environment)) {
        $text_converter = $environment->getTextConverter();
        $retour = $text_converter->encode($mode, $value);
        unset($text_converter);
    } else {
        trigger_error('can not encode data', E_USER_WARNING);
    }

    return $retour;
}

/**
 * Extended implementation of the standard PHP-Function.
 *
 * Needed to ensure proper searching in CommSy with standard PHP settings
 * When the 'locale' setting of PHP is not set properly, the search for language specific characters
 * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtoupper doesn't translate
 * them (http://de3.php.net/manual/en/function.strtoupper.php)
 *
 * Our extended implementation translates correct without respect to 'locale'
 */
function cs_strtoupper($value): string
{
    return mb_strtoupper(strtr($value, LC_CHARS, UC_CHARS), 'UTF-8');
}

/**
 * Extended implementation of the standard PHP-Function.
 *
 * Needed to ensure proper searching in CommSy with standard PHP settings
 * When the 'locale' setting of PHP is not set properly, the search for language specific characters
 * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtolower doesn't translate
 * them (http://de3.php.net/manual/en/function.strtolower.php)
 *
 * Our extended implementation translates correct without respect to 'locale'
 */
function cs_strtolower($value): string
{
    return mb_strtolower(strtr($value, UC_CHARS, LC_CHARS), 'UTF-8');
}
