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
 * This event is fired when the status of a user object has been updated.
 *
 * Class UserStatusChangedEvent
 */
class UserStatusChangedEvent extends Event
{
    /**
     * @var cs_user_item The new user object
     */
    private $user;

    public function __construct(cs_user_item $user)
    {
        $this->user = $user;
    }

    public function getUser(): cs_user_item
    {
        return $this->user;
    }
}
