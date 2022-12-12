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

namespace App\Privacy;

/**
 * Class PersonalData.
 *
 * Holds a user's personal master data, i.e. account data as well as profile data for all of the user's rooms.
 */
class PersonalData
{
    /**
     * @var AccountData|null holds the user's account data
     */
    private ?AccountData $accountData = null;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each community room
     */
    private ?array $communityRoomProfileDataArray = null;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each project room
     */
    private ?array $projectRoomProfileDataArray = null;

    /**
     * @var RoomProfileData[]|null array of RoomProfileData objects holding the user's profile data for each group room
     */
    private ?array $groupRoomProfileDataArray = null;

    /**
     * @return AccountData|null the user's account data
     */
    public function getAccountData(): ?AccountData
    {
        return $this->accountData;
    }

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
     */
    public function setGroupRoomProfileDataArray(?array $groupRoomProfileDataArray): PersonalData
    {
        $this->groupRoomProfileDataArray = $groupRoomProfileDataArray;

        return $this;
    }
}
