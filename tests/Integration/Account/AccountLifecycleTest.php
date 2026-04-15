<?php

declare(strict_types=1);

namespace Tests\Integration\Account;

use App\Account\AccountDeleter;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Facade\MembershipManager;
use App\Repository\UserRepository;
use App\Utils\RoomService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use function Zenstruck\Foundry\Persistence\assert_not_persisted;
use function Zenstruck\Foundry\Persistence\assert_persisted;
use function Zenstruck\Foundry\Persistence\repository;

/**
 * Integration tests for DB state when creating and deleting accounts and user objects.
 */
final class AccountLifecycleTest extends KernelTestCase
{
    #[WithStory(RoomWithMemberStory::class)]
    public function testInitialUserState(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');
        assert_persisted($account);

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');
        assert_persisted($room);

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');
        assert_persisted($roomUser);

        // Check for a valid portal user
        $userRepository = repository(User::class);
        $portalUser = $userRepository->findOneBy([
            'room' => $account->getPortal()->getId(),
            'userId' => $account->getUsername(),
            'portal' => $account->getPortal(),
            'account' => $account,
        ]);
        assert_persisted($portalUser);

        // Check for a valid privateroom user
        $roomRepository = repository(Room::class);

        // This is a little bit too general without a join, but there should only be one privateroom at this moment
        $privateRoom = $roomRepository->findOneBy([
            'type' => 'privateroom',
            'contextId' => $account->getContextId(),
        ]);
        assert_persisted($userRepository->findOneBy([
            'room' => $privateRoom->getContextId(),
            'userId' => $account->getUsername(),
            'portal' => $account->getContextId(),
        ]));

        // Check for a valid room user
        self::assertEquals($room->getItemId(), $roomUser->getContextId());
        self::assertEquals($account->getUsername(), $roomUser->getUserId());
        self::assertEquals($account->getContextId(), $roomUser->getPortal()->getId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAccountDeletion(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');

        /** @var UserRepository $userRepository */
        $userRepository = repository(User::class);

        $numUsersBefore = $userRepository->count([
            'deleterId' => null,
            'deletionDate' => null,
        ]);

        // AccountManager::delete() has been deprecated in favour of AccountDeleter.
        // We call delete() (synchronous) directly instead of dispatch() (async via
        // Messenger), because the following assertions inspect DB state immediately.
        $accountDeleter = self::getContainer()->get(AccountDeleter::class);
        $accountDeleter->delete($account);

        assert_not_persisted($account);

        $numUsersAfter = $userRepository->count([
            'deleterId' => null,
            'deletionDate' => null,
        ]);

        self::assertEquals($numUsersBefore - 3, $numUsersAfter);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testRoomUserDeletion(): void
    {
        self::bootKernel();

        /** @var Account $account */
        $account = RoomWithMemberStory::get('account');

        /** @var Room $room */
        $room = RoomWithMemberStory::get('room');

        /** @var User $roomUser */
        $roomUser = RoomWithMemberStory::get('roomUser');

        /** @var UserRepository $userRepository */
        $userRepository = repository(User::class);

        $numUsersBefore = $userRepository->count([
            'deleterId' => null,
            'deletionDate' => null,
        ]);

        /** @var RoomService $roomService */
        $roomService = self::getContainer()->get(RoomService::class);
        $legacyRoom = $roomService->getRoomItem($room->getItemId());

        $membershipManager = self::getContainer()->get(MembershipManager::class);
        $membershipManager->leaveWorkspace($legacyRoom, $account);

        $numUsersAfter = $userRepository->count([
            'deleterId' => null,
            'deletionDate' => null,
        ]);

        self::assertEquals($numUsersBefore - 1, $numUsersAfter);

        // Refresh doctrine entity, legacy code did bypass unit of work
        self::getContainer()->get(EntityManagerInterface::class)
            ->refresh($roomUser);

        $this->assertNotNull($roomUser->getDeleterId());
        $this->assertNotNull($roomUser->getDeletionDate());
    }
}
