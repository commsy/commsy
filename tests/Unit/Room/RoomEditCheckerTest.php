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
use App\Room\RoomEditChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RoomEditCheckerTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private RoomEditChecker $checker;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->checker = new RoomEditChecker($this->userRepository);
    }

    // ---- canEdit

    public function testDeniesReadOnlyActor(): void
    {
        $actor = $this->user(itemId: 5, status: 4, contextId: 42);
        $target = $this->room(itemId: 42, type: 'project');

        self::assertFalse($this->checker->canEdit($actor, $target));
    }

    public function testGrantsRoot(): void
    {
        $actor = (new User())->setStatus(3)->setUserId('root');
        $target = $this->room(itemId: 42, type: 'project');

        self::assertTrue($this->checker->canEdit($actor, $target));
    }

    public function testDeniesNonUserActor(): void
    {
        // Status 0 = guest, isUser is false.
        $actor = $this->user(itemId: 5, status: 0, contextId: 42);
        $target = $this->room(itemId: 42, type: 'project');

        self::assertFalse($this->checker->canEdit($actor, $target));
    }

    public function testGrantsCreator(): void
    {
        $actor = $this->user(itemId: 99, status: 2, contextId: 42);
        $target = $this->room(itemId: 42, type: 'project');
        $target->setCreator($actor);

        self::assertTrue($this->checker->canEdit($actor, $target));
    }

    public function testGrantsRoomModeratorInSameContext(): void
    {
        // Actor's context == target room id, status 3 (moderator)
        $actor = $this->user(itemId: 5, status: 3, contextId: 42);
        $target = $this->room(itemId: 42, type: 'project');

        self::assertTrue($this->checker->canEdit($actor, $target));
    }

    public function testGrantsRoomModeratorViaCrossContextLookup(): void
    {
        // Actor is portal-level (contextId 7), target room is 42.
        // UserRepository finds an in-context membership with status 3.
        $portalActor = $this->user(itemId: 5, status: 2, contextId: 7);
        $portalActor->setAccount(new Account());

        $roomMembership = $this->user(itemId: 105, status: 3, contextId: 42);
        $this->userRepository
            ->expects(self::once())
            ->method('findInContext')
            ->willReturn($roomMembership);

        $target = $this->room(itemId: 42, type: 'project');
        self::assertTrue($this->checker->canEdit($portalActor, $target));
    }

    public function testDeniesNonMemberNonCreator(): void
    {
        $actor = $this->user(itemId: 5, status: 2, contextId: 7);
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        $target = $this->room(itemId: 42, type: 'project');
        self::assertFalse($this->checker->canEdit($actor, $target));
    }

    public function testCrossContextModeratorOverrideCommunityToProject(): void
    {
        // Actor is a moderator (status 3) in their own context (some community),
        // viewing a project room. The legacy `inCommunityRoom AND isProjectRoom
        // AND isModerator` branch grants.
        $actor = $this->user(itemId: 5, status: 3, contextId: 99);
        $target = $this->room(itemId: 42, type: 'project');
        $community = $this->room(itemId: 99, type: 'community');

        self::assertTrue($this->checker->canEdit($actor, $target, $community));
    }

    public function testCrossContextOverrideDoesNotFireWithoutCommunityContext(): void
    {
        // Same setup but currentContext is a project room: override
        // should NOT fire — we need to be browsing a community room.
        $actor = $this->user(itemId: 5, status: 3, contextId: 99);
        $actor->setAccount(new Account());
        $this->userRepository->method('findInContext')->willReturn(null);

        $target = $this->room(itemId: 42, type: 'project');
        $project = $this->room(itemId: 99, type: 'project');

        self::assertFalse($this->checker->canEdit($actor, $target, $project));
    }

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

    private function room(int $itemId, string $type): Room
    {
        return (new Room())->setItemId($itemId)->setType($type);
    }
}
