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

namespace Tests\Integration\Rubric;

use App\Entity\Announcement;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Rubric\Announcement\AnnouncementDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see AnnouncementDeleter}.
 *
 * Pins down the effective deletion contract for announcements so that the
 * same assertion suite can be reused as each further rubric (Dates,
 * Discussion, Todo, Task, Material) is migrated off the legacy
 * `cs_item::delete()` cascade.
 *
 * Main items are created through {@see AnnouncementFactory}, which wraps the
 * same legacy manager chain controllers use — so the fixture mirrors real
 * runtime state. Auxiliary rows (link_items, annotations, links) are still
 * written via DBAL for now; factories for those will follow once this test
 * proves the approach.
 */
final class AnnouncementDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private AnnouncementDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    /**
     * Core contract of the deleter: after the call, the announcement and its
     * matching `items` row must be marked as soft-deleted (`deletion_date` set,
     * `deleter_id` pointing at the caller). Pins down that we never hard-delete —
     * rows must stay intact for restore / audit use cases.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAnnouncementAndItemsRows(): void
    {
        $announcement = $this->createAnnouncement();

        $this->deleter->deleteItem($announcement->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('announcement', $announcement->getItemId());
        $this->assertSoftDeleted('items', $announcement->getItemId());
    }

    /**
     * Links between items (`link_items`) must be cleaned up from both sides —
     * whether the deleted announcement sits in `first_item_id` or `second_item_id`.
     * Otherwise the UI would keep showing dangling links pointing at an item
     * that is already gone.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $source = $this->createAnnouncement();
        $target = $this->createAnnouncement();

        $linkAsFirst = $this->createLinkItem($source->getItemId(), $target->getItemId());
        $linkAsSecond = $this->createLinkItem($target->getItemId(), $source->getItemId());

        $this->deleter->deleteItem($source->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkAsFirst);
        $this->assertLinkItemSoftDeleted($linkAsSecond);
    }

    /**
     * Rows in the `links` table (buzzword_for, in_time, label_for, …) must be
     * soft-deleted in both directions (from/to) when an announcement is
     * removed. Unifies legacy behaviour: cs_announcement_manager used to
     * hard-delete only `relevant_for`, other rubrics didn't clean up `links`
     * at all. The new deleter soft-deletes every link that references the
     * item regardless of type, so restore / audit keeps working.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $source = $this->createAnnouncement();
        $target = $this->createAnnouncement();

        $this->createLink($source->getItemId(), $target->getItemId(), 'relevant_for');
        $this->createLink($source->getItemId(), $target->getItemId(), 'buzzword_for');
        $this->createLink($target->getItemId(), $source->getItemId(), 'label_for');

        $this->deleter->deleteItem($source->getItemId(), $this->deleterId);

        $aliveCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NULL
                  AND deleter_id IS NULL',
            ['id' => $source->getItemId()]
        );
        self::assertSame(0, $aliveCount, 'every link referencing the deleted announcement must be soft-deleted');

        $softDeletedCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NOT NULL
                  AND deleter_id = :deleterId',
            ['id' => $source->getItemId(), 'deleterId' => $this->deleterId]
        );
        self::assertSame(3, $softDeletedCount, 'all three link types must carry the soft-delete marker with the correct deleter_id');
    }

    /**
     * Annotations (comments) hang off their announcement via `linked_item_id`.
     * When the announcement is deleted, every attached annotation must be
     * soft-deleted too — in the `annotations` rubric table **and** in the
     * central `items` table. Forgetting the `items` row would leave zombie
     * entries behind in search / feed.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAnnotations(): void
    {
        $announcement = $this->createAnnouncement();
        $annotationId = $this->createAnnotation($announcement->getItemId());

        $this->deleter->deleteItem($announcement->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('annotations', $annotationId);
        $this->assertSoftDeleted('items', $annotationId);
    }

    /**
     * The deleter must dispatch an {@see ItemDeletedEvent}. Side-effects that
     * are not visible in the DB hook into it: Elasticsearch cleanup (via
     * ElasticaSubscriber), moderator notification mails (ItemSubscriber), etc.
     * Verified through the TraceableEventDispatcher — without this guarantee,
     * items could disappear from the DB but stay in the search index.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $announcement = $this->createAnnouncement();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteItem($announcement->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'AnnouncementDeleter must dispatch ItemDeletedEvent so ES cleanup, mail notifications etc. can hook in.'
        );
    }

    /**
     * Regression safety net: deleting a single announcement must not affect
     * other announcements in the same room. Guards against SQL mistakes like
     * a missing WHERE clause or context-wide UPDATEs that would otherwise
     * only surface in production.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherAnnouncements(): void
    {
        $target = $this->createAnnouncement();
        $bystander = $this->createAnnouncement();

        $this->deleter->deleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('announcement', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    /**
     * Hard-delete sweep: soft-deleted announcements whose `deletion_date`
     * is older than the configured grace window must be physically removed
     * from the `announcement` table. Rows inside the grace window stay
     * soft-deleted, alive rows remain fully intact.
     *
     * The `items` twin row is intentionally NOT touched by this sweep —
     * the central `items` cleanup stays on the legacy
     * `cs_manager::deleteReallyOlderThan()` path in CronHardDelete.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRows(): void
    {
        $expired = $this->createAnnouncement();
        $recent = $this->createAnnouncement();
        $alive = $this->createAnnouncement();

        $this->deleter->deleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->deleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE announcement SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(1, $affected);
        $this->assertPhysicallyDeleted('announcement', $expired->getItemId());
        $this->assertSoftDeleted('announcement', $recent->getItemId());
        $this->assertNotSoftDeleted('announcement', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(AnnouncementDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createAnnouncement(): Announcement
    {
        return AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    private function createLinkItem(int $firstItemId, int $secondItemId): int
    {
        $linkItem = LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $firstItemId,
            'secondItemId' => $secondItemId,
        ]);

        return $linkItem->getItemId();
    }

    private function createLink(int $fromItemId, int $toItemId, string $linkType): void
    {
        LinkFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'fromItemId' => $fromItemId,
            'toItemId' => $toItemId,
            'linkType' => $linkType,
        ]);
    }

    private function createAnnotation(int $parentItemId): int
    {
        $annotation = AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $parentItemId,
        ]);

        return $annotation->getItemId();
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
        self::assertNull($row['deleter_id'], sprintf('%s row %d must not have deleter_id set', $table, $itemId));
    }

    private function assertLinkItemSoftDeleted(int $linkId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM link_items WHERE item_id = :id',
            ['id' => $linkId]
        );
        self::assertIsArray($row, sprintf('Expected link_items row for id %d', $linkId));
        self::assertNotNull($row['deletion_date'], sprintf('link_items row %d must be soft-deleted', $linkId));
        self::assertSame(
            $this->deleterId,
            (int) $row['deleter_id'],
            sprintf('link_items row %d must record the correct deleter_id', $linkId)
        );

        // cs_link_manager::_create() writes a twin row into `items`
        // (type = 'link_item') to allocate the AUTO_INCREMENT id before
        // inserting into `link_items`. RubricDeletionHelper must soft-delete
        // both sides so the two tables stay in sync.
        $twin = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM items WHERE item_id = :id',
            ['id' => $linkId]
        );
        self::assertIsArray($twin, sprintf('Expected items twin row for link_items id %d', $linkId));
        self::assertNotNull($twin['deletion_date'], sprintf('items twin of link_items %d must be soft-deleted', $linkId));
        self::assertSame(
            $this->deleterId,
            (int) $twin['deleter_id'],
            sprintf('items twin of link_items %d must record the correct deleter_id', $linkId)
        );
    }
}
