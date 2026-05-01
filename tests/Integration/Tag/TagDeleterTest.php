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

namespace Tests\Integration\Tag;

use App\Entity\Room;
use App\Entity\User;
use App\Tag\TagDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins {@see TagDeleter}'s four-table sweep (tag / link_items / tag2tag /
 * items twin) for the recursive `softDelete()` and the single-tag
 * `softDeleteWithoutChildren()` used by the `combineTags` merge path.
 */
final class TagDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private TagDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteSweepsAllFourTablesForSingleTag(): void
    {
        $parentId = $this->createTag('root');
        $childId = $this->createTag('child');
        $this->createTag2Tag($parentId, $childId);

        $linkItemId = $this->createLinkItemForTag($parentId);

        $this->deleter->softDelete($parentId, $this->deleterId);

        $this->assertSoftDeleted('tag', $parentId);
        $this->assertSoftDeleted('items', $parentId);
        $this->assertTag2TagSoftDeleted($parentId, $childId);
        $this->assertLinkItemSoftDeleted($linkItemId);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteCascadesIntoDescendants(): void
    {
        $grandparent = $this->createTag('grandparent');
        $parent = $this->createTag('parent');
        $child = $this->createTag('child');

        $this->createTag2Tag($grandparent, $parent);
        $this->createTag2Tag($parent, $child);

        $this->deleter->softDelete($grandparent, $this->deleterId);

        $this->assertSoftDeleted('tag', $grandparent);
        $this->assertSoftDeleted('tag', $parent);
        $this->assertSoftDeleted('tag', $child);
        $this->assertSoftDeleted('items', $grandparent);
        $this->assertSoftDeleted('items', $parent);
        $this->assertSoftDeleted('items', $child);
    }

    /**
     * `combineTags` re-parents children under the merged tag, so the
     * non-recursive sweep must leave them alive.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteWithoutChildrenLeavesChildrenAlive(): void
    {
        $parent = $this->createTag('parent');
        $child = $this->createTag('child');

        $this->createTag2Tag($parent, $child);

        $this->deleter->softDeleteWithoutChildren($parent, $this->deleterId);

        $this->assertSoftDeleted('tag', $parent);
        $this->assertSoftDeleted('items', $parent);
        $this->assertNotSoftDeleted('tag', $child);
        $this->assertNotSoftDeleted('items', $child);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testSoftDeleteDoesNotAffectUnrelatedTags(): void
    {
        $target = $this->createTag('target');
        $bystander = $this->createTag('bystander');

        $this->deleter->softDelete($target, $this->deleterId);

        $this->assertSoftDeleted('tag', $target);
        $this->assertNotSoftDeleted('tag', $bystander);
        $this->assertNotSoftDeleted('items', $bystander);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(TagDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createTag(string $title): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (context_id, type, modification_date) VALUES (:ctx, :type, NOW())',
            ['ctx' => $this->room->getItemId(), 'type' => 'tag']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO tag (item_id, context_id, creator_id, creation_date, modification_date, title)
                VALUES (:itemId, :ctx, :creatorId, NOW(), NOW(), :title)',
            [
                'itemId' => $itemId,
                'ctx' => $this->room->getItemId(),
                'creatorId' => $this->roomUser->getItemId(),
                'title' => $title,
            ]
        );

        return $itemId;
    }

    private function createTag2Tag(int $fromId, int $toId): void
    {
        $this->connection->executeStatement(
            'INSERT INTO tag2tag (from_item_id, to_item_id, context_id, creator_id, creation_date, modifier_id, modification_date)
                VALUES (:fromId, :toId, :ctx, :creatorId, NOW(), :creatorId, NOW())',
            [
                'fromId' => $fromId,
                'toId' => $toId,
                'ctx' => $this->room->getItemId(),
                'creatorId' => $this->roomUser->getItemId(),
            ]
        );
    }

    private function createLinkItemForTag(int $tagId): int
    {
        $this->connection->executeStatement(
            'INSERT INTO items (context_id, type, modification_date) VALUES (:ctx, :type, NOW())',
            ['ctx' => $this->room->getItemId(), 'type' => 'link_item']
        );
        $itemId = (int) $this->connection->lastInsertId();

        $this->connection->executeStatement(
            'INSERT INTO link_items (item_id, context_id, creator_id, creation_date, first_item_id, first_item_type, second_item_id, second_item_type)
                VALUES (:itemId, :ctx, :creatorId, NOW(), :tagId, :tagType, :other, :otherType)',
            [
                'itemId' => $itemId,
                'ctx' => $this->room->getItemId(),
                'creatorId' => $this->roomUser->getItemId(),
                'tagId' => $tagId,
                'tagType' => 'tag',
                'other' => 1,
                'otherType' => 'announcement',
            ]
        );

        return $itemId;
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
            sprintf('SELECT deletion_date FROM %s WHERE item_id = :id', $table),
            ['id' => $itemId]
        );
        self::assertIsArray($row, sprintf('Expected %s row for item %d', $table, $itemId));
        self::assertNull($row['deletion_date'], sprintf('%s row %d must still be alive', $table, $itemId));
    }

    private function assertTag2TagSoftDeleted(int $fromId, int $toId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM tag2tag WHERE from_item_id = :fromId AND to_item_id = :toId',
            ['fromId' => $fromId, 'toId' => $toId]
        );
        self::assertIsArray($row, sprintf('Expected tag2tag row %d -> %d', $fromId, $toId));
        self::assertNotNull($row['deletion_date'], 'tag2tag pivot must be soft-deleted');
        self::assertSame($this->deleterId, (int) $row['deleter_id']);
    }

    private function assertLinkItemSoftDeleted(int $linkItemId): void
    {
        $row = $this->connection->fetchAssociative(
            'SELECT deletion_date, deleter_id FROM link_items WHERE item_id = :id',
            ['id' => $linkItemId]
        );
        self::assertIsArray($row, sprintf('Expected link_items row for id %d', $linkItemId));
        self::assertNotNull($row['deletion_date'], 'link_items row must be soft-deleted');
        self::assertSame($this->deleterId, (int) $row['deleter_id']);
    }
}
