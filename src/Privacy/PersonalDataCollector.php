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

        // TODO: to get all related users, should we better start from the portalUser (`$portalUser->getRelatedPortalUserItem()`) instead?
        //       see comment in `ProfileController->calendarsAction()` which likely also applies here
        /**
         * @var \cs_user_item[] $relatedUsers
         */
        $relatedUsers = $user->getRelatedUserList()->to_array();

        foreach ($relatedUsers as $relatedUser) {
            $roomProfileData = new RoomProfileData();
            $roomItem = $relatedUser->getContextItem();

            $roomProfileData->setRoomID($roomItem->getItemID());
            $roomProfileData->setRoomName($roomItem->getTitle());

            $roomProfileData->setItemID($relatedUser->getItemID());
            $roomProfileData->setCreationDate(new \DateTime($relatedUser->getCreationDate()));

            $roomProfileData->setStatus($relatedUser->getStatus());
            $roomProfileData->setIsContact($relatedUser->isContact());

            $roomProfileData->setTitle($relatedUser->getTitle());

            $roomProfileData->setEmail($relatedUser->getEmail());
            $roomProfileData->setIsEmailVisible($relatedUser->isEmailVisible());

            $roomProfileData->setStreet($relatedUser->getStreet());
            $roomProfileData->setZipcode($relatedUser->getZipcode());
            $roomProfileData->setCity($relatedUser->getCity());

            $roomProfileData->setWorkspace($relatedUser->getRoom());
            $roomProfileData->setOrganisation($relatedUser->getOrganisation());
            $roomProfileData->setPosition($relatedUser->getPosition());

            $roomProfileData->setPhoneNumber($relatedUser->getTelephone());
            $roomProfileData->setCellphoneNumber($relatedUser->getCellularphone());
            $roomProfileData->setSkypeID($relatedUser->getSkype());
            $roomProfileData->setHomepage($relatedUser->getHomepage());

            $roomProfileData->setDescription($relatedUser->getDescription());

            $roomProfileDataArray[] = $roomProfileData;
        }

        return $roomProfileDataArray;
    }
}
