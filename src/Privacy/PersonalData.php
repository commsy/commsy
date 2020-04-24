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
     * @var AccountData|null holds the user's account data
     */
    private $accountData;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each community room
     */
    private $communityRoomProfileDataArray;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each project room
     */
    private $projectRoomProfileDataArray;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each group room
     */
    private $groupRoomProfileDataArray;

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
     * @return RoomProfileData[]|null
     */
    public function getCommunityRoomProfileDataArray(): ?array
    {
        return $this->communityRoomProfileDataArray;
    }

    /**
     * @param RoomProfileData[]|null $communityRoomProfileDataArray
     * @return PersonalData
     */
    public function setCommunityRoomProfileDataArray(?array $communityRoomProfileDataArray): PersonalData
    {
        $this->communityRoomProfileDataArray = $communityRoomProfileDataArray;
        return $this;
    }

    /**
     * @return RoomProfileData[]|null
     */
    public function getProjectRoomProfileDataArray(): ?array
    {
        return $this->projectRoomProfileDataArray;
    }

    /**
     * @param RoomProfileData[]|null $projectRoomProfileDataArray
     * @return PersonalData
     */
    public function setProjectRoomProfileDataArray(?array $projectRoomProfileDataArray): PersonalData
    {
        $this->projectRoomProfileDataArray = $projectRoomProfileDataArray;
        return $this;
    }

    /**
     * @return RoomProfileData[]|null
     */
    public function getGroupRoomProfileDataArray(): ?array
    {
        return $this->groupRoomProfileDataArray;
    }

    /**
     * @param RoomProfileData[]|null $groupRoomProfileDataArray
     * @return PersonalData
     */
    public function setGroupRoomProfileDataArray(?array $groupRoomProfileDataArray): PersonalData
    {
        $this->groupRoomProfileDataArray = $groupRoomProfileDataArray;
        return $this;
    }
}
