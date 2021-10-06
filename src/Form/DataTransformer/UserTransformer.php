<?php
namespace App\Form\DataTransformer;

use App\Account\AccountManager;
use App\Entity\Account;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class UserTransformer extends AbstractTransformer
{
    protected $entity = 'user';

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var Security
     */
    private Security $security;

    /**
     * @var AccountManager
     */
    private AccountManager $accountManager;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        EntityManagerInterface $entityManager,
        Security $security,
        AccountManager $accountManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->accountManager = $accountManager;
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_user_item $userItem
     * @return array
     */
    public function transform($userItem)
    {

        // get portal user if in room context
        if ( !$this->legacyEnvironment->inPortal() ) {
            $portalUser = $this->legacyEnvironment->getPortalUserItem();
        } else {
            $portalUser = $this->legacyEnvironment->getCurrentUserItem();
        }

        $userData = array();
        if ($userItem) {
            $userData['userId'] = $userItem->getUserId();
            $userData['firstname'] = $userItem->getFirstname();
            $userData['lastname'] = $userItem->getLastname();
            $userData['language'] = $userItem->getLanguage();
            if ($userItem->getAutoSaveStatus() == '1') {
                $userData['autoSaveStatus'] = true;
            } else {
                $userData['autoSaveStatus'] = false;
            }
            $userData['title'] = $userItem->getTitle();
            if($userItem->getBirthday()){
                $userData['dateOfBirth'] = new \DateTime($userItem->getBirthday());
            }
            $userData['emailRoom'] = $userItem->getRoomEmail();
            $userData['emailAccount'] = $portalUser->getEmail();
            $userData['emailChoice'] = $userItem->getUsePortalEmail() ? 'account' : 'roomProfile';
            $userData['hideEmailInThisRoom'] = !$userItem->isEmailVisible();
            $userData['phone'] = $userItem->getTelephone();
            $userData['mobile'] = $userItem->getCellularphone();
            $userData['street'] = $userItem->getStreet();
            $userData['zipCode'] = $userItem->getZipcode();
            $userData['city'] = $userItem->getCity();
            $userData['room'] = $userItem->getRoom();
            $userData['organisation'] = $userItem->getOrganisation();
            $userData['position'] = $userItem->getPosition();
            $userData['icq'] = $userItem->getICQ();
            $userData['msn'] = $userItem->getMSN();
            $userData['skype'] = $userItem->getSkype();
            $userData['yahoo'] = $userItem->getYahoo();
            $userData['jabber'] = $userItem->getJabber();
            $userData['homepage'] = $userItem->getHomepage();
            $userData['description'] = $userItem->getDescription();
            $userData['language'] = $userItem->getLanguage();
        }
        return $userData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $userObject
     * @param array $userData
     * @return cs_user_item|null
     */
    public function applyTransformation($userObject, $userData)
    {
        /** @var cs_user_item $userObject */
        if ($userObject) {
            $userObject->setUserId($userData['userId']);

            // get portal user if in room context
            if (!$this->legacyEnvironment->inPortal()) {
                $portalUser = $this->legacyEnvironment->getPortalUserItem();
            } else {
                $portalUser = $this->legacyEnvironment->getCurrentUserItem();
            }

            /** @var Account $account */
            $account = $this->security->getUser();

            if ($account->getAuthSource()->isChangeUsername()) {
                // check if userid has changed
                $newUserId = $userData['userId'];
                if ($portalUser->getUserID() != $newUserId) {
                    if ($this->accountManager->propagateUsernameChange($account, $portalUser, $userData['userId'])) {
                        $portalUser->setUserId($newUserId); // Important, as this object is saved again later!
                    } else {
                        die("ERROR: changing User ID not successful");
                    }
                }
            }

            $this->accountManager->updateUserLocale($account, $userData['language']);

            $userObject->setFirstname($userData['firstname']);
            $userObject->setLastname($userData['lastname']);
            $userObject->setLanguage($userData['language']);

            $portalUser->setFirstname($userData['firstname']);
            $portalUser->setLastname($userData['lastname']);
            $portalUser->setLanguage($userData['language']);

            // since name and language are now configured in the account settings,
            // they always have to be changed for the list of related users as well
            $userList = $userObject->getRelatedUserList();
            $tempUserItem = $userList->getFirst();
            while ($tempUserItem) {
                $tempUserItem->setFirstname($userData['firstname']);
                $tempUserItem->setLastname($userData['lastname']);
                $tempUserItem->setLanguage($userData['language']);
                $tempUserItem->save();
                $tempUserItem = $userList->getNext();
            }

            if ($userData['autoSaveStatus']) {
                $userObject->turnAutoSaveOn();
            } else {
                $userObject->turnAutoSaveOff();
            }

            $userObject->setTitle($userData['title']);
            if (array_key_exists('dateOfBirth', $userData) && $userData['dateOfBirth']) {
                $userObject->setBirthday($userData['dateOfBirth']->format('Y-m-d'));
            } else {
                $userObject->setBirthday("");
            }

            if ($userData['emailChoice'] === 'account') {
                $userObject->setUsePortalEmail(1);
            } else {
                $userObject->setUsePortalEmail(0);
            }

            $userObject->setEmail($userData['emailRoom']);

            $privateRoomUserItem = $portalUser->getRelatedPrivateRoomUserItem();

            if (isset($userData['emailAccount'])) {
                $portalUser->setEmail($userData['emailAccount']);
                if ($portalUser->hasToChangeEmail()) {
                    $portalUser->unsetHasToChangeEmail();
                }
                $portalUser->save();

                if ($privateRoomUserItem) {
                    $privateRoomUserItem->setEmail($portalUser->getEmail());
                    $privateRoomUserItem->save();
                }

                if ($this->legacyEnvironment->inPortal()) {
                    $userObject->setEmail($portalUser->getEmail());

                    $account->setEmail($portalUser->getEmail());
                    $account->setFirstname($portalUser->getFirstname());
                    $account->setLastname($portalUser->getLastname());
                    $account->setLanguage($portalUser->getLanguage());

                    $this->entityManager->persist($account);
                    $this->entityManager->flush();
                }
            }

            if ($userData['hideEmailInThisRoom']) {
                $userObject->setEmailNotVisible();
            } else {
                $userObject->setEmailVisible();
            }

            $userObject->setTelephone($userData['phone']);
            $userObject->setCellularphone($userData['mobile']);
            $userObject->setStreet($userData['street']);
            $userObject->setZipcode($userData['zipCode']);
            $userObject->setCity($userData['city']);
            $userObject->setRoom($userData['room']);
            $userObject->setOrganisation($userData['organisation']);
            $userObject->setPosition($userData['position']);
            $userObject->setICQ($userData['icq']);
            $userObject->setMSN($userData['msn']);
            $userObject->setSkype($userData['skype']);
            $userObject->setYahoo($userData['yahoo']);
            $userObject->setJabber($userData['jabber']);
            $userObject->setHomepage($userData['homepage']);
            $userObject->setDescription($userData['description']);
            $userObject->setLanguage($userData['language']);

            if ($privateRoomUserItem) {
                $privateRoomUserItem->setLanguage($userData['language']);
                $privateRoomUserItem->save();
            }
        }
        return $userObject;
    }
}