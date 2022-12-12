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

namespace App\Action\MarkRead;

use App\Utils\MaterialService;
use cs_item;

class MarkReadMaterial implements MarkReadInterface
{
    public function __construct(private MaterialService $materialService)
    {
    }

    public function markRead(cs_item $item): void
    {
        $this->materialService->markMaterialReadAndNoticed($item->getItemId());
    }
}
