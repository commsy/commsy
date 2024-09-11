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

namespace App\Event\Workspace;

use cs_room_item;
use Symfony\Contracts\EventDispatcher\Event;

final class WorkspaceDeletedEvent extends Event
{
    public function __construct(
        private readonly cs_room_item $room
    ) {}

    public function getWorkspace(): cs_room_item
    {
        return $this->room;
    }
}
