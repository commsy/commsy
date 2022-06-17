<?php

namespace App\Mail\Factories;

use App\Entity\Portal;
use App\Mail\MessageInterface;
use App\Mail\Messages\InvitationMessage;
use App\Services\LegacyEnvironment;
use cs_room_item;

class InvitationMessageFactory
{
    /**
     * @var LegacyEnvironment
     */
    private LegacyEnvironment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment;
    }

    public function createInvitationMessage(Portal $portal, cs_room_item $room, string $token): MessageInterface
    {
        return new InvitationMessage($this->legacyEnvironment, $portal, $room, $token);
    }
}