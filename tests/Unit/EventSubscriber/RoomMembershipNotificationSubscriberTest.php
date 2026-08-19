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

namespace Tests\Unit\EventSubscriber;

use App\Event\UserJoinedRoomEvent;
use App\Event\UserStatusChangedEvent;
use App\EventSubscriber\RoomMembershipNotificationSubscriber;
use App\Notification\RoomMembershipNotifier;
use cs_room_item;
use cs_user_item;
use PHPUnit\Framework\TestCase;

class RoomMembershipNotificationSubscriberTest extends TestCase
{
    public function testPendingJoinBecomesATaskForTheModerators(): void
    {
        $notifier = $this->createMock(RoomMembershipNotifier::class);
        $notifier->expects($this->once())
            ->method('requestReceived')
            ->with(105, 42, 'Ada Lovelace');

        $subscriber = new RoomMembershipNotificationSubscriber($notifier);
        $subscriber->onUserJoinedRoom(new UserJoinedRoomEvent(
            $this->user(requested: true, itemId: 42, fullName: 'Ada Lovelace'),
            $this->room(105),
        ));
    }

    public function testDirectJoinCreatesNoTask(): void
    {
        $notifier = $this->createMock(RoomMembershipNotifier::class);
        $notifier->expects($this->never())->method('requestReceived');

        $subscriber = new RoomMembershipNotificationSubscriber($notifier);
        $subscriber->onUserJoinedRoom(new UserJoinedRoomEvent($this->user(requested: false), $this->room(105)));
    }

    public function testConfirmingReportsAnAcceptedDecision(): void
    {
        $notifier = $this->createMock(RoomMembershipNotifier::class);
        $notifier->expects($this->once())
            ->method('requestDecided')
            ->with(105, 42, true);

        $subscriber = new RoomMembershipNotificationSubscriber($notifier);
        $subscriber->onUserStatusChanged(new UserStatusChangedEvent(
            $this->user(requested: false, itemId: 42, contextId: 105)
        ));
    }

    public function testRejectingReportsARejectedDecision(): void
    {
        $notifier = $this->createMock(RoomMembershipNotifier::class);
        $notifier->expects($this->once())
            ->method('requestDecided')
            ->with(105, 42, false);

        $subscriber = new RoomMembershipNotificationSubscriber($notifier);
        $subscriber->onUserStatusChanged(new UserStatusChangedEvent(
            $this->user(requested: false, rejected: true, itemId: 42, contextId: 105)
        ));
    }

    public function testStillPendingIsNoDecision(): void
    {
        $notifier = $this->createMock(RoomMembershipNotifier::class);
        $notifier->expects($this->never())->method('requestDecided');

        $subscriber = new RoomMembershipNotificationSubscriber($notifier);
        $subscriber->onUserStatusChanged(new UserStatusChangedEvent($this->user(requested: true)));
    }

    private function user(
        bool $requested,
        bool $rejected = false,
        int $itemId = 1,
        int $contextId = 1,
        string $fullName = 'Someone',
    ): cs_user_item {
        $user = $this->createMock(cs_user_item::class);
        $user->method('isRequested')->willReturn($requested);
        $user->method('isRejected')->willReturn($rejected);
        $user->method('getItemID')->willReturn($itemId);
        $user->method('getContextID')->willReturn($contextId);
        $user->method('getFullName')->willReturn($fullName);

        return $user;
    }

    private function room(int $itemId): cs_room_item
    {
        $room = $this->createMock(cs_room_item::class);
        $room->method('getItemID')->willReturn($itemId);

        return $room;
    }
}
