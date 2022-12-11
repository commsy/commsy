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

namespace App\Facade;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Room;
use App\Form\Model\Csv\CsvUserDataset;
use App\Services\LegacyEnvironment;
use App\Utils\UserService;
use Doctrine\ORM\EntityManagerInterface;

class UserCreatorFacade
{
    private \cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private AccountCreatorFacade $accountFacade,
        private UserService $userService,
        private EntityManagerInterface $entityManager,
        private \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordEncoder
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @throws \Exception
     */
    public function createFromCsvDataset(
        AuthSource $authSource,
        CsvUserDataset $csvUserDataset
    ) {
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

        $roomIdString = $csvUserDataset->getRooms();
        if ($roomIdString) {
            $roomIds = explode(' ', trim($roomIdString));
            $this->addUserToRoomsWithIds($newUser, $roomIds);
        }
    }

    /**
     * Searches for a not yet used username or identifier for the given authentication source.
     * If the provided identifier is already used, search continues by appending a numeric
     * suffix until a free account ist found.
     *
     * @return string The free user identifier
     */
    private function findFreeIdentifier(string $identifier, AuthSource $authSource): string
    {
        $portalId = $authSource->getPortal()->getId();
        $accountRepository = $this->entityManager->getRepository(Account::class);
        $lookup = $identifier;
        $suffix = 0;

        while ($accountRepository->findOneByCredentials($lookup, $portalId, $authSource)) {
            ++$suffix;
            $lookup = $identifier.$suffix;
        }

        return $lookup;
    }

    /**
     * @return bool|string The password or false on error
     *
     * @throws \Exception
     */
    private function generatePassword(): string
    {
        return substr(sha1(random_bytes(10)), 0, 10);
    }

    /**
     * Creates entries in auth and user table as needed. Only the local authentication persisted in the commsy
     * database needs to create an authentication item. See the different auth implementations for detail.
     */
    private function createAccountAndUser(
        string $identifier,
        string $password,
        string $firstname,
        string $lastname,
        string $email,
        AuthSource $authSource
    ): \cs_user_item {
        $account = new Account();
        $account->setUsername($identifier);
        $account->setFirstname($firstname);
        $account->setLastname($lastname);
        $account->setEmail($email);
        $account->setContextId($authSource->getPortal()->getId());
        $account->setLanguage('de');
        $account->setAuthSource($authSource);

        $account->setPassword($this->passwordEncoder->encodePassword($account, $password));

        return $this->accountFacade->persistNewAccount($account);
    }

    /**
     * Adds users representing the given account to the rooms with the given room slugs.
     *
     * @param Account  $account   the account for whom room users shall be created
     * @param string[] $roomSlugs list of room slugs (i.e., unique textual identifiers for the rooms)
     */
    public function addUserToRoomsWithSlugs(Account $account, array $roomSlugs): void
    {
        if (empty($roomSlugs)) {
            return;
        }

        $roomRepository = $this->entityManager->getRepository(Room::class);

        // map room slugs to actual room IDs
        $roomIds = array_map(function (string $roomSlug) use ($roomRepository, $account) {
            $room = $roomRepository->findOneByRoomSlug(trim($roomSlug), $account->getContextId());

            return ($room) ? $room->getItemId() : null;
        }, $roomSlugs);

        // filter out any null values (where a room slug couldn't be mapped to an actual room ID)
        $roomIds = array_filter($roomIds);

        // create room users
        $portalUser = $this->userService->getPortalUser($account);
        $this->addUserToRoomsWithIds($portalUser, $roomIds);
    }

    /**
     * Adds users representing the given user to the rooms with the given IDs.
     *
     * @param \cs_user_item $user    the user for whom room users shall be created
     * @param array         $roomIds list of room IDs
     */
    private function addUserToRoomsWithIds(\cs_user_item $user, array $roomIds): void
    {
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
