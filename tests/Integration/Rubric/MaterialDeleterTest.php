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

use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\Section;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Event\ItemReindexEvent;
use App\Rubric\Material\MaterialDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Database-level integration tests for {@see MaterialDeleter}.
 *
 * Covers the three public entry points:
 *  - `deleteItem()`           — CS_ALL, whole material + every section
 *                                version + versioned file links
 *  - `deleteCurrentVersion()` — only the latest version is dropped, items
 *                                row survives, parent gets reindexed
 *  - `deleteSection()`        — single section within a specific material
 *                                version, parent material reindexed
 */
final class MaterialDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private MaterialDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesMaterialAndItemsRows(): void
    {
        $material = $this->createMaterial();

        $this->deleter->deleteItem($material->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('materials', $material->getItemId());
        $this->assertSoftDeleted('items', $material->getItemId());
    }

    /**
     * CS_ALL: every section (all versions) of the material disappears.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCascadesToAllSections(): void
    {
        $material = $this->createMaterial();
        $a = $this->createSection($material);
        $b = $this->createSection($material);
        $c = $this->createSection($material);

        $this->deleter->deleteItem($material->getItemId(), $this->deleterId);

        foreach ([$a, $b, $c] as $section) {
            $this->assertSoftDeleted('section', $section->getItemId());
            $this->assertSoftDeleted('items', $section->getItemId());
        }
    }

    /**
     * Links on the material *and* on cascading sections must be
     * soft-deleted — the legacy `cs_section_item::delete()` already pulled
     * the `links` rows, we keep parity here and also cover the material.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $material = $this->createMaterial();
        $other = $this->createMaterial();
        $section = $this->createSection($material);

        $this->createLink($material->getItemId(), $other->getItemId(), 'buzzword_for');
        $this->createLink($section->getItemId(), $other->getItemId(), 'label_for');

        $this->deleter->deleteItem($material->getItemId(), $this->deleterId);

        foreach ([$material->getItemId(), $section->getItemId()] as $sourceId) {
            $alive = (int) $this->connection->fetchOne(
                'SELECT COUNT(*) FROM links
                    WHERE (from_item_id = :id OR to_item_id = :id)
                      AND deletion_date IS NULL
                      AND deleter_id IS NULL',
                ['id' => $sourceId]
            );
            self::assertSame(0, $alive, sprintf('links referencing item %d must be soft-deleted', $sourceId));
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $material = $this->createMaterial();
        $other = $this->createMaterial();
        $section = $this->createSection($material);

        $linkOnMaterial = $this->createLinkItem($material->getItemId(), $other->getItemId());
        $linkOnSection = $this->createLinkItem($other->getItemId(), $section->getItemId());

        $this->deleter->deleteItem($material->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkOnMaterial);
        $this->assertLinkItemSoftDeleted($linkOnSection);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $material = $this->createMaterial();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteItem($material->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty($dispatched, 'MaterialDeleter must dispatch ItemDeletedEvent.');
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherMaterials(): void
    {
        $target = $this->createMaterial();
        $bystander = $this->createMaterial();

        $this->deleter->deleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('materials', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    /**
     * `deleteSection()` without version filter removes the section
     * across all versions and dispatches a reindex for the parent material.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSectionSoftDeletesSectionAndReindexesMaterial(): void
    {
        $material = $this->createMaterial();
        $section = $this->createSection($material);

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteSection($section->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('section', $section->getItemId());
        $this->assertSoftDeleted('items', $section->getItemId());
        $this->assertNotSoftDeleted('materials', $material->getItemId());

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemReindexEvent::class
        );
        self::assertNotEmpty(
            $dispatched,
            'deleteSection() must dispatch ItemReindexEvent for the parent material.'
        );
    }

    /**
     * `deleteCurrentVersion()` on a material with only one version drops
     * that version but leaves the `items` row alive so the material entity
     * itself keeps existing for UI rollback scenarios.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCurrentVersionKeepsItemsRowAlive(): void
    {
        $material = $this->createMaterial();

        $this->deleter->deleteCurrentVersion($material->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('materials', $material->getItemId());
        $this->assertNotSoftDeleted('items', $material->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCurrentVersionDispatchesReindexEvent(): void
    {
        $material = $this->createMaterial();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteCurrentVersion($material->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemReindexEvent::class
        );
        self::assertNotEmpty(
            $dispatched,
            'deleteCurrentVersion() must dispatch ItemReindexEvent.'
        );
    }

    /**
     * Hard-delete sweep covers both rubric-owned tables: `materials`
     * (all versions) and `section` (all versions, any parent). The
     * orchestrator no longer needs to know about Material's versioning
     * or sub-entry structure — the deleter owns that concern.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRowsIncludingSections(): void
    {
        $expired = $this->createMaterial();
        $expiredSection = $this->createSection($expired);
        $recent = $this->createMaterial();
        $alive = $this->createMaterial();

        $this->deleter->deleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->deleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE materials SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );
        $this->connection->executeStatement(
            'UPDATE section SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expiredSection->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(2, $affected);
        $this->assertPhysicallyDeleted('materials', $expired->getItemId());
        $this->assertPhysicallyDeleted('section', $expiredSection->getItemId());
        $this->assertSoftDeleted('materials', $recent->getItemId());
        $this->assertNotSoftDeleted('materials', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(MaterialDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createMaterial(): Materials
    {
        return MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    private function createSection(Materials $material): Section
    {
        return SectionFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'material' => $material,
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
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id LIMIT 1', $table),
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
            sprintf('SELECT deletion_date, deleter_id FROM %s WHERE item_id = :id LIMIT 1', $table),
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
