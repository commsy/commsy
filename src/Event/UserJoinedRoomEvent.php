<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserJoinedRoomEvent
 * @package App\Event
 *
 * This event is fired when a new user joined a workspace.
 * This is true for existing workspaces as well as new workspaces.
 */
class UserJoinedRoomEvent extends Event
{
    /**
     * @var \cs_user_item
     */
    private $user;

    /**
     * @var \cs_room_item
     */
    private $room;

    public function __construct(\cs_user_item $user, \cs_room_item $room)
    {
        $this->user = $user;
        $this->room = $room;
    }

    public function getUser(): \cs_user_item
    {
        return $this->user;
    }

    public function getRoom(): \cs_room_item
    {
        return $this->room;
    }
}