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

use App\Mail\Message;
use cs_room_item;

class RoomArchivedMessage extends Message
{
    public function __construct(private cs_room_item $room, private int $numDays)
    {
    }

    public function getSubject(): string
    {
        return '{room_name} will be archived in {num_days} days.';
    }

    public function getTemplateName(): string
    {
        return 'mail/room_archived.html.twig';
    }

    public function getTranslationParameters(): array
    {
        return [
            'room_name' => $this->room->getTitle(),
            'num_days' => $this->numDays,
        ];
    }

    public function getParameters(): array
    {
        return [
            'room' => $this->room,
        ];
    }

    public function getRoom(): cs_room_item
    {
        return $this->room;
    }

    public function setRoom(cs_room_item $room): RoomArchivedMessage
    {
        $this->room = $room;

        return $this;
    }

    public function getNumDays(): int
    {
        return $this->numDays;
    }

    public function setNumDays(int $numDays): RoomArchivedMessage
    {
        $this->numDays = $numDays;

        return $this;
    }
}
