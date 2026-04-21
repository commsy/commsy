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

use App\Entity\Dates;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Rubric\Dates\DatesDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\DatesFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see DatesDeleter}.
 *
 * Same assertion shape as {@see AnnouncementDeleterTest} — soft-delete of the
 * rubric row plus items twin, cleanup of link_items, links (all types),
 * annotations; event dispatch; no collateral damage on bystanders — and adds
 * Dates-specific scenarios:
 *  - Whole-series deletion via {@see DatesDeleter::deleteSeries()}
 *  - Single-occurrence exclusion via
 *    {@see DatesDeleter::excludeOccurrenceFromSeries()} (siblings get a
 *    `recurringExclude` entry in their RRULE, but are not themselves deleted)
 */
final class DatesDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private DatesDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    /**
     * Core contract: after deletion the `dates` row and its matching `items`
     * twin are soft-deleted (deletion_date set, deleter_id pointing at caller).
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesDatesAndItemsRows(): void
    {
        $date = $this->createDate();

        $this->deleter->softDeleteItem($date->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('dates', $date->getItemId());
        $this->assertSoftDeleted('items', $date->getItemId());
    }

    /**
     * `link_items` must be soft-deleted in both directions (first_item_id /
     * second_item_id). Guards against dangling UI cross-links into the
     * deleted date.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $source = $this->createDate();
        $target = $this->createDate();

        $linkAsFirst = $this->createLinkItem($source->getItemId(), $target->getItemId());
        $linkAsSecond = $this->createLinkItem($target->getItemId(), $source->getItemId());

        $this->deleter->softDeleteItem($source->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkAsFirst);
        $this->assertLinkItemSoftDeleted($linkAsSecond);
    }

    /**
     * Rows in the `links` table (buzzword_for, in_time, label_for) must be
     * soft-deleted in both directions when a date is removed. Dates already
     * did this in the legacy cascade — pinned down here so the refactored
     * deleter keeps that contract while also unifying behaviour with the
     * other rubrics (see AnnouncementDeleterTest for the counterpart).
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $source = $this->createDate();
        $target = $this->createDate();

        $this->createLink($source->getItemId(), $target->getItemId(), 'buzzword_for');
        $this->createLink($source->getItemId(), $target->getItemId(), 'in_time');
        $this->createLink($target->getItemId(), $source->getItemId(), 'label_for');

        $this->deleter->softDeleteItem($source->getItemId(), $this->deleterId);

        $aliveCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NULL
                  AND deleter_id IS NULL',
            ['id' => $source->getItemId()]
        );
        self::assertSame(0, $aliveCount, 'every link referencing the deleted date must be soft-deleted');

        $softDeletedCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NOT NULL
                  AND deleter_id = :deleterId',
            ['id' => $source->getItemId(), 'deleterId' => $this->deleterId]
        );
        self::assertSame(3, $softDeletedCount, 'all three link types must carry the soft-delete marker');
    }

    /**
     * Annotations attached to the date must be soft-deleted in both the
     * `annotations` rubric table and in `items`. Otherwise comments would
     * stay alive as zombie entries in search/feed output.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAnnotations(): void
    {
        $date = $this->createDate();
        $annotationId = $this->createAnnotation($date->getItemId());

        $this->deleter->softDeleteItem($date->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('annotations', $annotationId);
        $this->assertSoftDeleted('items', $annotationId);
    }

    /**
     * The deleter must dispatch an {@see ItemDeletedEvent} so side-effectful
     * subscribers (Elasticsearch cleanup, moderator mails, etherpad removal)
     * can hook in — none of which are observable in the database.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $date = $this->createDate();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteItem($date->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'DatesDeleter must dispatch ItemDeletedEvent so ES cleanup, mail notifications etc. can hook in.'
        );
    }

    /**
     * Regression safety net: deleting a single date must not touch unrelated
     * dates in the same room. Guards against missing WHERE clauses or
     * context-wide UPDATEs.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherDates(): void
    {
        $target = $this->createDate();
        $bystander = $this->createDate();

        $this->deleter->softDeleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('dates', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    /**
     * `deleteSeries()` must soft-delete every occurrence sharing the given
     * recurrence_id — and only those. Dates in other series (or none) must
     * stay alive.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSeriesRemovesAllMembers(): void
    {
        $recurrenceId = 424242;
        $series = [
            $this->createDate(['recurrenceId' => $recurrenceId]),
            $this->createDate(['recurrenceId' => $recurrenceId]),
            $this->createDate(['recurrenceId' => $recurrenceId]),
        ];
        $bystander = $this->createDate();

        $this->deleter->deleteSeries($recurrenceId, $this->deleterId);

        foreach ($series as $member) {
            $this->assertSoftDeleted('dates', $member->getItemId());
            $this->assertSoftDeleted('items', $member->getItemId());
        }
        $this->assertNotSoftDeleted('dates', $bystander->getItemId());
    }

    /**
     * `excludeOccurrenceFromSeries()` soft-deletes the given occurrence and
     * patches every sibling's recurrence_pattern with a `recurringExclude`
     * token — so CalDAV/RRULE consumers skip the removed slot while the rest
     * of the series stays intact.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testExcludeOccurrenceFromSeriesPatchesSiblings(): void
    {
        $recurrenceId = 535353;
        $pattern = ['freq' => 'DAILY', 'interval' => 1];

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $excluded = $this->createDate([
            'recurrenceId' => $recurrenceId,
            'recurrencePattern' => $pattern,
            'startDay' => $today,
            'datetimeStart' => $today . ' 10:00:00',
            'datetimeEnd' => $today . ' 11:00:00',
        ]);
        $sibling = $this->createDate([
            'recurrenceId' => $recurrenceId,
            'recurrencePattern' => $pattern,
            'startDay' => $tomorrow,
            'datetimeStart' => $tomorrow . ' 10:00:00',
            'datetimeEnd' => $tomorrow . ' 11:00:00',
        ]);

        $this->deleter->excludeOccurrenceFromSeries($excluded->getItemId(), $this->deleterId);

        // The occurrence itself is gone …
        $this->assertSoftDeleted('dates', $excluded->getItemId());
        // … the sibling stays alive …
        $this->assertNotSoftDeleted('dates', $sibling->getItemId());
        // … and the sibling's RRULE carries the exclusion token.
        $raw = $this->connection->fetchOne(
            'SELECT recurrence_pattern FROM dates WHERE item_id = :id',
            ['id' => $sibling->getItemId()]
        );
        self::assertIsString($raw);
        $stored = unserialize($raw);
        self::assertIsArray($stored);
        self::assertArrayHasKey('recurringExclude', $stored);
        $token = (new \DateTime($today . ' 10:00:00'))->format('Ymd\THis');
        self::assertContains($token, $stored['recurringExclude']);
    }

    /**
     * Hard-delete sweep: soft-deleted dates whose `deletion_date` is older
     * than the grace window must be physically removed from the `dates`
     * table. Recent soft-deletes remain, alive rows stay untouched. The
     * `items` twin stays on the legacy CS_ITEM_TYPE sweep path.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRows(): void
    {
        $expired = $this->createDate();
        $recent = $this->createDate();
        $alive = $this->createDate();

        $this->deleter->softDeleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->softDeleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE dates SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(1, $affected);
        $this->assertPhysicallyDeleted('dates', $expired->getItemId());
        $this->assertSoftDeleted('dates', $recent->getItemId());
        $this->assertNotSoftDeleted('dates', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(DatesDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createDate(array $overrides = []): Dates
    {
        return DatesFactory::createOne(array_merge([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ], $overrides));
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
