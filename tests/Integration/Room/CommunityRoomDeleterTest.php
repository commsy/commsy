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
use App\Room\CommunityRoomDeleter;
use App\Room\RoomDeletionOptions;
use App\Room\RoomType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\RoomFactory;
use Tests\Factory\RoomUserFactory;
use Tests\Story\AccountStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see CommunityRoomDeleter}.
 *
 * Beyond the shared RoomDeleter contract the community-specific assertions
 * cover the project-room linkage: deleting a community must soft-delete
 * the bidirectional `link_items` rows but leave the linked project rooms
 * themselves alive — matching legacy semantics where a community could
 * be deleted regardless of how many projects still referenced it.
 */
final class CommunityRoomDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private CommunityRoomDeleter $deleter;
    private Account $account;
    private int $deleterId;

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteSoftDeletesRoomAndItemsRows(): void
    {
        $room = $this->createCommunityRoom();
        $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('room', $room->getItemId());
        $this->assertSoftDeleted('items', $room->getItemId());
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteCascadesIntoRubricContent(): void
    {
        $room = $this->createCommunityRoom();
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
        $room = $this->createCommunityRoom();
        $member = $this->createMembership($room);

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertSoftDeleted('user', $member->getItemId());
        $this->assertSoftDeleted('items', $member->getItemId());
    }

    /**
     * Legacy semantics: a community can be deleted while it still has
     * linked project rooms. Only the bidirectional `link_items` row dies;
     * the project room keeps living, just without the community on its
     * `getCommunityList()`.
     */
    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDropsCommunityProjectLinkButKeepsProjectAlive(): void
    {
        $community = $this->createCommunityRoom();
        $member = $this->createMembership($community);

        $project = RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => 'project',
        ]);
        $projectMember = $this->createMembership($project);

        $linkItem = LinkItemFactory::createOne([
            'room' => $community,
            'creator' => $member,
            'firstItemId' => $community->getItemId(),
            'secondItemId' => $project->getItemId(),
        ]);

        $this->deleter->softDeleteRoom($community->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        // community itself is gone …
        $this->assertSoftDeleted('room', $community->getItemId());

        // … the link_item row between community and project is gone …
        $this->assertSoftDeleted('link_items', $linkItem->getItemId());

        // … but the project room and its membership survive untouched.
        $this->assertNotSoftDeleted('room', $project->getItemId());
        $this->assertNotSoftDeleted('items', $project->getItemId());
        $this->assertNotSoftDeleted('user', $projectMember->getItemId());
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteDispatchesWorkspaceDeletedEvent(): void
    {
        $room = $this->createCommunityRoom();
        $this->createMembership($room);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteRoom($room->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        self::assertNotEmpty(
            $this->dispatchedEvents($dispatcher, WorkspaceDeletedEvent::class),
            'CommunityRoomDeleter must dispatch WorkspaceDeletedEvent so moderation mails fire.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSilentOptionSuppressesWorkspaceDeletedEvent(): void
    {
        $room = $this->createCommunityRoom();
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
        $room = $this->createCommunityRoom();
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
            'CommunityRoomDeleter must dispatch ItemDeletedEvent so ES cleanup runs.'
        );
    }

    #[WithStory(AccountStory::class)]
    public function testSoftDeleteIsIdempotent(): void
    {
        $room = $this->createCommunityRoom();
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
        $target = $this->createCommunityRoom();
        $bystander = $this->createCommunityRoom();
        $this->createMembership($target);
        $bystanderMember = $this->createMembership($bystander);

        $this->deleter->softDeleteRoom($target->getItemId(), $this->deleterId, RoomDeletionOptions::forUserAction());

        $this->assertNotSoftDeleted('room', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
        $this->assertNotSoftDeleted('user', $bystanderMember->getItemId());
    }

    public function testHardDeleteIsNotImplementedYet(): void
    {
        $this->expectException(LogicException::class);
        $this->deleter->hardDeleteRoom(1);
    }

    public function testRoomTypeIsCommunity(): void
    {
        self::assertSame(RoomType::Community, $this->deleter->roomType());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(CommunityRoomDeleter::class);

        $this->account = AccountStory::get('account');
        $this->deleterId = $this->account->getId();
    }

    private function createCommunityRoom(): Room
    {
        return RoomFactory::createOne([
            'contextId' => $this->account->getPortal()?->getId(),
            'portal' => $this->account->getPortal(),
            'type' => RoomType::Community->value,
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
