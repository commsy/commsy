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

namespace App\Mail\Messages;

use App\Entity\Portal;
use App\Mail\Message;
use App\Services\LegacyEnvironment;

class RoomActivityLockWarningMessage extends Message
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private Portal $portal,
        private object $room
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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

        $now = new \DateTimeImmutable();
        $numDaysInactive = $now->diff($this->room->getLastLogin(), true)->format('%a');

        return [
            'room' => $this->room,
            'hello' => $legacyTranslator->getEmailMessage('PROJECT_MAIL_BODY_ARCHIVE_INFO'),
            'content' => $legacyTranslator->getEmailMessage('EMAIL_INACTIVITY_ROOM_LOCK_UPCOMING_BODY',
                $this->room->getTitle(),
                $numDaysInactive,
                $this->portal->getClearInactiveRoomsLockDays(),
            ),
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
