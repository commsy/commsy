<?php


namespace App\Event;


use cs_room_item;
use cs_user_item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserLeftRoomEvent
 * @package App\Event
 *
 * This event is fired when a user left a workspace.
 * This is true for existing as well es deleted rooms.
 */
class UserLeftRoomEvent extends Event
{
    /**
     * @var cs_user_item
     */
    private cs_user_item $user;

    /**
     * @var cs_room_item
     */
    private cs_room_item $room;

    public function __construct(cs_user_item $user, cs_room_item $room)
    {
        $this->user = $user;
        $this->room = $room;
    }

    public function getUser(): cs_user_item
    {
        return $this->user;
    }

    public function getRoom(): cs_room_item
    {
        return $this->room;
    }
}