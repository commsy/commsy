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

namespace App\MessageHandler;

use App\Message\RefreshRoomContactPersonsMessage;
use App\Services\LegacyEnvironment;
use cs_room_item;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handles {@see RefreshRoomContactPersonsMessage}: rebuilds the
 * `room.contact_persons` cache string for a single room, so the UI
 * stops listing moderators that have left or had their status changed.
 *
 * Implemented on top of legacy `cs_room_item::renewContactPersonString()`
 * — that method already does exactly what we need (empty the column,
 * iterate the contact-moderator list, write back without bumping the
 * room's `modification_date`). Wrapping it in an async handler buys us
 * two things over the inline legacy call:
 *  - the originating user request (e.g. "leave workspace") returns
 *    immediately, without waiting for a moderator-list query + room
 *    save round-trip,
 *  - the call site no longer needs to know whether the context is a
 *    real room, a portal, or the server item — that check moves into
 *    the handler where it belongs.
 */
#[AsMessageHandler]
readonly class RefreshRoomContactPersonsHandler
{
    public function __construct(
        private LegacyEnvironment $legacyEnvironment,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(RefreshRoomContactPersonsMessage $message): void
    {
        $env = $this->legacyEnvironment->getEnvironment();
        $roomManager = $env->getRoomManager();
        $room = $roomManager->getItem($message->roomId);

        if (!$room instanceof cs_room_item) {
            // Room may have been hard-deleted between dispatch and
            // handling — treat as a no-op rather than a failure.
            $this->logger->debug(
                'RefreshRoomContactPersonsHandler: room {id} not found, skipping cache refresh',
                ['id' => $message->roomId]
            );
            return;
        }

        // Portal / server items reuse the `cs_room_item` base but have
        // no contact-persons UI; their context_id never appears as a
        // `user.context_id` from a real membership delete, but defend
        // against future call sites that might dispatch indiscriminately.
        if ($room->isPortal() || $room->isServer()) {
            return;
        }

        $room->renewContactPersonString();
    }
}
