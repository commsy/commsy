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

namespace App\Room;

/**
 * What a person may do in a room, as stored in user.status. The numbers are the
 * legacy ones {@see \cs_user_item} writes; they are given names here so callers
 * stop passing bare integers around.
 */
enum RoomMembershipStatus: int
{
    case Blocked = 0;
    case Requested = 1;
    case User = 2;
    case Moderator = 3;
    case ReadOnly = 4;
}
