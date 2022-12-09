<?php

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
    /**
     * @var LegacyEnvironment
     */
    private LegacyEnvironment $legacyEnvironment;

    /**
     * @var PortalRepository
     */
    private PortalRepository $portalRepository;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        PortalRepository $portalRepository
    ) {
        $this->legacyEnvironment = $legacyEnvironment;
        $this->portalRepository = $portalRepository;
    }

    /**
     * @param object $room
     * @return MessageInterface|null
     */
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

    /**
     * @param object $room
     * @return MessageInterface|null
     */
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
