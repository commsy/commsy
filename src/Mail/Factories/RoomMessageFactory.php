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

namespace App\Mail\Factories;

use App\Entity\Room;
use App\Mail\MessageInterface;
use App\Mail\Messages\RoomActivityDeleteWarningMessage;
use App\Mail\Messages\RoomActivityLockWarningMessage;
use App\Mail\Messages\UserJoinedContextMessage;
use App\Repository\PortalRepository;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use cs_user_item;
use LogicException;

class RoomMessageFactory
{
    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly PortalRepository $portalRepository,
        private readonly CurrentUserResolver $currentUserResolver
    ) {
    }

    public function createRoomActivityLockWarningMessage(object $room): ?MessageInterface
    {
        /** @var Room $room */
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        $portal = $room->getPortal();
        if ($portal) {
            return new RoomActivityLockWarningMessage($this->legacyEnvironment, $portal, $room);
        }

        return null;
    }

    public function createRoomActivityDeleteWarningMessage(object $room): ?MessageInterface
    {
        /** @var Room $room */
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        $portal = $room->getPortal();
        if ($portal) {
            return new RoomActivityDeleteWarningMessage($this->legacyEnvironment, $portal, $room);
        }

        return null;
    }

    public function createUserJoinedContextMessage(Room $room, cs_user_item $newUser, ?string $comment): MessageInterface
    {
        $portal = $room->getPortal();
        return new UserJoinedContextMessage($this->currentUserResolver, $portal, $room, $newUser, $comment);
    }
}
