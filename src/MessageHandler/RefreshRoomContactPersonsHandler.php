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
 * `room.contact_persons` cache string for a single room. Delegates to
 * legacy `cs_room_item::renewContactPersonString()`.
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
            // Room may have been hard-deleted between dispatch and handling.
            $this->logger->debug(
                'RefreshRoomContactPersonsHandler: room {id} not found, skipping cache refresh',
                ['id' => $message->roomId]
            );
            return;
        }

        // Portal / server items reuse cs_room_item but have no contact-persons UI.
        if ($room->isPortal() || $room->isServer()) {
            return;
        }

        $room->renewContactPersonString();
    }
}
