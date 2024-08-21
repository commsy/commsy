<?php

namespace App\Mail\Factories;

use App\Mail\MessageInterface;
use App\Mail\Messages\RoomModerationMessage;
use App\Proxy\PortalProxy;
use App\Services\LegacyEnvironment;
use cs_community_item;
use cs_context_item;
use cs_room_item;

readonly class ModerationMessageFactory
{
    public function __construct(
        private LegacyEnvironment $legacyEnvironment,
    ) {
    }

    public function createRoomModerationMessage(
        cs_room_item $room,
        PortalProxy|cs_community_item|cs_context_item|null $parentContext,
        string $changeType,
        ?array $oldLinkedIds = [],
        ?array $newLinkedIds = [],
    ): ?MessageInterface
    {
        return new RoomModerationMessage($room,
            $parentContext,
            $changeType,
            $this->legacyEnvironment,
            $oldLinkedIds ?? [],
            $newLinkedIds ?? []
        );
    }
}
