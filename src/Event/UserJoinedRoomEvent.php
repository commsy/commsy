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
 * Class UserJoinedRoomEvent.
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
