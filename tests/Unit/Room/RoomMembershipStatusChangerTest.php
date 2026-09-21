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

use App\Room\RoomMembershipStatus;
use App\Room\RoomMembershipStatusChanger;
use App\Utils\UserService;
use cs_user_item;
use PHPUnit\Framework\TestCase;

/**
 * Pins the cascade rule the user list has always followed and the notification
 * bell now shares: blocking reaches into the group rooms, anything else only
 * when it lifts a block.
 */
class RoomMembershipStatusChangerTest extends TestCase
{
    public function testBlockingAlwaysCascadesIntoTheGroupRooms(): void
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getStatus')->willReturn(2);
        $user->expects(self::once())->method('reject');
        $user->expects(self::once())->method('save');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())->method('propagateStatusToGrouproomUsersForUser')->with($user);

        (new RoomMembershipStatusChanger($userService))->changeTo($user, RoomMembershipStatus::Blocked);
    }

    public function testLiftingABlockCascadesSoTheGroupRoomsFollowBack(): void
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getStatus')->willReturn(0);
        $user->expects(self::once())->method('makeUser');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::once())->method('propagateStatusToGrouproomUsersForUser')->with($user);

        (new RoomMembershipStatusChanger($userService))->changeTo($user, RoomMembershipStatus::User);
    }

    /**
     * Accepting a join request is exactly this case: status 1 becomes 2, so a
     * status somebody was deliberately given inside a group room stays put.
     */
    public function testAChangeThatDoesNotLiftABlockLeavesTheGroupRoomsAlone(): void
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getStatus')->willReturn(1);
        $user->expects(self::once())->method('makeUser');

        $userService = $this->createMock(UserService::class);
        $userService->expects(self::never())->method('propagateStatusToGrouproomUsersForUser');

        (new RoomMembershipStatusChanger($userService))->changeTo($user, RoomMembershipStatus::User);
    }

    public function testEveryStatusMapsToItsLegacyCall(): void
    {
        foreach ([
            [RoomMembershipStatus::Requested, 'request'],
            [RoomMembershipStatus::Moderator, 'makeModerator'],
            [RoomMembershipStatus::ReadOnly, 'makeReadOnlyUser'],
        ] as [$status, $method]) {
            $user = $this->createMock(cs_user_item::class);
            $user->method('getStatus')->willReturn(2);
            $user->expects(self::once())->method($method);

            (new RoomMembershipStatusChanger($this->createMock(UserService::class)))->changeTo($user, $status);
        }
    }
}
