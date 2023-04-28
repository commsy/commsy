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

namespace App\Search;

use App\Services\LegacyEnvironment;
use cs_environment;

class IndexableChecker
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function isIndexable($object)
    {
        // Call the objects isIndexable method
        if (!$object->isIndexable()) {
            return false;
        }

        // Check if this is a draft
        $itemManager = $this->legacyEnvironment->getItemManager();
        $item = $itemManager->getItem($object->getItemId());
        if ($item && $item->isDraft()) {
            return false;
        }

        return true;
    }
}
