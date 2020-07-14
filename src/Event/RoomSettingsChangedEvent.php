<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class RoomSettingsChanged
 * @package App\Event
 *
 * This event is fired when settings inside a room are changed.
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