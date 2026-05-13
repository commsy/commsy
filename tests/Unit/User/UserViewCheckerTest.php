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

namespace Tests\Unit\User;

use App\Entity\Room;
use App\Entity\User;
use App\Security\Permission\Checker\ItemViewChecker;
use App\Security\Permission\Subject\ItemViewSubject;
use App\Security\Permission\Subject\ItemViewSubjectFactory;
use App\User\UserViewChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UserViewCheckerTest extends TestCase
{
    private ItemViewSubjectFactory&MockObject $subjectFactory;
    private ItemViewChecker&MockObject $itemViewChecker;
    private UserViewChecker $checker;

    protected function setUp(): void
    {
        $this->subjectFactory = $this->createMock(ItemViewSubjectFactory::class);
        $this->itemViewChecker = $this->createMock(ItemViewChecker::class);
        $this->checker = new UserViewChecker($this->subjectFactory, $this->itemViewChecker);
    }

    // ---- Community-room branch

    public function testCommunityRoomGrantsRoot(): void
    {
        $room = $this->communityRoom(itemId: 7);
        $actor = (new User())->setStatus(3)->setUserId('root');
        $target = $this->user(contextId: 7, userId: 'bob', visible: 1);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testCommunityRoomGuestSeesOnlyVisibleForAllTargets(): void
    {
        $room = $this->communityRoom(itemId: 7);
        $guest = $this->user(contextId: 7, userId: 'guest', status: 0);

        $visibleForAll = $this->user(contextId: 7, userId: 'bob', visible: 2);
        $visibleForLoggedIn = $this->user(contextId: 7, userId: 'carol', visible: 1);

        self::assertTrue($this->checker->canSee($guest, $visibleForAll, $room));
        self::assertFalse($this->checker->canSee($guest, $visibleForLoggedIn, $room));
    }

    public function testCommunityRoomLoggedInUserSeesAnyTargetInSameContext(): void
    {
        $room = $this->communityRoom(itemId: 7);
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob', visible: 1);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testCommunityRoomDeniesWhenActorContextDiffersFromTargetAndRoom(): void
    {
        $room = $this->communityRoom(itemId: 7);
        // Actor in unrelated context 99, target in 42, room is 7 — no match.
        $actor = $this->user(contextId: 99, userId: 'alice', status: 2);
        $target = $this->user(contextId: 42, userId: 'bob', visible: 1);

        self::assertFalse($this->checker->canSee($actor, $target, $room));
    }

    public function testCommunityRoomGrantsSelfWhenContextsAlign(): void
    {
        $room = $this->communityRoom(itemId: 7);
        // Actor is a guest in the same room (status 0 + visible 1 target
        // would otherwise deny via the guest-only-visible-for-all branch),
        // but actor & target share the (userId, authSource) pair so the
        // legacy self sub-condition grants — same context lets us in.
        $actor = $this->user(contextId: 7, userId: 'alice', authSource: 5, status: 0);
        $target = $this->user(contextId: 7, userId: 'alice', authSource: 5, visible: 1);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testCommunityRoomDeniesSelfAcrossContextMismatch(): void
    {
        $room = $this->communityRoom(itemId: 7);
        // Same identity but actor's context doesn't match the target's
        // context OR the current room — legacy precondition fails first.
        $actor = $this->user(contextId: 99, userId: 'alice', authSource: 5, status: 1);
        $target = $this->user(contextId: 42, userId: 'alice', authSource: 5, visible: 1);

        self::assertFalse(
            $this->checker->canSee($actor, $target, $room),
            'Self-recognition requires the context precondition to hold',
        );
    }

    public function testCommunityRoomGrantsModerator(): void
    {
        $room = $this->communityRoom(itemId: 7);
        $actor = $this->user(contextId: 7, userId: 'mod', status: 3);
        $target = $this->user(contextId: 7, userId: 'bob', visible: 1);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    // ---- Default branch (non-community)

    public function testDefaultBranchDeniesWhenItemViewCheckerDenies(): void
    {
        $room = $this->projectRoom(itemId: 7);
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(false);

        self::assertFalse($this->checker->canSee($actor, $target, $room));
    }

    public function testDefaultBranchGrantsForPrivateRoom(): void
    {
        $room = $this->privateRoom(itemId: 7);
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testDefaultBranchGrantsWhenRoomHasUserRubric(): void
    {
        $room = $this->projectRoom(itemId: 7, homeConf: 'material_grid,user_view,date_list');
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testDefaultBranchHidesOtherUsersWhenRubricIsOff(): void
    {
        $room = $this->projectRoom(itemId: 7, homeConf: 'material_grid,date_list');  // no 'user'
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertFalse(
            $this->checker->canSee($actor, $target, $room),
            'User rubric off + viewer is not self/moderator → hide',
        );
    }

    public function testDefaultBranchAlwaysShowsSelfEvenWhenRubricIsOff(): void
    {
        $room = $this->projectRoom(itemId: 7, homeConf: 'material_grid');
        $actor = $this->user(contextId: 7, userId: 'alice', authSource: 5, status: 2);
        $target = $this->user(contextId: 7, userId: 'alice', authSource: 5);

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testDefaultBranchShowsToModeratorEvenWhenRubricIsOff(): void
    {
        $room = $this->projectRoom(itemId: 7, homeConf: 'material_grid');
        $actor = $this->user(contextId: 7, userId: 'mod', status: 3);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, $room));
    }

    public function testPortalLevelBrowseGrantsWhenItemViewCheckerGrants(): void
    {
        // Null currentRoom = legacy currentContextItem was a Portal
        $actor = $this->user(contextId: 7, userId: 'alice', status: 2);
        $target = $this->user(contextId: 7, userId: 'bob');

        $this->subjectFactory->method('fromItem')->willReturn($this->subject());
        $this->itemViewChecker->method('canSee')->willReturn(true);

        self::assertTrue($this->checker->canSee($actor, $target, currentRoom: null));
    }

    // ---- helpers

    private function user(
        int $contextId,
        string $userId,
        int $status = 2,
        int $authSource = 1,
        int $visible = 1,
        int $itemId = 0,
    ): User {
        $u = (new User())
            ->setStatus($status)
            ->setUserId($userId)
            ->setAuthSource($authSource)
            ->setVisible($visible);
        $u->itemId = $itemId !== 0 ? $itemId : crc32($userId . '-' . $contextId);
        $u->setRoom((new Room())->setItemId($contextId));
        return $u;
    }

    private function communityRoom(int $itemId): Room
    {
        return (new Room())->setItemId($itemId)->setType('community');
    }

    private function projectRoom(int $itemId, string $homeConf = 'user_view,material_grid'): Room
    {
        return (new Room())
            ->setItemId($itemId)
            ->setType('project')
            ->setExtras(['HOMECONF' => $homeConf]);
    }

    private function privateRoom(int $itemId): Room
    {
        return (new Room())->setItemId($itemId)->setType('privateroom');
    }

    private function subject(): ItemViewSubject
    {
        return new ItemViewSubject(
            itemId: 1,
            contextId: 7,
            creatorId: null,
            isDeactivated: false,
            contextIsDeleted: false,
        );
    }
}
