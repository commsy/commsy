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
use cs_environment;
use cs_room_item;

class InvitationMessage extends Message
{
    private cs_environment $legacyEnvironment;

    private cs_room_item $room;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private Portal $portal,
        cs_room_item $room,
        private string $token
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->room = $room;
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
