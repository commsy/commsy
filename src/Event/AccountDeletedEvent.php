<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the user's account (i.e. the portal user item) is about to be deleted.
 *
 * Class AccountDeletedEvent
 * @package App\Event
 */
class AccountDeletedEvent extends Event
{
    /**
     * @var \cs_user_item The portal user item to be deleted
     */
    private $portalUser;

    public function __construct(\cs_user_item $portalUser)
    {
        $this->portalUser = $portalUser;
    }

    public function getPortalUser(): \cs_user_item
    {
        return $this->portalUser;
    }
}
