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

namespace App\Message;

/**
 * Asks a worker to refresh the denormalised `room.contact_persons`
 * cache string (comma-separated moderator names shown in the UI) for a
 * single room. Async replacement for the legacy inline
 * `cs_room_item::renewContactPersonString()` call.
 */
final readonly class RefreshRoomContactPersonsMessage
{
    public function __construct(
        public int $roomId,
    ) {}
}
