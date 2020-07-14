<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserLeftWorkspaceEvent
 * @package App\Event
 *
 * This event ist fired when a user left a workspace.
 * This is true for existing as well es deleted rooms.
 */
class UserLeftWorkspaceEvent extends Event
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