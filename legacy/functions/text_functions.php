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
