<?php

namespace App\Mail\Factories;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Mail\MessageInterface;
use App\Mail\Messages\RoomActivityDeletedMessage;
use App\Mail\Messages\RoomActivityDeleteWarningMessage;
use App\Mail\Messages\RoomActivityLockedMessage;
use App\Mail\Messages\RoomActivityLockWarningMessage;
use App\Services\LegacyEnvironment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RoomMessageFactory
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;

    /**
     * @var LegacyEnvironment
     */
    private LegacyEnvironment $legacyEnvironment;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function createRoomActivityLockWarningMessage(Room $room): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($room);
        if ($portal) {
            return new RoomActivityLockWarningMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $room);
        }

        return null;
    }

    public function createRoomActivityDeleteWarningMessage(Room $room): ?MessageInterface
    {
        $portal = $this->accountManager->getPortal($room);
        if ($portal) {
            return new RoomActivityDeleteWarningMessage($this->urlGenerator, $this->legacyEnvironment, $portal, $room);
        }

        return null;
    }
}