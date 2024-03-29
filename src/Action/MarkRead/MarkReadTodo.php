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

use App\Utils\TodoService;
use cs_item;

class MarkReadTodo implements MarkReadInterface
{
    public function __construct(private readonly TodoService $todoService)
    {
    }

    public function markRead(cs_item $item): void
    {
        $this->todoService->markTodoReadAndNoticed($item->getItemId());
    }
}
