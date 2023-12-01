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

/** returns a string that is x characters at the most but won't
 *  break in the middle of a word.
 *
 * @param text that uld be chunked
 * @param length size of the caracters
 *
 * @return array retour_array the prepared array
 */
function chunkText($text, $length)
{
    $first_tag = '(:';
    $last_tag = ':)';

    $text = trim((string) $text);
    $mySubstring = preg_replace('~^(.{1,$length})[ .,].*~u', '\\1', $text); // ???
    if (mb_strlen($mySubstring) > $length) {
        $mySubstring = mb_substr($text, 0, $length);
        if (strstr($text, $first_tag)
             and strstr($text, $last_tag)
        ) {
            if (mb_strrpos($mySubstring, $last_tag) < mb_strrpos($mySubstring, $first_tag)) {
                $mySubstring2 = mb_substr($text, $length);
                $mySubstring .= mb_substr($mySubstring2, 0, mb_strpos($mySubstring2, $last_tag) + 2);
                $mySubstring .= ' ';
            }
        }
        if (strstr($mySubstring, ' ')) {
            $mySubstring = mb_substr($mySubstring, 0, mb_strrpos($mySubstring, ' '));
        }
        $mySubstring .= ' ...';
    }
    $mySubstring = preg_replace('~\n~u', ' ', $mySubstring);

    return $mySubstring;
}

/** returns an URL that is x characters at the most
 *  special needed for _activate_urls in cs_view.php
 *  in a preg_replace_callback - function.
 *
 * @param array from preg_replace_function
 *
 * @return text for replacement in preg_replace_function
 */
function spezial_chunkURL(array $text): string
{
    // ------------------
    // --->UTF8 - OK<----
    // ------------------
    $text = $text[1];
    $text = chunkText($text, 45);

    return '">'.$text.'</a>';
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
