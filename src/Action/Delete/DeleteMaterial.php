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

use App\Services\MarkedService;
use cs_item;

class DeleteMaterial implements DeleteInterface
{
    public function __construct(protected MarkedService $markedService)
    {
    }

    public function delete(cs_item $item): void
    {
        /** \cs_material_item $material */
        $material = $item;

        $material->deleteAllVersions();

        $this->markedService->removeItemFromClipboard($material->getItemId());
    }

    /**
     * @return string
     */
    public function getRedirectRoute(cs_item $item)
    {
        return null;
    }
}
