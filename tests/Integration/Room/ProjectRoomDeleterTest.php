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
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Event\Workspace\WorkspaceDeletedEvent;
use App\Room\ProjectRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see ProjectRoomDeleter}.
 *
 * On top of the shared RoomDeleter contract the project-room tests cover
 * the cascade into sub-rooms (grouprooms + userrooms), which is unique
 * to project rooms and the last-remaining piece of the
 * `cs_project_item::delete()` legacy cascade.
 */
final class ProjectRoomDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private ProjectRoomDeleter $deleter;
    private Account $account;
    private int $deleterId;

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomAndItemsRows(): void
    {
        $room = $this->createProjectRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $room->getItemId());
        $this->assertSoftDeleted('items', $room->getItemId());
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoRubricContent(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMembership($room);

        $announcement = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $member,
        ]);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('announcement', $announcement->getItemId());
        $this->assertSoftDeleted('items', $announcement->getItemId());
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomMemberships(): void
    {
        $room = $this->createProjectRoom();
        $member = $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('user', $member->getItemId());
        $this->assertSoftDeleted('items', $member->getItemId());
    }

    /**
     * Grouprooms whose extras point back at the project must be dragged
     * down with the project — they would otherwise linger as orphans
     * with no reachable parent.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoGroupRooms(): void
    {
        $project = $this->createProjectRoom();
        $this->createMembership($project);

        $groupRoom = $this->createGroupRoom($project);
        $groupMember = $this->createMembership($groupRoom);

        $this->deleter->softDeleteRoom($project->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $groupRoom->getItemId());
        $this->assertSoftDeleted('items', $groupRoom->getItemId());
        $this->assertSoftDeleted('user', $groupMember->getItemId());
    }

    /**
     * Userrooms hang off the project (not off grouprooms) — verify the
     * cascade reaches them even without an intermediate group room.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoUserRooms(): void
    {
        $project = $this->createProjectRoom();
        $this->createMembership($project);

        $userRoom = $this->createUserRoom($project);
        $userRoomMember = $this->createMembership($userRoom);

        $this->deleter->softDeleteRoom($project->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $userRoom->getItemId());
        $this->assertSoftDeleted('items', $userRoom->getItemId());
        $this->assertSoftDeleted('user', $userRoomMember->getItemId());
    }

    /**
     * The sub-room cascade must run with `silent = true` so we end up
     * with exactly one WorkspaceDeletedEvent for the project itself —
     * not one per cascaded grouproom/userroom.
     */
    #[WithStory(AccountStory::class)]
    public function testSubRoomCascadeSuppressesPerSubRoomWorkspaceEvents(): void
    {
        $project = $this->createProjectRoom();
        $this->createMembership($project);
        $this->createGroupRoom($project);
        $this->createGroupRoom($project);
        $this->createUserRoom($project);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteRoom($project->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        // With three sub-rooms involved we would see up to four events
        // without the silent flag. We expect exactly the single
        // project-level event.
        $events = $this->dispatchedEvents($dispatcher, WorkspaceDeletedEvent::class);
        self::assertCount(
            1,
            $events,
            'ProjectRoomDeleter must emit exactly one WorkspaceDeletedEvent — sub-room cascade is silent.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDispatchesWorkspaceDeletedEvent(): void
    {
        $room = $this->createProjectRoom();
        $this->createMembership($room);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        self::assertNotEmpty(
            $this->dispatchedEvents($dispatcher, WorkspaceDeletedEvent::class),
            'ProjectRoomDeleter must dispatch WorkspaceDeletedEvent so moderation mails fire.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSilentOptionSuppressesWorkspaceDeletedEvent(): void
    {
        $room = $this->createProjectRoom();
        $this->createMembership($room);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteRoom(
            $room->getItemId(),
            $this->deleterId,
            RoomDeletionOptions::forUserAction()->asSilent(),
        );

        self::assertEmpty(
            $this->dispatchedEvents($dispatcher, WorkspaceDeletedEvent::class),
            'silent=true must suppress WorkspaceDeletedEvent dispatch.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDispatchesItemDeletedEvent(): void
    {
        $room = $this->createProjectRoom();
        $this->createMembership($room);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'ProjectRoomDeleter must dispatch ItemDeletedEvent so ES cleanup runs.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteIsIdempotent(): void
    {
        $room = $this->createProjectRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());
        $firstDate = $this->connection->fetchOne(
            'SELECT deletion_date FROM room WHERE item_id = :id',
            ['id' => $room->getItemId()]
        );

        $this->deleter->softDeleteRoom($room->getItemId(), 999999, RoomDeletionOptions::forUserAction());

        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM room WHERE item_id = :id',
            ['id' => $room->getItemId()]
        );
        self::assertSame($firstDate, $row['deletion_date']);
        self::assertSame($this->deleterId, (int) $row['deleter_id']);
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDoesNotAffectOtherRooms(): void
    {
        $target = $this->createProjectRoom();
        $bystander = $this->createProjectRoom();
        $bystanderGroupRoom = $this->createGroupRoom($bystander);
        $this->createMembership($target);
        $bystanderMember = $this->createMembership($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertNotSoftDeleted('room', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
        $this->assertNotSoftDeleted('user', $bystanderMember->getItemId());
        $this->assertNotSoftDeleted('room', $bystanderGroupRoom->getItemId());
        $this->assertNotSoftDeleted('items', $bystanderGroupRoom->getItemId());
    }

    public function testRoomTypeIsProject(): void
    {
        self::assertSame(RoomType::Project, $this->deleter->roomType());
    }

    /**
     * Happy-path hard-delete of a project room: the project's own `room`
     * row + rubric content + membership row are physically removed. Sub-
     * rooms (grouproom / userroom) are **not** cascaded here — they carry
     * their own `deletion_date` from the soft-delete phase and will be
     * picked up by {@see \App\Room\RoomHardDeleter::hardDeleteRoomsOlderThan}
     * on their own. The test leaves the sub-rooms soft-deleted and
     * asserts they stay that way (no premature physical removal, no
     * resurrection).
     */
    #[WithStory(AccountStory::class)]
    public function testHardDeleteRemovesProjectContentAndLeavesSubRoomsSoftDeleted(): void
    {
        $project = $this->createProjectRoom();
        $member = $this->createMembership($project);
        $announcement = AnnouncementFactory::createOne([
            'room' => $project,
            'creator' => $member,
        ]);
        $groupRoom = $this->createGroupRoom($project);
        $userRoom = $this->createUserRoom($project);

        // Soft-delete cascades into sub-rooms (silent).
        $this->deleter->softDeleteRoom($project->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        // Hard-delete only the project itself.
        $this->deleter->hardDeleteRoom($project->getItemId());

        // Project physically gone.
        $this->assertPhysicallyDeleted('room', $project->getItemId());
        $this->assertPhysicallyDeleted('announcement', $announcement->getItemId());
        $this->assertPhysicallyDeleted('items', $announcement->getItemId());
        $this->assertPhysicallyDeleted('user', $member->getItemId());

        // Sub-rooms stay soft-deleted (their own hard-delete happens on
        // the next RoomHardDeleter pass).
        $this->assertSoftDeleted('room', $groupRoom->getItemId());
        $this->assertSoftDeleted('room', $userRoom->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(ProjectRoomDeleter::class);

        $this->account = AccountStory::get('account');
        $this->deleterId = $this->account->getId();
    }

    private function createProjectRoom(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::Project->value,
        ]);
    }

    private function createGroupRoom(Room $project): Room
    {
        return RoomFactory::createOne([
            'contextId' => $project->getItemId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::GroupRoom->value,
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

    private function createMembership(Room $room): User
    {
        return RoomUserFactory::createOne([
            'account' => $this->account,
            'room' => $room,
        ]);
    }

    private function dispatchedEvents(TraceableEventDispatcher $dispatcher, string $eventClass): array
    {
        return array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === $eventClass
        );
    }

    private function assertSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNotNull($row['deletion_date'], sprintf('%s row %d must have deletion_date set', $table, $itemId));
        self::assertSame(
            $this->deleterId,
            (int) $row['deleter_id'],
            sprintf('%s row %d must record the correct deleter_id', $table, $itemId)
        );
    }

    private function assertPhysicallyDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT item_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertFalse($row, sprintf('%s row %d must be physically removed', $table, $itemId));
    }

    private function assertNotSoftDeleted(string $table, int $itemId): void
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNull($row['deletion_date'], sprintf('%s row %d must still be alive', $table, $itemId));
        self::assertNull($row['deleter_id'], sprintf('%s row %d must have no deleter_id', $table, $itemId));
    }
}
