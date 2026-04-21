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

use App\Entity\Labels;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Rubric\Label\LabelDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see LabelDeleter}.
 *
 * One deleter covers the whole cs_label_item hierarchy (topic / hashtag /
 * buzzword / timepulse / institution / group) because every subtype lives
 * in the shared `labels` table distinguished only by `labels.type`. These
 * tests use the `buzzword` subtype — the simplest one (no grouproom
 * mirror, not indexed by `cs_label_item::save()`) — to exercise the
 * deleter's generic path. Subtype-specific concerns (grouproom cascade
 * for `group`) are intentionally out of scope: they sit in the room-deleter
 * layer.
 */
final class LabelDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private LabelDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    /**
     * Core contract: after the call, the `labels` row and its `items`
     * twin are soft-deleted. No hard-delete — rows survive for restore /
     * audit use cases.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLabelAndItemsRows(): void
    {
        $label = $this->createLabel();

        $this->deleter->softDeleteItem($label->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('labels', $label->getItemId());
        $this->assertSoftDeleted('items', $label->getItemId());
    }

    /**
     * `links` rows referencing the label (buzzword_for, label_for, …) must
     * be soft-deleted in both directions. Legacy parity with
     * cs_link_manager::deleteLinksBecauseItemIsDeleted.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $label = $this->createLabel();
        $other = $this->createLabel();

        $this->createLink($label->getItemId(), $other->getItemId(), 'buzzword_for');
        $this->createLink($other->getItemId(), $label->getItemId(), 'label_for');

        $this->deleter->softDeleteItem($label->getItemId(), $this->deleterId);

        $aliveCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NULL
                  AND deleter_id IS NULL',
            ['id' => $label->getItemId()]
        );
        self::assertSame(0, $aliveCount, 'every link referencing the deleted label must be soft-deleted');
    }

    /**
     * `link_items` rows must be soft-deleted too. Legacy labels_manager
     * did not touch `link_items`, but group-subtype labels use it for
     * user memberships (cs_group_item::addMember) — leaving those
     * dangling against a deleted group was a latent inconsistency that
     * LabelDeleter fixes.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $label = $this->createLabel();
        $other = $this->createLabel();

        $linkAsFirst = $this->createLinkItem($label->getItemId(), $other->getItemId());
        $linkAsSecond = $this->createLinkItem($other->getItemId(), $label->getItemId());

        $this->deleter->softDeleteItem($label->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkAsFirst);
        $this->assertLinkItemSoftDeleted($linkAsSecond);
    }

    /**
     * {@see ItemDeletedEvent} is dispatched so ElasticaSubscriber removes
     * the label document from `commsy_label` (legacy parity with
     * cs_label_item::deleteElasticItem).
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $label = $this->createLabel();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteItem($label->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'LabelDeleter must dispatch ItemDeletedEvent so ES cleanup etc. can hook in.'
        );
    }

    /**
     * Regression safety: deleting one label must not affect other labels
     * in the same room. Catches SQL mistakes like a missing WHERE clause.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherLabels(): void
    {
        $target = $this->createLabel();
        $bystander = $this->createLabel();

        $this->deleter->softDeleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('labels', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    /**
     * Hard-delete sweep: soft-deleted labels whose `deletion_date` is
     * older than the grace window are physically removed from `labels`.
     * Recent soft-deletes remain; alive rows stay untouched. The `items`
     * twin stays on the legacy CS_ITEM_TYPE sweep path.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRows(): void
    {
        $expired = $this->createLabel();
        $recent = $this->createLabel();
        $alive = $this->createLabel();

        $this->deleter->softDeleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->softDeleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE labels SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(1, $affected);
        $this->assertPhysicallyDeleted('labels', $expired->getItemId());
        $this->assertSoftDeleted('labels', $recent->getItemId());
        $this->assertNotSoftDeleted('labels', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(LabelDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createLabel(string $type = 'buzzword'): Labels
    {
        return LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => $type,
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
