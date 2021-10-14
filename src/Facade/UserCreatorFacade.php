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
use App\Form\Model\Csv\CsvUserDataset;
use App\Services\LegacyEnvironment;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class UserCreatorFacade
{
    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var AccountCreatorFacade
     */
    private AccountCreatorFacade $accountFacade;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        AccountCreatorFacade $accountFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->accountFacade = $accountFacade;
        $this->entityManager = $entityManager;

    }

    /**
     * @param CsvUserDataset[] $csvUserDatasets
     * @param AuthSource $authSource
     * @throws Exception
     */
    public function createFromCsvDataset(
        AuthSource $authSource,
        array $csvUserDatasets
    ) {
        foreach ($csvUserDatasets as $csvUserDataset) {
            /** CsvUserDataset $csvUserDataset */
            $userIdentifier = $this->findFreeIdentifier($csvUserDataset->getIdentifier(), $authSource);
            $userPassword = $csvUserDataset->getPassword() ?? $this->generatePassword();

            $newUser = $this->createAccountAndUser(
                $userIdentifier,
                $userPassword,
                $csvUserDataset->getFirstname(),
                $csvUserDataset->getLastname(),
                $csvUserDataset->getEmail(),
                $authSource
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
     * @param AuthSource $authSource
     * @return string The free user identifier
     */
    private function findFreeIdentifier(string $identifier, AuthSource $authSource): string
    {
        $portalId = $authSource->getPortal()->getId();
        $accountRepository = $this->entityManager->getRepository(Account::class);
        $lookup = $identifier;
        $suffix = 0;

        while ($accountRepository->findOneByCredentials($lookup, $portalId, $authSource)) {
            $suffix++;
            $lookup = $identifier . $suffix;
        }

        return $lookup;
    }

    /**
     * @return bool|string The password or false on error
     * @throws Exception
     */
    private function generatePassword(): string
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
     * @param AuthSource $authSource
     * @return cs_user_item
     */
    private function createAccountAndUser(
        string $identifier,
        string $password,
        string $firstname,
        string $lastname,
        string $email,
        AuthSource $authSource
    ): cs_user_item {
        $account = new Account();
        $account->setUsername($identifier);
        $account->setPassword($password);
        $account->setFirstname($firstname);
        $account->setLastname($lastname);
        $account->setEmail($email);
        $account->setContextId($authSource->getPortal()->getId());
        $account->setAuthSource($authSource);

        return $this->accountFacade->persistNewAccount($account);
    }

    /**
     * @param cs_user_item $user
     * @param string $rooms
     */
    private function addUserToRooms(cs_user_item $user, string $rooms): void
    {
        $roomIds = explode(' ', trim($rooms));

        $roomManager = $this->legacyEnvironment->getRoomManager();
        $privateRoomUser = $user->getRelatedPrivateRoomUserItem();

        foreach ($roomIds as $roomId) {
            $room = $roomManager->getItem($roomId);

            if ($room) {
                $relatedUserInContext = $user->getRelatedUserItemInContext($roomId);
                if (!$relatedUserInContext) {
                    // determine the source user to clone from
                    $sourceUser = $privateRoomUser ?: $user;

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