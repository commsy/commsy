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

use cs_user_item;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is fired when the user's account (i.e. the portal user item) is about to be deleted.
 *
 * Class AccountDeletedEvent
 */
class AccountDeletedEvent extends Event
{
    public function __construct(
        /**
         * @var cs_user_item The portal user item to be deleted
         */
        private readonly cs_user_item $portalUser
    )
    {
    }

    public function getPortalUser(): cs_user_item
    {
        return $this->portalUser;
    }
}
