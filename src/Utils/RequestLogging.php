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

namespace App\Utils;

interface RequestLogging
{
    public const ROOM_CONTEXT_IGNORE_REGEX_ARRAY = [
        '~\/room\/(\d)+\/user\/(\d)+\/image~',
        '~\/room\/(\d)+\/theme\/background~',
        '~\/room\/(\d)+\/logo~',
        '~\/_wdt~',
    ];
}
