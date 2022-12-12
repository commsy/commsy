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

namespace App\Action\Delete;

use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_environment;
use cs_item;

class DeleteGeneric implements DeleteInterface
{
    protected cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        protected MarkedService $markedService
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $item->delete();

        $this->markedService->removeItemFromClipboard($item->getItemId());
    }

    /**
     * @return string
     */
    public function getRedirectRoute(cs_item $item)
    {
        return null;
    }
}
