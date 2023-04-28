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
use App\Entity\RoomSlug;
use App\Event\UserJoinedRoomEvent;
use App\Form\Model\Csv\CsvUserDataset;
use App\Mail\Mailer;
use App\Services\LegacyEnvironment;
use App\Utils\AccountMail;
use App\Utils\UserService;
use cs_environment;
use cs_user_item;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UserCreatorFacade
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $legacyEnvironment,
        private readonly AccountCreatorFacade $accountFacade,
        private readonly UserService $userService,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordEncoder,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Mailer $mailer,
        private readonly AccountMail $accountMail
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
    ): cs_user_item {
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

        $roomSlugRepository = $this->entityManager->getRepository(RoomSlug::class);

        // map room slugs to actual room IDs
        $roomIds = array_map(function (string $roomSlug) use ($roomSlugRepository, $account) {
            /** @var RoomSlug $slug */
            $slug = $roomSlugRepository->findOneBy(['slug' => trim($roomSlug)]);
            $room = $slug->getRoom();
            if ($room->getContextId() == $account->getContextId()) {
                return $room->getItemId();
            }

            return null;
        }, $roomSlugs);

        // filter out any null values (where a room slug couldn't be mapped to an actual room ID)
        $roomIds = array_filter($roomIds);

        // create room users
        $portalUser = $this->userService->getPortalUser($account);
        $this->addUserToRoomsWithIds($portalUser, $roomIds, 2, true);
    }

    /**
     * Adds users representing the given user to the rooms with the given IDs.
     *
     * @param cs_user_item $user the user for whom room users shall be created
     * @param array $roomIds list of room IDs
     * @param int|null $userStatus the room user's status (0: locked, 1: applying, 2: user, 3: moderator, 4: read-only);
     * defaults to 1, or 2 if the room has disabled member access checks
     * @param bool $informUser whether the user shall be informed via email about the newly added room user and its
     * status (true) or not (false); defaults to false
     */
    private function addUserToRoomsWithIds(
        cs_user_item $user,
        array $roomIds,
        int $userStatus = null,
        bool $informUser = false
    ): void
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $privateRoomUser = $user->getRelatedPrivateRoomUserItem();

        foreach ($roomIds as $roomId) {
            $room = $roomManager->getItem($roomId);

            if ($room) {
                // NOTE: userroom creation (plus the involved user cloning) & choosing appropriate email texts when
                //       sending info emails requires the current context to be set, so we set the context explicitly
                //       here (otherwise it may not have been set (yet), e.g. when auto-creating room users on login)
                $oldContextId = $this->legacyEnvironment->getCurrentContextID();
                $this->legacyEnvironment->setCurrentContextID($roomId);

                $relatedUserInContext = $user->getRelatedUserItemInContext($roomId);
                if (!$relatedUserInContext) {
                    // determine the source user to clone from
                    $sourceUser = $privateRoomUser ?: $user;

                    $newUserItem = $sourceUser->cloneData();
                    $newUserItem->setContextID($roomId);

                    // user status
                    if (null !== $userStatus) {
                        $userStatus = filter_var($userStatus, FILTER_VALIDATE_INT, [
                            'options' => ['min_range' => 0, 'max_range' => 4]
                        ]);
                    }
                    if (false === $userStatus || null === $userStatus) {
                        // default user status: 1 (applying), or 2 (user) if the room has disabled member access checks
                        $userStatus = $room->checkNewMembersNever() ? 2 : 1;
                    }
                    $newUserItem->setStatus($userStatus);

                    $newUserItem->save();

                    // if necessary, trigger creation of user rooms
                    $event = new UserJoinedRoomEvent($newUserItem, $room);
                    $this->eventDispatcher->dispatch($event);

                    // task
                    if ($newUserItem->isRequested()) {
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

                    // send email about user status change
                    if ($informUser && !$newUserItem->isRequested()) {
                        $userIds = [$newUserItem->getItemID()];
                        $actions = [
                            0 => 'user-block',
                            2 => 'user-status-user',
                            3 => 'user-status-moderator',
                            4 => 'user-status-reading-user'
                        ];

                        // NOTE: email texts are provided by the legacy translator which depends on a correct context
                        $this->userService->sendUserInfoMail($this->mailer, $this->accountMail, $userIds, $actions[$userStatus]);
                    }
                }

                $this->legacyEnvironment->setCurrentContextID($oldContextId);
            }
        }
    }
}
