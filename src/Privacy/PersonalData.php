<?php
namespace App\Privacy;

/**
 * Class PersonalData
 *
 * Holds a user's personal master data, i.e. account data as well as profile data for all of the user's rooms.
 *
 * @package App\Privacy
 */
class PersonalData
{
    /**
     * @var AccountData|null
     */
    private $accountData;

    /**
     * @var RoomProfileData[]|null
     */
    private $roomProfileDataArray;

    /**
     * @return AccountData|null the user's account data
     */
    public function getAccountData(): ?AccountData
    {
        return $this->accountData;
    }

    /**
     * @param AccountData|null $accountData
     * @return PersonalData
     */
    public function setAccountData(?AccountData $accountData): PersonalData
    {
        $this->accountData = $accountData;
        return $this;
    }

    /**
     * @return RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each room
     */
    public function getRoomProfileDataArray(): ?array
    {
        return $this->roomProfileDataArray;
    }

    /**
     * @param RoomProfileData[]|null $roomProfileDataArray
     * @return PersonalData
     */
    public function setRoomProfileDataArray(?array $roomProfileDataArray): PersonalData
    {
        $this->roomProfileDataArray = $roomProfileDataArray;
        return $this;
    }
}
