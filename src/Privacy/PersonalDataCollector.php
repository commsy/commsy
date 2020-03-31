<?php
namespace App\Privacy;

use App\Services\LegacyEnvironment;
use App\Utils\UserService;

/**
 * Class PersonalDataCollector
 *
 * Collects a user's personal master data, i.e. account data as well as profile data for all of the user's rooms.
 *
 * @package App\Privacy
 */
class PersonalDataCollector
{
    /**
     * @var LegacyEnvironment
     */
    private $legacyEnvironment;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * PersonalDataCollector constructor.
     * @param LegacyEnvironment $legacyEnvironment
     * @param UserService $userService
     */
    public function __construct(LegacyEnvironment $legacyEnvironment, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->userService = $userService;
    }

    /**
     * Returns the personal data of the user with the given user ID
     * @param int $userID
     * @return PersonalData|null
     */
    public function getPersonalDataForUserID(int $userID): ?PersonalData
    {
        $user = $this->userService->getUser($userID);
        if (!$user) {
            return null;
        }

        $personalData = new PersonalData();
        $personalData->setAccountData($this->getAccountDataForUser($user));
        $personalData->setRoomProfileDataArray($this->getRoomProfileDataForUser($user));

        return $personalData;
    }

    /**
     * Returns the account data for the given user
     * @param \cs_user_item $user
     * @return AccountData|null
     */
    private function getAccountDataForUser(\cs_user_item $user): ?AccountData
    {
        $portal = $this->legacyEnvironment->getCurrentPortalItem();
        $portalUser = $user->getRelatedPortalUserItem();
        if (!$portal || !$portalUser) {
            return null;
        }

        $accountData = new AccountData();

        $accountData->setPortalID($portal->getItemID());
        $accountData->setPortalName($portal->getTitle());

        $accountData->setItemID($portalUser->getItemID());
        $accountData->setUserID($portalUser->getUserID());

        return $accountData;
    }

    /**
     * Returns all room profile data for the given user, i.e. an array of profile data for all of the user's rooms.
     * @param \cs_user_item $user
     * @return RoomProfileData[]|null
     */
    private function getRoomProfileDataForUser(\cs_user_item $user): ?array
    {
        /**
         * @var RoomProfileData[]
         */
        $roomProfileDataArray = [];

        $rooms = $this->userService->getRoomList($user);

        foreach ($rooms as $room) {
            $roomProfileData = new RoomProfileData();

            // TODO: get the appropriate user object for this room and extract the profile data

            $roomProfileDataArray[] = $roomProfileData;
        }

        return $roomProfileDataArray;
    }
}
