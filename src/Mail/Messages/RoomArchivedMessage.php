<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-08
 * Time: 16:00
 */

namespace App\Mail\Messages;


use App\Mail\Message;

class RoomArchivedMessage extends Message
{
    /**
     * @var \cs_room_item $room
     */
    private $room;

    /**
     * @var int $numDays;
     */
    private $numDays;

    public function __construct(\cs_room_item $roomItem, int $numDays)
    {
        $this->room = $roomItem;
        $this->numDays = $numDays;
    }

    public function getSubject(): string
    {
        return '%room_name% will be archived in %num_days% days.';
    }

    public function getTemplateName(): string
    {
        return 'mail/room_archived.html.twig';
    }

    public function getTranslationParameters(): array
    {
        return [
            '%room_name%' => $this->room->getTitle(),
            '%num_days%' => $this->numDays,
        ];
    }

    public function getParameters(): array
    {
        return [
            'room' => $this->room,
        ];
    }

    /**
     * @return \cs_room_item
     */
    public function getRoom(): \cs_room_item
    {
        return $this->room;
    }

    /**
     * @param \cs_room_item $room
     * @return RoomArchivedMessage
     */
    public function setRoom(\cs_room_item $room): RoomArchivedMessage
    {
        $this->room = $room;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumDays(): int
    {
        return $this->numDays;
    }

    /**
     * @param int $numDays
     * @return RoomArchivedMessage
     */
    public function setNumDays(int $numDays): RoomArchivedMessage
    {
        $this->numDays = $numDays;
        return $this;
    }
}