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
use App\Services\CurrentUserResolver;
use App\Services\MarkedService;
use cs_item;

/**
 * Thin wrapper around {@see MaterialDeleter::softDeleteItem()} — drops
 * every version of the material (all versions).
 */
class DeleteMaterial implements DeleteInterface
{
    public function __construct(
        protected MarkedService $markedService,
        private readonly MaterialDeleter $materialDeleter,
        private readonly CurrentUserResolver $currentUserResolver,
    ) {
    }

    public function delete(cs_item $item): void
    {
        $deleterId = (int) ($this->currentUserResolver->getUser()?->getItemId() ?? 0);
        $this->materialDeleter->softDeleteItem((int) $item->getItemId(), $deleterId);

        $this->markedService->removeItemFromClipboard($item->getItemId());
    }

    public function getRedirectRoute(cs_item $item): ?string
    {
        return null;
    }
}
