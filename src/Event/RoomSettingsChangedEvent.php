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

namespace App\Event;

use cs_room_item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RoomSettingsChanged.
 */
class RoomSettingsChangedEvent extends Event
{
    public function __construct(
        /**
         * @var cs_room_item The unchanged room object
         */
        private readonly cs_room_item $oldRoom,
        /**
         * @var cs_room_item The new room object
         */
        private readonly cs_room_item $newRoom
    )
    {
    }

    public function getOldRoom(): cs_room_item
    {
        return $this->oldRoom;
    }

    public function getNewRoom(): cs_room_item
    {
        return $this->newRoom;
    }
}
