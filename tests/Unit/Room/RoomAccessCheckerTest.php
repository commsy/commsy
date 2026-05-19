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

declare(strict_types=1);

namespace Tests\Unit\Room;

use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Room\RoomAccessChecker;
use App\Room\RoomStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Pure-function tests for RoomAccessChecker. No kernel boot, no DB —
 * we mock UserRepository and inject hand-built Doctrine entities.
 *
 * The integration coverage of these same paths comes from
 * ItemVoterEnterTest (already in place from Phase 1) — those tests will
 * exercise the real code path once the Voter is wired to this checker
 * in Phase 3.
 */
final class RoomAccessCheckerTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private RoomAccessChecker $checker;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        // ArrayAdapter is Symfony's request-scoped in-memory cache; a
        // fresh instance per test gives us a clean slate without having
        // to mock the CacheInterface.
        $this->checker = new RoomAccessChecker($this->userRepository, new ArrayAdapter());
    }

    // ---- canEnter(Account, Room) ----

    public function testRootAccountCanEnterAnyRoom(): void
    {
        $room = $this->room(type: 'project');
        $this->userRepository->expects(self::never())->method('findInContext');

        self::assertTrue($this->checker->canEnter($this->account('root'), $room));
    }

    public function testRootAccountCanEnterEvenLockedRoom(): void
    {
        $room = $this->room(type: 'project', status: RoomStatus::LOCKED->value);

        self::assertTrue($this->checker->canEnter($this->account('root'), $room));
    }

    public function testLockedRoomBlocksNonRoot(): void
    {
        $room = $this->room(type: 'project', status: RoomStatus::LOCKED->value);
        $this->userRepository->expects(self::never())->method('findInContext');

        self::assertFalse($this->checker->canEnter($this->account('alice'), $room));
    }

    public function testLockedByModeratorRoomBlocksNonRoot(): void
    {
        $room = $this->room(type: 'project', status: RoomStatus::LOCKED_PORTAL_MOD->value);

        self::assertFalse($this->checker->canEnter($this->account('alice'), $room));
    }

    public function testCommunityRoomWithGuestAccessAcceptsNonMember(): void
    {
        $room = $this->room(type: 'community', openForGuests: true);
        $this->userRepository->expects(self::never())->method('findInContext');

        self::assertTrue($this->checker->canEnter($this->account('alice'), $room));
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function nonCommunityRoomTypes(): iterable
    {
        // Legacy quirk: cs_project_item / cs_grouproom_item / cs_userroom_item
        // override isOpenForGuests() to hardcoded false. Only cs_community_item
        // honors the column.
        yield 'project' => ['project'];
        yield 'grouproom' => ['grouproom'];
        yield 'userroom' => ['userroom'];
        yield 'privateroom' => ['privateroom'];
    }

    #[DataProvider('nonCommunityRoomTypes')]
    public function testGuestAccessFlagIsIgnoredOnNonCommunityRooms(string $type): void
    {
        $room = $this->room(type: $type, openForGuests: true);
        $this->userRepository->method('findInContext')->willReturn(null);

        self::assertFalse(
            $this->checker->canEnter($this->account('alice'), $room),
            sprintf('"%s" type rooms must not honor openForGuests (legacy hardcoded false)', $type),
        );
    }

    public function testRegularMemberCanEnter(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $alice = $this->account('alice');
        $membership = $this->member(status: 2);
        $this->userRepository
            ->expects(self::once())
            ->method('findInContext')
            ->with($alice, 42)
            ->willReturn($membership);

        self::assertTrue($this->checker->canEnter($alice, $room));
    }

    public function testModeratorCanEnter(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $this->userRepository->method('findInContext')->willReturn($this->member(status: 3));

        self::assertTrue($this->checker->canEnter($this->account('mod'), $room));
    }

    public function testReadOnlyMemberCanEnter(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $this->userRepository->method('findInContext')->willReturn($this->member(status: 4));

        self::assertTrue($this->checker->canEnter($this->account('reader'), $room));
    }

    public function testRequestedMemberCannotEnter(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $this->userRepository->method('findInContext')->willReturn($this->member(status: 1));

        self::assertFalse(
            $this->checker->canEnter($this->account('alice'), $room),
            'Status 1 (membership requested) is not yet a member — User::isUser() filters status>=2',
        );
    }

    public function testNonMemberCannotEnterRegularRoom(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $this->userRepository->method('findInContext')->willReturn(null);

        self::assertFalse($this->checker->canEnter($this->account('stranger'), $room));
    }

    // ---- canEnterByUserItemId(int, Room) ----

    public function testCanEnterByUserItemIdForMembershipInTargetRoom(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $member = $this->member(status: 2, contextRoom: $room);
        $this->userRepository->method('find')->with(7)->willReturn($member);

        self::assertTrue($this->checker->canEnterByUserItemId(7, $room));
    }

    public function testCanEnterByUserItemIdRejectsLockedRoom(): void
    {
        $room = $this->room(itemId: 42, type: 'project', status: RoomStatus::LOCKED->value);
        $this->userRepository->expects(self::never())->method('find');

        self::assertFalse($this->checker->canEnterByUserItemId(7, $room));
    }

    public function testCanEnterByUserItemIdAcceptsCommunityWithGuestAccess(): void
    {
        $room = $this->room(itemId: 42, type: 'community', openForGuests: true);
        $this->userRepository->expects(self::never())->method('find');

        self::assertTrue($this->checker->canEnterByUserItemId(7, $room));
    }

    public function testCanEnterByUserItemIdRejectsUnknownItemId(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $this->userRepository->method('find')->with(999)->willReturn(null);

        self::assertFalse($this->checker->canEnterByUserItemId(999, $room));
    }

    public function testCanEnterByUserItemIdRejectsUserItemFromDifferentRoom(): void
    {
        $targetRoom = $this->room(itemId: 42, type: 'project');
        $otherRoom = $this->room(itemId: 99, type: 'project');
        $member = $this->member(status: 2, contextRoom: $otherRoom);
        $this->userRepository->method('find')->willReturn($member);

        self::assertFalse(
            $this->checker->canEnterByUserItemId(7, $targetRoom),
            'A user_item that belongs to a different room must not grant access here',
        );
    }

    public function testCanEnterByUserItemIdRejectsRequestedStatus(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $member = $this->member(status: 1, contextRoom: $room);
        $this->userRepository->method('find')->willReturn($member);

        self::assertFalse($this->checker->canEnterByUserItemId(7, $room));
    }

    // ---- request-scoped memoisation ----

    public function testRepeatedCanEnterCallsHitTheCache(): void
    {
        $room = $this->room(itemId: 42, type: 'project');
        $account = $this->account('alice');

        // expects(self::once()): if the cache misses on the second call,
        // findInContext would be invoked twice and the mock fails.
        $this->userRepository
            ->expects(self::once())
            ->method('findInContext')
            ->willReturn($this->member(status: 2));

        self::assertTrue($this->checker->canEnter($account, $room));
        self::assertTrue($this->checker->canEnter($account, $room));
        self::assertTrue($this->checker->canEnter($account, $room));
    }

    public function testRepeatedCanEnterByUserItemIdCallsHitTheCache(): void
    {
        $room = $this->room(itemId: 42, type: 'project');

        $this->userRepository
            ->expects(self::once())
            ->method('find')
            ->with(7)
            ->willReturn($this->member(status: 2, contextRoom: $room));

        self::assertTrue($this->checker->canEnterByUserItemId(7, $room));
        self::assertTrue($this->checker->canEnterByUserItemId(7, $room));
    }

    /**
     * Regression for the request-scoped cache key: it must include the
     * room id, so a verdict cached for one room is never served for a
     * different room that happens to share the same user_item id.
     * (Replaces the cross-room isolation coverage lost when the
     * legacy-identity cache tests were removed.)
     */
    public function testCanEnterByUserItemIdCacheKeyIsolatesDifferentRoomsForSameUser(): void
    {
        $roomA = $this->room(itemId: 42, type: 'project');
        $roomB = $this->room(itemId: 99, type: 'project');

        // Same user_item id 7, but the row belongs to room A only.
        $this->userRepository
            ->method('find')
            ->with(7)
            ->willReturn($this->member(status: 2, contextRoom: $roomA));

        self::assertTrue($this->checker->canEnterByUserItemId(7, $roomA));
        self::assertFalse(
            $this->checker->canEnterByUserItemId(7, $roomB),
            'A verdict cached for one room must not leak to another room with the same user_item id',
        );
    }

    // ---- helpers ----

    private function account(string $username): Account
    {
        return (new Account())->setUsername($username);
    }

    private function room(
        int $itemId = 1,
        string $type = 'project',
        string|int $status = '1',
        bool $openForGuests = false,
    ): Room {
        return (new Room())
            ->setItemId($itemId)
            ->setType($type)
            ->setStatus((string) $status)
            ->setOpenForGuests($openForGuests);
    }

    private function member(int $status, ?Room $contextRoom = null): User
    {
        $user = (new User())->setStatus($status);
        if ($contextRoom !== null) {
            $user->setRoom($contextRoom);
        }
        return $user;
    }
}
