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

use App\Entity\Account;
use App\Entity\Room;
use App\Room\RoomStatus;
use App\Room\RoomType;
use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\RoomFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Locking a project room locks its group and user rooms with it; unlocking it
 * lifts only those locks, never one a sub room had on its own.
 */
#[WithStory(AccountStory::class)]
final class ProjectRoomLockTest extends KernelTestCase
{
    private Account $account;
    private cs_environment $legacyEnvironment;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->account = AccountStory::get('account');
        $this->legacyEnvironment = self::getContainer()->get(LegacyEnvironment::class)->getEnvironment();
    }

    public function testLockingTheProjectRoomLocksItsGroupAndUserRoomsUntilItIsUnlocked(): void
    {
        $project = $this->createProjectRoom();
        $groupRoom = $this->createGroupRoom($project);
        $userRoom = $this->createUserRoom($project);

        $this->lockRoom($project);

        self::assertSame(RoomStatus::LOCKED->value, $this->roomStatus($groupRoom));
        self::assertSame(RoomStatus::LOCKED->value, $this->roomStatus($userRoom));

        $this->unlockRoom($project);

        self::assertSame(RoomStatus::OPEN->value, $this->roomStatus($groupRoom));
        self::assertSame(RoomStatus::OPEN->value, $this->roomStatus($userRoom));
    }

    public function testAPortalLockOfTheProjectRoomReachesItsSubRooms(): void
    {
        $project = $this->createProjectRoom();
        $groupRoom = $this->createGroupRoom($project);
        $userRoom = $this->createUserRoom($project);

        $this->lockRoom($project, RoomStatus::LOCKED_PORTAL_MOD);

        self::assertSame(RoomStatus::LOCKED_PORTAL_MOD->value, $this->roomStatus($groupRoom));
        self::assertSame(RoomStatus::LOCKED_PORTAL_MOD->value, $this->roomStatus($userRoom));
    }

    public function testASubRoomLockedBeforeKeepsItsLock(): void
    {
        $project = $this->createProjectRoom();
        $groupRoom = $this->createGroupRoom($project);
        $userRoom = $this->createUserRoom($project);
        $this->lockRoom($groupRoom, RoomStatus::LOCKED_PORTAL_MOD);
        $this->lockRoom($userRoom);

        $this->lockRoom($project);
        $this->unlockRoom($project);

        self::assertSame(RoomStatus::LOCKED_PORTAL_MOD->value, $this->roomStatus($groupRoom));
        self::assertSame(RoomStatus::LOCKED->value, $this->roomStatus($userRoom));
    }

    public function testASubRoomLockedAgainOnItsOwnKeepsThatLock(): void
    {
        $project = $this->createProjectRoom();
        $groupRoom = $this->createGroupRoom($project);

        $this->lockRoom($project);
        $this->unlockRoom($groupRoom);
        $this->lockRoom($groupRoom);
        $this->unlockRoom($project);

        self::assertSame(RoomStatus::LOCKED->value, $this->roomStatus($groupRoom));
    }

    public function testAClosedSubRoomComesBackClosed(): void
    {
        $project = $this->createProjectRoom();
        $groupRoom = $this->createGroupRoom($project, RoomStatus::CLOSED);

        $this->lockRoom($project);
        $this->unlockRoom($project);

        self::assertSame(RoomStatus::CLOSED->value, $this->roomStatus($groupRoom));
    }

    private function lockRoom(Room $room, RoomStatus $status = RoomStatus::LOCKED): void
    {
        $roomItem = $this->legacyEnvironment->getRoomManager()->getItem($room->getItemId());
        $roomItem->lock($status);
        $roomItem->save();
    }

    private function unlockRoom(Room $room): void
    {
        $roomItem = $this->legacyEnvironment->getRoomManager()->getItem($room->getItemId());
        $roomItem->unlock();
        $roomItem->save();
    }

    private function roomStatus(Room $room): int
    {
        return (int) self::getContainer()->get(Connection::class)
            ->fetchOne('SELECT status FROM room WHERE item_id = ?', [$room->getItemId()]);
    }

    private function createProjectRoom(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::Project->value,
        ]);
    }

    private function createGroupRoom(Room $project, RoomStatus $status = RoomStatus::OPEN): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::GroupRoom->value,
            'status' => (string) $status->value,
            'extras' => ['PROJECT_ROOM_ITEM_ID' => $project->getItemId()],
        ]);
    }

    private function createUserRoom(Room $project): Room
    {
        return RoomFactory::createOne([
            'contextId' => $project->getItemId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::UserRoom->value,
            'extras' => ['PROJECT_ROOM_ITEM_ID' => $project->getItemId()],
        ]);
    }
}
