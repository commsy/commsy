<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 30.07.18
 * Time: 19:54
 */

namespace App\Facade;


use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Form\Model\Csv\CsvUserDataset;
use App\Services\LegacyEnvironment;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class UserCreatorFacade
{
    /**
     * @var \cs_environment
     */
    private $legacyEnvironment;

    private $passwordEncoder;

    private $accountFacade;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        UserPasswordEncoderInterface $passwordEncoder,
        AccountCreatorFacade $accountFacade
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->accountFacade = $accountFacade;
        $this->passwordEncoder = $passwordEncoder;

    }

    /**
     * @param CsvUserDataset[] $csvUserDatasets
     * @param AuthSource $authSourceItem
     */
    public function createFromCsvDataset(
        AuthSource $authSourceItem,
        array $csvUserDatasets
    ) {
        foreach ($csvUserDatasets as $csvUserDataset) {
            /** CsvUserDataset $csvUserDataset */
            // $userIdentifier = $this->findFreeIdentifier($csvUserDataset->getIdentifier(), $authSourceItem);
            $userPassword = $csvUserDataset->getPassword() ?? $this->generatePassword();

            $newUser = $this->createUser(
                $csvUserDataset->getIdentifier(),
                $userPassword,
                $csvUserDataset->getFirstname(),
                $csvUserDataset->getLastname(),
                $csvUserDataset->getEmail(),
                $this->legacyEnvironment->getCurrentPortalID(),
                $authSourceItem
            );

            $newUser = $this->createUser(
                $csvUserDataset->getIdentifier(),
                $userPassword,
                $csvUserDataset->getFirstname(),
                $csvUserDataset->getLastname(),
                $csvUserDataset->getEmail(),
                $this->legacyEnvironment->getCurrentPortalID(),
                $authSourceItem
            );

            if ($csvUserDataset->getRooms()) {
                $this->addUserToRooms($newUser, $csvUserDataset->getRooms());
            }
        }
    }

    /**
     * Searches for a not yet used username or identifier for the given authentication source.
     * If the provided identifier is already used, search continues by appending a numeric
     * suffix until a free account ist found.
     *
     * @param string $identifier
     * @param AuthSource $authSourceItem
     * @return string The free user identifier
     */
    private function findFreeIdentifier(string $identifier, AuthSource $authSourceItem): string
    {
        $authentication = $this->legacyEnvironment->getAuthenticationObject();
        $lookup = $identifier;
        $suffix = null;

        while (!$authentication->is_free($lookup, $authSourceItem->getItemID())) {
            if ($suffix === null) {
                $suffix = 0;
            }

            $suffix++;
            $lookup = $identifier . (string)$suffix;
        }

        return $lookup;
    }

    /**
     * @param int $length
     * @return bool|string The password or false on error
     * @throws \Exception
     */
    private function generatePassword(int $length = 12): string
    {
        return substr(sha1(random_bytes(10)), 0, 10);
    }

    /**
     * Creates entries in auth and user table as needed. Only the local authentication persisted in the commsy
     * database needs to create an authentication item. See the different auth implementations for detail.
     *
     * @param string $identifier
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $portalId
     * @param int $authSourceId
     * @return \cs_user_item
     */
    private function createAuthAndUser(
        string $identifier,
        string $password,
        string $firstname,
        string $lastname,
        string $email,
        int $portalId,
        int $authSourceId
    ): \cs_user_item {
        $authentication = $this->legacyEnvironment->getAuthenticationObject();

        $newAccount = $authentication->getNewItem();
        $newAccount->setUserID($identifier);
        $newAccount->setPassword($password);
        $newAccount->setFirstname($firstname);
        $newAccount->setLastname($lastname);
        $newAccount->setEmail($email);
        $newAccount->setPortalID($portalId);
        $newAccount->setAuthSourceID($authSourceId);

        $authentication->save($newAccount);

        $newUser = $authentication->getUserItem();
        $newUser->makeUser();
        $newUser->save();

        return $newUser;
    }


    /**
     * @param string $identifier
     * @param string $password
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param int $portalId
     * @param AuthSource $authSourceItem
     * @return \cs_user_item
     */
    public function createUser(
        string $identifier,
        string $password,
        string $firstname,
        string $lastname,
        string $email,
        int $portalId,
        AuthSource $authSourceItem
    ): \cs_user_item {

        $account = new Account();
        $account->setAuthSource($authSourceItem);
        $account->setContextId($portalId);

        $account->setLanguage('de');
        $password = $this->passwordEncoder->encodePassword($account, $password);
        $account->setPassword($password);

        $account->setFirstname($firstname);
        $account->setLastname($lastname);
        $account->setEmail($email);
        $account->setUsername($identifier);

        return $this->accountFacade->persistNewAccount($account);
    }

    private function addUserToRooms(\cs_user_item $user, string $rooms)
    {
        $roomIds = explode(' ', trim($rooms));

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $privateRoomUser = $user->getRelatedPrivateRoomUserItem();

        foreach ($roomIds as $roomId) {
            $room = $roomManager->getItem($roomId);

            if ($room) {
                $userAlreadyExists = $user->getRelatedUserItemInContext($roomId) ? true : false;
                if (!$userAlreadyExists) {
                    // determine the source user to clone from
                    $sourceUser = $privateRoomUser ? $privateRoomUser : $user;

                    $newUserItem = $sourceUser->cloneData();
                    $newUserItem->setContextID($roomId);

                    if ($room->checkNewMembersNever()) {
                        $newUserItem->setStatus(2);
                    } else {
                        $newUserItem->setStatus(1);
                    }

                    $newUserItem->save();

                    // task
                    if (!$newUserItem->isUser()) {
                        $currentUser = $this->legacyEnvironment->getCurrentUserItem();

                        $taskManager = $this->legacyEnvironment->getTaskManager();
                        $requestTask = $taskManager->getNewItem();
                        $requestTask->setCreatorItem($currentUser);
                        $requestTask->setContextID($room->getItemID());
                        $requestTask->setTitle('TASK_USER_REQUEST');
                        $requestTask->setStatus('REQUEST');
                        $requestTask->setItem($newUserItem);
                        $requestTask->save();
                    }
                }
            }
        }
    }
}