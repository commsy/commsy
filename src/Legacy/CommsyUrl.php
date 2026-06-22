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
 * Builds the legacy single-entry-point URLs ("?cid=...&amp;mod=...&amp;fct=...")
 * and their <a> tags.
 *
 * Interim Legacy->App bridge replacing the former global functions ahref_curl()/
 * curl()/_curl() from legacy/functions/curl_functions.php. Only the legacy link
 * rendering (cs_link) and the legacy text auto-linker (misc_text_converter) call
 * this — modern code uses the Symfony router.
 *
 * Reduced to the parameters those callers actually use; the legacy variants
 * (redirect '&' separator, file/filehack overrides, name/style/jshack/id
 * attributes, empty-address mode) had no callers and were dropped.
 */
final class CommsyUrl
{
    /**
     * Builds an <a> tag for a legacy curl, or — in print mode — just the link text.
     *
     * $contextId, $module, $function and $parameter originate from untyped legacy
     * cs_item accessors and are therefore left untyped here.
     */
    public static function ahref(
        $contextId,
        $module,
        $function,
        $parameter,
        string $linktext,
        string $title = '',
        string $target = '',
        string $fragment = '',
    ): string {
        $href = self::url($contextId, $module, $function, $parameter, $fragment);

        $attributes = '';
        if ('' !== $title) {
            $attributes .= ' title="'.strip_tags($title).'"';
        }
        if ('' !== $target) {
            $attributes .= ' target="'.$target.'"';
        }

        $anchor = '<a href="'.$href.'"'.$attributes.'>'.$linktext.'</a>';

        // In print mode the link is rendered as plain text (except for zip downloads).
        if ('print' === ($_GET['mode'] ?? null) && 'zip' !== ($_GET['download'] ?? null)) {
            return $linktext;
        }

        return $anchor;
    }

    private static function url($contextId, $module, $function, $parameter, string $fragment = ''): string
    {
        // $c_single_entry_point is not set anywhere, so this is normally '' and the
        // result is a relative "?cid=..." URL — faithful to the legacy _curl().
        $address = $GLOBALS['c_single_entry_point'] ?? '';

        $address .= '?cid='.$contextId.'&amp;mod='.$module.'&amp;fct='.$function;

        if (is_array($parameter)) {
            foreach ($parameter as $key => $value) {
                $address .= '&amp;'.$key.'='.$value;
            }
        }

        if ('' !== $fragment) {
            $address .= '#'.$fragment;
        }

        return $address;
    }
}
