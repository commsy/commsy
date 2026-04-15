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

use App\Rubric\Material\MaterialDeleter;
use App\Services\LegacyEnvironment;
use App\Services\MarkedService;
use cs_environment;
use cs_item;

/**
 * Thin wrapper around {@see MaterialDeleter::deleteItem()} — the generic
 * "Material wegwerfen" UI path drops every version of the material
 * (CS_ALL-Semantik), including all section versions and versioned file
 * attachments. A dedicated "delete only the latest version" action may be
 * added later; for now `MaterialDeleter::deleteCurrentVersion()` exists but
 * is not wired to a UI trigger.
 */
class DeleteMaterial implements DeleteInterface
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        protected MarkedService $markedService,
        private readonly MaterialDeleter $materialDeleter,
        LegacyEnvironment $legacyEnvironment,
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function delete(cs_item $item): void
    {
        $deleterId = (int) $this->legacyEnvironment->getCurrentUserItem()?->getItemID();
        $this->materialDeleter->deleteItem((int) $item->getItemId(), $deleterId);

        $this->markedService->removeItemFromClipboard($item->getItemId());
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        return null;
    }
}
