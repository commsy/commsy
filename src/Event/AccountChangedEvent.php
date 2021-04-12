<?php


namespace App\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the user's account has been updated.
 *
 * Class AccountChangedEvent
 * @package App\Event
 */
class AccountChangedEvent extends Event
{
    /**
     * @var \cs_user_item The unchanged item
     */
    private $oldAccount;

    /**
     * @var \cs_user_item The updated item
     */
    private $newAccount;

    public function __construct(\cs_user_item $oldAccount, \cs_user_item $newAccount)
    {
        $this->oldAccount = $oldAccount;
        $this->newAccount = $newAccount;
    }

    /**
     * @return \cs_user_item
     */
    public function getOldAccount(): \cs_user_item
    {
        return $this->oldAccount;
    }

    /**
     * @return \cs_user_item
     */
    public function getNewAccount(): \cs_user_item
    {
        return $this->newAccount;
    }
}