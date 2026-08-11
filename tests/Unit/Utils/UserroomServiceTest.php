<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Room\RoomManager;
use App\Room\UserRoomDeleter;
use App\Services\CurrentUserResolver;
use App\Services\LegacyEnvironment;
use App\User\UserMembershipDeleter;
use App\Utils\RoomService;
use App\Utils\UserroomService;
use App\Utils\UserService;
use cs_environment;
use cs_project_item;
use cs_user_item;
use cs_userroom_item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A user room is created as soon as someone applies for membership in the project
 * room, not when the application is granted. Its owner therefore has to carry the
 * applicant's status: with a fixed "regular user" an applicant could enter the user
 * room and create entries while the application was still pending, even though the
 * project room above stayed closed to them (RT #1526633).
 */
class UserroomServiceTest extends TestCase
{
    private MockObject $userService;
    private MockObject $roomManager;

    /**
     * status 1 — applied for membership, not granted
     */
    public function testTheOwnerOfAFreshUserRoomKeepsAPendingStatus(): void
    {
        $this->expectClonedWithStatus(1);

        $this->service()->createUserroom($this->projectRoom(), $this->user(status: 1));
    }

    /**
     * status 2 — a granted membership still yields a usable user room
     */
    public function testAGrantedMembershipStillOwnsItsUserRoom(): void
    {
        $this->expectClonedWithStatus(2);

        $this->service()->createUserroom($this->projectRoom(), $this->user(status: 2));
    }

    /**
     * The counterpart to the two tests above: creating the room with a pending status
     * is only safe because approval raises it again. Nothing in this chain is new — it
     * is pinned here because the fix above now depends on it. If it broke, applicants
     * would be approved in the project room and locked out of their own user room.
     */
    public function testApprovalRaisesTheOwnerStatusInTheUserRoom(): void
    {
        $owner = $this->createMock(cs_user_item::class);
        $owner->expects(self::once())->method('setStatus')->with(2);
        $owner->expects(self::once())->method('save');

        $approved = $this->user(status: 2);
        $userroom = $this->createMock(cs_userroom_item::class);
        $userroom->method('getItemID')->willReturn(4711);
        $userroom->method('getLinkedUserItem')->willReturn($approved);

        $projectUser = $this->user(status: 2);
        $projectUser->method('getLinkedUserroomItem')->willReturn($userroom);

        // the owner entry inside the user room represents the approved project user
        $owner->method('getLinkedProjectUserItem')->willReturn($approved);

        $this->userService->method('getListUsers')->willReturnCallback(
            fn (int $roomId): array => 4711 === $roomId ? [$owner] : [$projectUser]
        );

        $room = $this->createMock(cs_project_item::class);
        $room->method('getItemID')->willReturn(105);

        $this->service()->changeUserStatusInUserroomsForRoom($room, $approved);
    }

    protected function setUp(): void
    {
        $this->userService = $this->createMock(UserService::class);
        $this->userService->method('getModeratorsForContext')->willReturn([]);

        $userroom = $this->createMock(cs_userroom_item::class);
        $userroom->method('getItemID')->willReturn(4711);

        $this->roomManager = $this->createMock(RoomManager::class);
        $this->roomManager->method('createRoom')->willReturn($userroom);
    }

    private function expectClonedWithStatus(int $status): void
    {
        $this->userService->expects(self::once())
            ->method('cloneUser')
            ->with(self::anything(), 4711, $status)
            ->willReturn($this->createMock(cs_user_item::class));
    }

    private function service(): UserroomService
    {
        $legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $legacyEnvironment->method('getEnvironment')->willReturn($this->createMock(cs_environment::class));

        return new UserroomService(
            $legacyEnvironment,
            $this->createMock(RoomService::class),
            $this->userService,
            $this->roomManager,
            $this->createMock(UserMembershipDeleter::class),
            $this->createMock(UserRoomDeleter::class),
            $this->createMock(CurrentUserResolver::class)
        );
    }

    private function projectRoom(): cs_project_item
    {
        $room = $this->createMock(cs_project_item::class);
        $room->method('getItemID')->willReturn(105);
        $room->method('getContextID')->willReturn(99);
        $room->method('getTitle')->willReturn('Projektraum');

        return $room;
    }

    private function user(int $status): cs_user_item
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getItemID')->willReturn(123);
        $user->method('getFullName')->willReturn('Erika Mustermann');
        $user->method('getStatus')->willReturn($status);

        return $user;
    }
}
