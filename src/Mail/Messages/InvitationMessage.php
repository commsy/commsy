<?php

namespace App\Mail\Messages;

use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_room_item;

class InvitationMessage extends Message
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var Portal
     */
    private Portal $portal;

    /**
     * @var cs_room_item
     */
    private cs_room_item $room;

    /**
     * @var string
     */
    private string $token;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Portal $portal,
        cs_room_item $room,
        string $token
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->portal = $portal;
        $this->room = $room;
        $this->token = $token;
    }

    public function getSubject(): string
    {
        return 'mail.invitation_subject';
    }

    public function getTemplateName(): string
    {
        return 'mail/invitation.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'room' => $this->room,
            'portal' => $this->portal,
            'token' => $this->token,
            'senderName' => $this->legacyEnvironment->getCurrentUserItem()->getFullName(),
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal%' => $this->portal->getTitle(),
        ];
    }
}