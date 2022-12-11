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

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RoomSettingsChanged.
 */
class RoomSettingsChangedEvent extends Event
{
    /**
     * @var \cs_room_item The unchanged room object
     */
    private $oldRoom;

    /**
     * @var \cs_room_item The new room object
     */
    private $newRoom;

    public function __construct(\cs_room_item $oldRoom, \cs_room_item $newRoom)
    {
        $this->oldRoom = $oldRoom;
        $this->newRoom = $newRoom;
    }

    public function getOldRoom(): \cs_room_item
    {
        return $this->oldRoom;
    }

    public function getNewRoom(): \cs_room_item
    {
        return $this->newRoom;
    }
}
