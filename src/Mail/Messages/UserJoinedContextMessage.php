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
use App\Entity\Room;
use App\Mail\Message;
use App\Services\CurrentUserResolver;
use cs_user_item;

class UserJoinedContextMessage extends Message
{
    public function __construct(
        private readonly CurrentUserResolver $currentUserResolver,
        private readonly Portal $portal,
        private readonly Room $room,
        private readonly cs_user_item $newUser,
        private readonly ?string $comment
    ) {
    }

    public function getSubject(): string
    {
        return 'mail.subject.user_joined_context';
    }

    public function getTemplateName(): string
    {
        return 'mail/user_joined_context.html.twig';
    }

    public function getParameters(): array
    {
        return [
            'room' => $this->room,
            'portal' => $this->portal,
            'newUser' => $this->newUser,
            'comment' => $this->comment,
        ];
    }

    public function getTranslationParameters(): array
    {
        return [
            'fullname' => $this->currentUserResolver->getUser()?->getFullname() ?? '',
            'room_title' => $this->room->getTitle(),
        ];
    }
}
