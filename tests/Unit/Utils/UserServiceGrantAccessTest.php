<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Event\UserStatusChangedEvent;
use App\Repository\UserRepository;
use App\Security\Permission\Legacy\LegacyPermissionBridge;
use App\Services\CurrentContextResolver;
use App\Services\LegacyEnvironment;
use App\Utils\RoomService;
use App\Utils\UserService;
use cs_environment;
use cs_list;
use cs_user_item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Switching a room's access check to "never" grants every pending application at once.
 * That has to be announced like any other approval, because the applicant's user room
 * carries their status separately — silently raising it only in the project room would
 * leave them locked out of their own user room (RT #1526633).
 */
class UserServiceGrantAccessTest extends TestCase
{
    private MockObject $eventDispatcher;

    public function testEveryGrantedApplicationIsAnnounced(): void
    {
        $applicants = [$this->applicant(11), $this->applicant(22)];

        $announced = [];
        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(function (object $event) use (&$announced) {
                self::assertInstanceOf(UserStatusChangedEvent::class, $event);
                $announced[] = $event->getUser()->getItemID();

                return $event;
            });

        $this->service($applicants)->grantAccessToAllPendingApplications();

        self::assertSame([11, 22], $announced);
    }

    public function testNothingIsAnnouncedWithoutPendingApplications(): void
    {
        $this->eventDispatcher->expects(self::never())->method('dispatch');

        $this->service([])->grantAccessToAllPendingApplications();
    }

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @param cs_user_item[] $applicants
     */
    private function service(array $applicants): UserService
    {
        $userList = $this->createMock(cs_list::class);
        $userList->method('getFirst')->willReturn($applicants[0] ?? false);
        $userList->method('getNext')->willReturnOnConsecutiveCalls(
            ...[...array_slice($applicants, 1), false]
        );

        $userManager = $this->createMock(\cs_user_manager::class);
        $userManager->method('get')->willReturn($userList);

        $taskList = $this->createMock(cs_list::class);
        $taskList->method('getFirst')->willReturn(false);
        $taskManager = $this->createMock(\cs_tasks_manager::class);
        $taskManager->method('getTaskListForItem')->willReturn($taskList);

        $environment = $this->createMock(cs_environment::class);
        $environment->method('getUserManager')->willReturn($userManager);
        $environment->method('getRoomManager')->willReturn($this->createMock(\cs_room_manager::class));
        $environment->method('getTaskManager')->willReturn($taskManager);

        $legacyEnvironment = $this->createMock(LegacyEnvironment::class);
        $legacyEnvironment->method('getEnvironment')->willReturn($environment);

        $contextResolver = $this->createMock(CurrentContextResolver::class);
        $contextResolver->method('getContextId')->willReturn(105);

        return new UserService(
            $legacyEnvironment,
            $this->createMock(RoomService::class),
            $this->createMock(UserRepository::class),
            $this->createMock(Security::class),
            $this->createMock(LegacyPermissionBridge::class),
            $contextResolver,
            $this->eventDispatcher
        );
    }

    private function applicant(int $itemId): cs_user_item
    {
        $user = $this->createMock(cs_user_item::class);
        $user->method('getItemID')->willReturn($itemId);

        return $user;
    }
}
