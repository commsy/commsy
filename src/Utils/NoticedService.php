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

use App\Services\LegacyEnvironment;
use cs_noticed_manager;

class NoticedService
{
    private cs_noticed_manager $noticedManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->noticedManager = $legacyEnvironment->getEnvironment()->getNoticedManager();
    }

    public function getLatestNoticedByIDArrayAndUser($id_array, $user_id)
    {
        return $this->noticedManager->getLatestNoticedByIDArrayAndUser($id_array, $user_id);
    }
}
