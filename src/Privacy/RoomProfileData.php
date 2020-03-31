<?php
namespace App\Privacy;

/**
 * Class RoomProfileData
 *
 * Holds a user's room profile data (for a room with ID $roomID and room user with ID $itemID).
 *
 * @package App\Privacy
 */
class RoomProfileData
{
    /**
     * @var int
     */
    private $roomID;

    /**
     * @var string
     */
    private $roomName;

    /**
     * @var int
     */
    private $itemID;

    /**
     * @return int
     */
    public function getRoomID(): int
    {
        return $this->roomID;
    }

    /**
     * @param int $roomID
     * @return RoomProfileData
     */
    public function setRoomID(int $roomID): RoomProfileData
    {
        $this->roomID = $roomID;
        return $this;
    }

    /**
     * @return string
     */
    public function getRoomName(): string
    {
        return $this->roomName;
    }

    /**
     * @param string $roomName
     * @return RoomProfileData
     */
    public function setRoomName(string $roomName): RoomProfileData
    {
        $this->roomName = $roomName;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemID(): int
    {
        return $this->itemID;
    }

    /**
     * @param int $itemID
     * @return RoomProfileData
     */
    public function setItemID(int $itemID): RoomProfileData
    {
        $this->itemID = $itemID;
        return $this;
    }
}
