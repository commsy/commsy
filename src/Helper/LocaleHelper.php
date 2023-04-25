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

namespace App\Helper;

use Symfony\Component\Translation\LocaleSwitcher;

class LocaleHelper
{
    public function __construct(
        private readonly LocaleSwitcher $localeSwitcher
    ) {
    }

    public function getLocale(): string
    {
        return $this->localeSwitcher->getLocale();
    }
}
