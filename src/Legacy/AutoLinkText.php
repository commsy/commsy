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
 * Shortens the visible text of auto-generated links in rendered content.
 *
 * Interim Legacy->App bridge: holds the bodies of the former global functions
 * chunkText()/spezial_chunkURL() from legacy/functions/text_functions.php so
 * those can be removed. Behaviour is preserved exactly (see AutoLinkTextTest).
 *
 * Note: this is deliberately NOT Symfony String's u()->truncate(); that shortens
 * differently (it leaves long URLs — the main case here — untouched).
 */
final class AutoLinkText
{
    /**
     * preg_replace_callback handler for the pattern ~">(.[^"]+)</a>~u: shortens
     * the link text captured in group 1. Was the global spezial_chunkURL().
     *
     * @param array<int, string> $match
     */
    public static function shortenAnchorText(array $match): string
    {
        return '">'.self::shorten($match[1]).'</a>';
    }

    /**
     * Truncates $text to at most $length characters without breaking the final
     * word, appending " ..." when shortened. Was the global chunkText().
     */
    public static function shorten(string $text, int $length = 45): string
    {
        $text = trim($text);
        $result = $text;

        if (mb_strlen($result) > $length) {
            $result = mb_substr($text, 0, $length);

            // Keep a CommSy "(: ... :)" tag intact if the cut fell inside one.
            if (str_contains($text, '(:') && str_contains($text, ':)')
                && mb_strrpos($result, ':)') < mb_strrpos($result, '(:')
            ) {
                $rest = mb_substr($text, $length);
                $result .= mb_substr($rest, 0, mb_strpos($rest, ':)') + 2).' ';
            }

            if (str_contains($result, ' ')) {
                $result = mb_substr($result, 0, mb_strrpos($result, ' '));
            }

            $result .= ' ...';
        }

        return str_replace("\n", ' ', $result);
    }
}
