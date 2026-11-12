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

namespace Tests\Integration\Room;

use App\Entity\Room;
use App\Message\WorkspaceActivityStateTransitions;
use App\MessageHandler\WorkspaceActivityStateTransitionsHandler;
use App\Room\RoomStatus;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;

/**
 * The deprovisioning takes over a lock that is already there: a locked room
 * gets no notice about an upcoming lock and goes straight to the locked stage,
 * from which the deletion is announced. The lock itself is left as it is.
 */
final class RoomActivityLockedRoomTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;

    public function testAnOpenRoomIsWarnedAboutTheLock(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::OPEN, Room::ACTIVITY_ACTIVE);

        $this->handle($room);

        self::assertSame(Room::ACTIVITY_ACTIVE_NOTIFIED, $this->reload($room)->getActivityState());
    }

    public function testALockedRoomSkipsTheLockNotice(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::LOCKED, Room::ACTIVITY_ACTIVE);

        $this->handle($room);

        $reloaded = $this->reload($room);
        self::assertSame(Room::ACTIVITY_IDLE, $reloaded->getActivityState());
        self::assertGreaterThan(
            new DateTime('-1 hour'),
            $reloaded->getActivityStateUpdated(),
            'the deletion notice is counted from now',
        );
        self::assertSame((string) RoomStatus::LOCKED->value, $reloaded->getStatus());
    }

    public function testARoomLockedDuringTheLockNoticeIsTakenOverAtOnce(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::LOCKED, Room::ACTIVITY_ACTIVE_NOTIFIED, new DateTime('-2 days'));

        $this->handle($room);

        self::assertSame(Room::ACTIVITY_IDLE, $this->reload($room)->getActivityState());
    }

    public function testALockByThePortalModerationIsKept(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::LOCKED_PORTAL_MOD, Room::ACTIVITY_ACTIVE);

        $this->handle($room);

        $reloaded = $this->reload($room);
        self::assertSame(Room::ACTIVITY_IDLE, $reloaded->getActivityState());
        self::assertSame((string) RoomStatus::LOCKED_PORTAL_MOD->value, $reloaded->getStatus());
    }

    public function testALockedRoomInUseIsLeftAlone(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::LOCKED, Room::ACTIVITY_ACTIVE, null, new DateTime('-10 days'));

        $this->handle($room);

        self::assertSame(Room::ACTIVITY_ACTIVE, $this->reload($room)->getActivityState());
    }

    public function testAnUnlockedRoomLeavesTheWayToDeletion(): void
    {
        $room = $this->createInactiveRoom(RoomStatus::OPEN, Room::ACTIVITY_IDLE_NOTIFIED, new DateTime('-400 days'));

        $this->handle($room);

        $reloaded = $this->reload($room);
        self::assertSame(Room::ACTIVITY_ACTIVE, $reloaded->getActivityState());
        self::assertNull($reloaded->getActivityStateUpdated());
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    private function createInactiveRoom(
        RoomStatus $status,
        string $state,
        ?DateTime $stateUpdated = null,
        ?DateTime $lastLogin = null,
    ): Room {
        $portal = PortalFactory::createOne(['clearInactiveRoomsFeatureEnabled' => true]);

        return RoomFactory::new()->project()->create([
            'portal' => $portal,
            'contextId' => $portal->getId(),
            'status' => $status->value,
            'lastlogin' => $lastLogin ?? new DateTime('-200 days'),
            'activityState' => $state,
            'activityStateUpdated' => $stateUpdated,
        ]);
    }

    private function handle(Room $room): void
    {
        $handler = self::getContainer()->get(WorkspaceActivityStateTransitionsHandler::class);
        $handler(new WorkspaceActivityStateTransitions([$room->getItemId()]));

        $this->entityManager->clear();
    }

    private function reload(Room $room): Room
    {
        return $this->entityManager->getRepository(Room::class)->find($room->getItemId());
    }
}
