<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the status of a user object has been updated.
 * 
 * Class UserStatusChangedEvent
 * @package App\Event
 */
class UserStatusChangedEvent extends Event
{
    /**
     * @var \cs_user_item The new user object
     */
    private $user;

    public function __construct(\cs_user_item $user)
    {
        $this->user = $user;
    }

    public function getUser(): \cs_user_item
    {
        return $this->user;
    }
}