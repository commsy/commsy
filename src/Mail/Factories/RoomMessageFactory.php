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
use App\Repository\PortalRepository;
use App\Services\LegacyEnvironment;
use LogicException;

class RoomMessageFactory
{
    public function __construct(private readonly LegacyEnvironment $legacyEnvironment, private readonly PortalRepository $portalRepository)
    {
    }

    public function createRoomActivityLockWarningMessage(object $room): ?MessageInterface
    {
        /** @var Room $room */
        if (!$room instanceof Room) {
            throw new LogicException('$room must be of type Room');
        }

        $portal = $this->portalRepository->find($room->getContextId());
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

        $portal = $this->portalRepository->find($room->getContextId());
        if ($portal) {
            return new RoomActivityDeleteWarningMessage($this->legacyEnvironment, $portal, $room);
        }

        return null;
    }
}
