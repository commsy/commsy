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
 * cache string for a single room.
 *
 * The cache holds a comma-separated list of moderator full names that
 * the UI shows on room cards / search results / room home pages. It
 * goes stale whenever a moderator's status changes or a moderator
 * leaves the room.
 *
 * Dispatching this message is the modernised replacement for legacy's
 * inline `cs_room_item::renewContactPersonString()` call inside
 * `cs_user_item::save()` / `::delete()`. Routed async via
 * `messenger.yaml` (`App\Message\*: async`) so the originating user
 * request returns without waiting for a moderator-list rebuild +
 * room save.
 */
final readonly class RefreshRoomContactPersonsMessage
{
    public function __construct(
        public int $roomId,
    ) {}
}
