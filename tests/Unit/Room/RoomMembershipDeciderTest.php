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

namespace Tests\Unit\Room;

use App\Event\UserStatusChangedEvent;
use App\Room\RoomMembershipDecider;
use App\Room\RoomMembershipStatusChanger;
use App\Security\Authorization\Voter\UserVoter;
use App\Utils\UserService;
use cs_user_item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Covers what the notification bell triggers when a moderator accepts or rejects
 * a join request inline: the membership really changes, the change is announced
 * (which is what clears the task and tells the requester), and neither a stale
 * button nor a moderator of some other room can push it through.
 *
 * The status changer is used for real here rather than mocked — the point of the
 * exercise is that the bell performs the same transition as the user list.
 */
class RoomMembershipDeciderTest extends TestCase
{
    private const ROOM_ID = 42;
    private const REQUESTER_ITEM_ID = 180;

    public function testAcceptingTurnsTheRequesterIntoAUserAndAnnouncesIt(): void
    {
        $user = $this->requester();
        $user->expects(self::once())->method('makeUser');
        $user->expects(self::once())->method('save');

        $userService = $this->userService($user);
        $userService->expects(self::once())->method('updateAllGroupStatus')->with($user, self::ROOM_ID);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')
            ->with(self::isInstanceOf(UserStatusChangedEvent::class))
            ->willReturnArgument(0);

        self::assertTrue($this->decider($userService, $dispatcher)->decide(self::REQUESTER_ITEM_ID, true));
    }

    public function testRejectingBlocksTheRequester(): void
    {
        $user = $this->requester();
        $user->expects(self::once())->method('reject');
        $user->expects(self::never())->method('makeUser');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->willReturnArgument(0);

        self::assertTrue($this->decider($this->userService($user), $dispatcher)->decide(self::REQUESTER_ITEM_ID, false));
    }

    public function testThereIsNothingToDecideForAnUnknownUser(): void
    {
        $userService = $this->createMock(UserService::class);
        $userService->method('getUser')->willReturn(null);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        self::assertFalse($this->decider($userService, $dispatcher)->decide(self::REQUESTER_ITEM_ID, true));
    }

    public function testARequestThatSomebodyElseAlreadyDecidedIsLeftAlone(): void
    {
        $user = $this->requester(stillRequested: false);
        $user->expects(self::never())->method('makeUser');
        $user->expects(self::never())->method('save');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        self::assertFalse($this->decider($this->userService($user), $dispatcher)->decide(self::REQUESTER_ITEM_ID, true));
    }

    /**
     * The bell spans every room of an account, so the check has to name the
     * request's own room — a moderator elsewhere must not get through.
     */
    public function testOnlyAModeratorOfThatRoomMayDecide(): void
    {
        $user = $this->requester();
        $user->expects(self::never())->method('makeUser');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserVoter::ROOM_MODERATOR, self::ROOM_ID)->willReturn(false);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $decider = new RoomMembershipDecider(
            $this->userService($user),
            new RoomMembershipStatusChanger($this->createMock(UserService::class)),
            $security,
            $dispatcher,
        );

        $this->expectException(AccessDeniedException::class);
        $decider->decide(self::REQUESTER_ITEM_ID, true);
    }

    private function requester(bool $stillRequested = true): cs_user_item&MockObject
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getContextID')->willReturn(self::ROOM_ID);
        $user->method('isRequested')->willReturn($stillRequested);
        $user->method('getStatus')->willReturn(1);

        return $user;
    }

    private function userService(cs_user_item $user): UserService&MockObject
    {
        $userService = $this->createMock(UserService::class);
        $userService->method('getUser')->with(self::REQUESTER_ITEM_ID)->willReturn($user);

        return $userService;
    }

    private function decider(UserService&MockObject $userService, EventDispatcherInterface $dispatcher): RoomMembershipDecider
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserVoter::ROOM_MODERATOR, self::ROOM_ID)->willReturn(true);

        return new RoomMembershipDecider(
            $userService,
            new RoomMembershipStatusChanger($userService),
            $security,
            $dispatcher,
        );
    }
}
