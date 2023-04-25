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
function spezial_chunkURL($text)
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
function cs_strtoupper($value)
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
function cs_strtolower($value)
{
    return mb_strtolower(strtr($value, UC_CHARS, LC_CHARS), 'UTF-8');
}

function mb_unserialize($serial_str)
{
    $retour = @unserialize($serial_str);
    if (empty($retour)) {
        $serial_str = preg_replace_callback('/s:(\d+):"(.*?)";/s', function ($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, (string) $serial_str);

        $retour = @unserialize($serial_str);
        if (empty($retour)) {
            $retour = @unserialize(_correct_a($serial_str));
        }
    }

    return $retour;
}

function _correct_a($value)
{
    $retour = $value;

    $found = [];
    preg_match_all('~a:([0-9]*):~', (string) $value, $found);
    if (!empty($found[1][0])) {
        $begin = substr((string) $value, 0, strpos((string) $value, '{') + 1);
        $middle = substr((string) $value, strpos((string) $value, '{') + 1, strrpos((string) $value, '}') - strpos((string) $value, '{') - 1);
        $end = substr((string) $value, strrpos((string) $value, '}'));
        if ((is_countable($found[1]) ? count($found[1]) : 0) > 1) {
            $middle = _correct_a($middle);
        }
        $count_sem = 0;
        $count_klam = 0;
        for ($i = 0; $i < strlen((string) $middle); ++$i) {
            if (0 == $count_klam
                 and ';' == $middle[$i]
            ) {
                $count_sem = $count_sem + 0.5;
            }
            if ('{' == $middle[$i]) {
                ++$count_klam;
            } elseif ('}' == $middle[$i]) {
                --$count_klam;
            }
        }
        if ($count_sem == round($count_sem, 0)
             and $count_sem != $found[1][0]
        ) {
            $begin = str_replace($found[1][0], $count_sem, $begin);
            $retour = $begin.$middle.$end;
        }
    }

    return $retour;
}

function cs_ucfirst($text)
{
    $return_text = mb_strtoupper(mb_substr((string) $text, 0, 1, 'UTF-8'), 'UTF-8');

    return $return_text.mb_substr((string) $text, 1, mb_strlen((string) $text, 'UTF-8'), 'UTF-8');
}

// von http://de3.php.net/sprintf
if (!function_exists('mb_sprintf')) {
    function mb_sprintf($format)
    {
        $argv = func_get_args();
        array_shift($argv);

        return mb_vsprintf($format, $argv);
    }
}
if (!function_exists('mb_vsprintf')) {
    /**
     * Works with all encodings in format and arguments.
     * Supported: Sign, padding, alignment, width and precision.
     * Not supported: Argument swapping.
     */
    function mb_vsprintf($format, $argv, $encoding = null)
    {
        if (is_null($encoding)) {
            $encoding = mb_internal_encoding();
        }

        // Use UTF-8 in the format so we can use the u flag in preg_split
        $format = mb_convert_encoding((string) $format, 'UTF-8', $encoding);

        $newformat = ''; // build a new format in UTF-8
        $newargv = []; // unhandled args in unchanged encoding

        while ('' !== $format) {
            // Split the format in two parts: $pre and $post by the first %-directive
            // We get also the matched groups
            [$pre, $sign, $filler, $align, $size, $precision, $type, $post] =
                preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",
                    $format, 2, PREG_SPLIT_DELIM_CAPTURE);

            $newformat .= mb_convert_encoding($pre, $encoding, 'UTF-8');

            if ('' == $type) {
                // didn't match. do nothing. this is the last iteration.
            } elseif ('%' == $type) {
                // an escaped %
                $newformat .= '%%';
            } elseif ('s' == $type) {
                $arg = array_shift($argv);
                $arg = mb_convert_encoding((string) $arg, 'UTF-8', $encoding);
                $padding_pre = '';
                $padding_post = '';

                // truncate $arg
                if ('' !== $precision) {
                    $precision = intval(substr($precision, 1));
                    if ($precision > 0 && mb_strlen($arg, $encoding) > $precision) {
                        $arg = mb_substr($precision, 0, $precision, $encoding);
                    }
                }

                // define padding
                if ($size > 0) {
                    $arglen = mb_strlen($arg, $encoding);
                    if ($arglen < $size) {
                        if ('' === $filler) {
                            $filler = ' ';
                        }
                        if ('-' == $align) {
                            $padding_post = str_repeat($filler, $size - $arglen);
                        } else {
                            $padding_pre = str_repeat($filler, $size - $arglen);
                        }
                    }
                }

                // escape % and pass it forward
                $newformat .= $padding_pre.str_replace('%', '%%', $arg).$padding_post;
            } else {
                // another type, pass forward
                $newformat .= "%$sign$filler$align$size$precision$type";
                $newargv[] = array_shift($argv);
            }
            $format = strval($post);
        }
        // Convert new format back from UTF-8 to the original encoding
        $newformat = mb_convert_encoding($newformat, $encoding, 'UTF-8');

        return vsprintf($newformat, $newargv);
    }
}

function cs_utf8_encode($value)
{
    if (mb_check_encoding($value, 'UTF-8')) {
        return $value;
    } elseif (mb_check_encoding($value, 'ISO-8859-1')) {
        return mb_convert_encoding((string) $value, 'UTF-8', 'ISO-8859-1');
    }
}
