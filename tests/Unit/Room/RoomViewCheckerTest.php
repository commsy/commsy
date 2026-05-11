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
use App\Repository\RoomRepository;
use App\Repository\UserRepository;
use App\Room\RoomViewChecker;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubject;
use App\Security\Permission\Subject\ItemViewSubjectFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RoomViewCheckerTest extends TestCase
{
    private ItemViewChecker&MockObject $itemViewChecker;
    private ItemViewSubjectFactory&MockObject $subjectFactory;
    private RoomRepository&MockObject $roomRepository;
    private UserRepository&MockObject $userRepository;
    private RoomViewChecker $checker;

    protected function setUp(): void
    {
        $this->itemViewChecker = $this->createMock(ItemViewChecker::class);
        $this->subjectFactory = $this->createMock(ItemViewSubjectFactory::class);
        $this->roomRepository = $this->createMock(RoomRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->checker = new RoomViewChecker(
            $this->itemViewChecker,
            $this->subjectFactory,
            $this->roomRepository,
            $this->userRepository,
        );
    }

    // ---- Project room

    public function testProjectRoomGrantsRoot(): void
    {
        $target = $this->room(42, 'project');
        $current = $this->room(42, 'project');
        $root = (new User())->setStatus(3)->setUserId('root');

        self::assertTrue($this->checker->canSee($root, $target, $current));
    }

    public function testProjectRoomGrantsInContextUser(): void
    {
        $target = $this->room(42, 'project');
        $current = $this->room(42, 'project');
        $actor = $this->user(itemId: 5, status: 2, contextId: 42);

        self::assertTrue($this->checker->canSee($actor, $target, $current));
    }

    public function testProjectRoomGrantsInContextGuest(): void
    {
        $target = $this->room(42, 'project');
        $current = $this->room(42, 'project');
        $guest = $this->user(itemId: 5, status: 0, contextId: 42);

        self::assertTrue($this->checker->canSee($guest, $target, $current));
    }

    public function testProjectRoomGrantsWhenCurrentRoomIsOpenForGuests(): void
    {
        $target = $this->room(42, 'project');
        $current = $this->room(99, 'project', openForGuests: true);
        $stranger = $this->user(itemId: 5, status: 2, contextId: 7);

        self::assertTrue($this->checker->canSee($stranger, $target, $current));
    }

    public function testProjectRoomDeniesAtPortalLevelForNonRoot(): void
    {
        $target = $this->room(42, 'project');
        $actor = $this->user(itemId: 5, status: 2, contextId: 7);

        self::assertFalse($this->checker->canSee($actor, $target, currentContext: null));
    }

    // ---- Group room

    public function testGroupRoomGrantsRoot(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $root = (new User())->setStatus(3)->setUserId('root');

        self::assertTrue($this->checker->canSee($root, $target));
    }

    public function testGroupRoomGrantsUserInLinkedProject(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $actor = $this->user(itemId: 5, status: 2, contextId: 42);

        self::assertTrue($this->checker->canSee($actor, $target));
    }

    public function testGroupRoomGrantsGuestInOpenLinkedProject(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $guest = $this->user(itemId: 5, status: 0, contextId: 42);

        $linkedProject = $this->room(42, 'project', openForGuests: true);
        $this->roomRepository->method('find')->with(42)->willReturn($linkedProject);

        self::assertTrue($this->checker->canSee($guest, $target));
    }

    public function testGroupRoomDeniesGuestInClosedLinkedProject(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $guest = $this->user(itemId: 5, status: 0, contextId: 42);

        $linkedProject = $this->room(42, 'project', openForGuests: false);
        $this->roomRepository->method('find')->willReturn($linkedProject);

        self::assertFalse($this->checker->canSee($guest, $target));
    }

    public function testGroupRoomGrantsMemberWhenBrowsingFromPrivateRoom(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $privateRoom = $this->room(70, 'privateroom');

        // Actor lives elsewhere (context 7), so the linked-project
        // branch doesn't grant — fall back to the private-room branch.
        $actor = $this->user(itemId: 5, status: 2, contextId: 7);
        $actor->setAccount(new Account());

        // findInContext returns a membership in the grouproom — actor IS a member.
        $membership = $this->user(itemId: 105, status: 2, contextId: 50);
        $this->userRepository->method('findInContext')->willReturn($membership);

        self::assertTrue($this->checker->canSee($actor, $target, $privateRoom));
    }

    public function testGroupRoomDeniesWhenAllBranchesFail(): void
    {
        $target = $this->room(50, 'grouproom', extras: ['PROJECT_ROOM_ITEM_ID' => 42]);
        $actor = $this->user(itemId: 5, status: 2, contextId: 7);
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        self::assertFalse($this->checker->canSee($actor, $target));
    }

    // ---- User room

    public function testUserRoomGrantsRoot(): void
    {
        $target = $this->room(60, 'userroom', extras: ['USER_ITEM_ID' => 999]);
        $root = (new User())->setStatus(3)->setUserId('root');

        self::assertTrue($this->checker->canSee($root, $target));
    }

    public function testUserRoomGrantsModerator(): void
    {
        $target = $this->room(60, 'userroom', extras: ['USER_ITEM_ID' => 999]);
        $mod = $this->user(itemId: 5, status: 3, contextId: 7);

        self::assertTrue(
            $this->checker->canSee($mod, $target),
            'Legacy quirk: ANY moderator may see ANY userroom, not just in-context',
        );
    }

    public function testUserRoomGrantsLinkedOwner(): void
    {
        $target = $this->room(60, 'userroom', extras: ['USER_ITEM_ID' => 999]);
        $owner = $this->user(itemId: 999, status: 2, contextId: 42);

        self::assertTrue($this->checker->canSee($owner, $target));
    }

    public function testUserRoomDeniesStrangers(): void
    {
        $target = $this->room(60, 'userroom', extras: ['USER_ITEM_ID' => 999]);
        $stranger = $this->user(itemId: 5, status: 2, contextId: 7);

        self::assertFalse($this->checker->canSee($stranger, $target));
    }

    // ---- Community / private (fall through to ItemViewChecker)

    public function testCommunityRoomDefersToItemViewChecker(): void
    {
        $target = $this->room(80, 'community');
        $current = $this->room(80, 'community');
        $actor = $this->user(itemId: 5, status: 2, contextId: 80);

        $subject = $this->subject();
        $this->subjectFactory->method('fromItem')->willReturn($subject);
        $this->itemViewChecker
            ->expects(self::once())
            ->method('canSee')
            ->with($actor, $subject, $current)
            ->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, $current));
    }

    public function testPrivateRoomDefersToItemViewChecker(): void
    {
        $target = $this->room(90, 'privateroom');
        $actor = $this->user(itemId: 5, status: 2, contextId: 90);

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(false);

        self::assertFalse($this->checker->canSee($actor, $target));
    }

    // ---- helpers

    private function user(int $itemId, int $status, int $contextId): User
    {
        $u = (new User())
            ->setStatus($status)
            ->setUserId('user-' . $itemId)
            ->setAuthSource(1);
        $u->itemId = $itemId;
        $u->setRoom((new Room())->setItemId($contextId));
        return $u;
    }

    private function room(int $itemId, string $type, bool $openForGuests = false, array $extras = []): Room
    {
        $r = (new Room())
            ->setItemId($itemId)
            ->setType($type)
            ->setOpenForGuests($openForGuests);
        if ($extras !== []) {
            $r->setExtras($extras);
        }
        return $r;
    }

    private function subject(): ItemViewSubject
    {
        return new ItemViewSubject(
            itemId: 1,
            contextId: 1,
            creatorId: null,
            isDeactivated: false,
            contextIsDeleted: false,
        );
    }
}
