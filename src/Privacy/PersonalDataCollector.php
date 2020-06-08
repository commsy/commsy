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
        $this->populateAccountData($personalData, $user);
        $this->populateRoomProfileData($personalData, $user);

        return $personalData;
    }

    /**
     * Populates the given PersonalData object with the account data for the given user
     * @param PersonalData $personalData
     * @param \cs_user_item $user
     */
    private function populateAccountData(PersonalData $personalData, \cs_user_item $user)
    {
        $accountData = $this->getAccountDataForUser($user);

        if (isset($accountData)) {
            $personalData->setAccountData($accountData);
        }
    }

    /**
     * Populates the given PersonalData object with all room profile data for the given user
     * @param PersonalData $personalData
     * @param \cs_user_item $user
     */
    private function populateRoomProfileData(PersonalData $personalData, \cs_user_item $user)
    {
        /**
         * @var RoomProfileData[]
         */
        $communityRoomProfileDataArray = [];

        /**
         * @var RoomProfileData[]
         */
        $projectRoomProfileDataArray = [];

        /**
         * @var RoomProfileData[]
         */
        $groupRoomProfileDataArray = [];

        // TODO: to get all related users, should we better start from the portalUser (`$portalUser->getRelatedPortalUserItem()`) instead?
        //       see comment in `ProfileController->calendarsAction()` which likely also applies here
        /**
         * @var \cs_user_item[] $relatedUsers
         */
        $relatedUsers = $user->getRelatedUserList()->to_array();

        foreach ($relatedUsers as $relatedUser) {
            $roomProfileData = $this->getRoomProfileDataForUser($relatedUser);
            $roomID = $roomProfileData->getRoomID();
            $roomType = $roomProfileData->getRoomType();

            if ($roomType === CS_COMMUNITY_TYPE) {
                $communityRoomProfileDataArray[$roomID] = $roomProfileData;
            } else if ($roomType === CS_PROJECT_TYPE) {
                $projectRoomProfileDataArray[$roomID] = $roomProfileData;
            } else if ($roomType === CS_GROUPROOM_TYPE) {
                $groupRoomProfileDataArray[$roomID] = $roomProfileData;
            } // NOTE: we ignore the user's private room since this doesn't have a user-facing room profile
        }

        if (!empty($communityRoomProfileDataArray)) {
            ksort($communityRoomProfileDataArray);
            $personalData->setCommunityRoomProfileDataArray($communityRoomProfileDataArray);
        }
        if (!empty($projectRoomProfileDataArray)) {
            ksort($projectRoomProfileDataArray);
            $personalData->setProjectRoomProfileDataArray($projectRoomProfileDataArray);
        }
        if (!empty($groupRoomProfileDataArray)) {
            ksort($groupRoomProfileDataArray);
            $personalData->setGroupRoomProfileDataArray($groupRoomProfileDataArray);
        }
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
        $accountData->setCreationDate(new \DateTime($portalUser->getCreationDate()));

        $lastLogin = $portalUser->getLastLogin();
        if (isset($lastLogin) && !empty($lastLogin)) {
            $accountData->setLastLoginDate(new \DateTime($lastLogin));
        }

        $accountData->setEmail($portalUser->getEmail());

        $accountData->setFirstName($portalUser->getFirstname());
        $accountData->setLastName($portalUser->getLastname());

        $accountData->setLanguage($portalUser->getLanguage());

        $birthdate = $portalUser->getBirthday();
        if (isset($birthdate) && !empty($birthdate)) {
            $accountData->setBirthdate(new \DateTime($birthdate));
        }

        /**
         * @var \cs_privateroom_item $privateRoom
         */
        $privateRoom = $portalUser->getOwnRoom($portal->getItemID());
        $accountData->setNewsletterStatus($privateRoom->getPrivateRoomNewsletterActivity());

        return $accountData;
    }

    /**
     * Returns the room profile data for the given user
     * @param \cs_user_item $user
     * @return RoomProfileData
     */
    private function getRoomProfileDataForUser(\cs_user_item $user): RoomProfileData
    {
        $roomProfileData = new RoomProfileData();
        $roomItem = $user->getContextItem();

        $roomProfileData->setRoomID($roomItem->getItemID());
        $roomProfileData->setRoomType($roomItem->getRoomType());
        $roomProfileData->setRoomName($roomItem->getTitle());

        $roomProfileData->setItemID($user->getItemID());
        $roomProfileData->setCreationDate(new \DateTime($user->getCreationDate()));

        $roomProfileData->setStatus($user->getStatus());
        $roomProfileData->setIsContact($user->isContact());

        $roomProfileData->setTitle($user->getTitle());

        $roomProfileData->setEmail($user->getEmail());
        $roomProfileData->setIsEmailVisible($user->isEmailVisible());

        $roomProfileData->setStreet($user->getStreet());
        $roomProfileData->setZipcode($user->getZipcode());
        $roomProfileData->setCity($user->getCity());

        $roomProfileData->setWorkspace($user->getRoom());
        $roomProfileData->setOrganisation($user->getOrganisation());
        $roomProfileData->setPosition($user->getPosition());

        $roomProfileData->setPhoneNumber($user->getTelephone());
        $roomProfileData->setCellphoneNumber($user->getCellularphone());
        $roomProfileData->setSkypeID($user->getSkype());
        $roomProfileData->setHomepage($user->getHomepage());

        $roomProfileData->setDescription($user->getDescription());

        return $roomProfileData;
    }
}
