<?php

namespace App\Mail\Messages;

use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTimeImmutable;

class RoomActivityLockWarningMessage extends Message
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
     * @var object
     */
    private object $room;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        Portal $portal,
        object $room

    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->portal = $portal;
        $this->room = $room;
    }

    public function getSubject(): string
    {
        return '%portal_name%: Workspace will be locked in %num_days% days';
    }

    public function getTemplateName(): string
    {
        return 'mail/account_room_lock_warning.html.twig';
    }

    public function getParameters(): array
    {
        $legacyTranslator = $this->legacyEnvironment->getTranslationObject();

        $now = new DateTimeImmutable();
        $numDaysInactive = $now->diff($this->room->getLastLogin(), true)->format("%a");

        return [
            'room' => $this->room,
            'hello' => $legacyTranslator->getEmailMessage('PROJECT_MAIL_BODY_ARCHIVE_INFO'),
            'content' => $legacyTranslator->getEmailMessage('EMAIL_INACTIVITY_ROOM_LOCK_UPCOMING_BODY',
                $this->room->getTitle(),
                $numDaysInactive,
                $this->portal->getClearInactiveRoomsLockDays(),
            )
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            '%portal_name%' => $this->portal->getTitle(),
            '%num_days%' => $this->portal->getClearInactiveRoomsLockDays(),
        ];
    }
}