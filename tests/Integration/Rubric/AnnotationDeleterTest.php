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
use App\Entity\Annotations;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Rubric\Annotation\AnnotationDeleter;
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
 * Database-level integration tests for {@see AnnotationDeleter}.
 *
 * Mirrors the AnnouncementDeleterTest contract — but scoped to the
 * per-annotation deletion path used by the UI when a user explicitly
 * deletes their own comment (parent item stays alive). Deletion of an
 * annotation as part of a cascade (parent item is being removed) is
 * covered by {@see ItemDeletionHelper::softDeleteAnnotations()} tests
 * on each parent rubric.
 */
final class AnnotationDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private AnnotationDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    /**
     * Core contract: after the call, both the `annotations` row and its
     * `items` twin must be soft-deleted (deletion_date + deleter_id set),
     * while the parent item stays untouched.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAnnotationAndItemsRows(): void
    {
        $parent = $this->createAnnouncement();
        $annotationId = $this->createAnnotation($parent->getItemId());

        $this->deleter->deleteItem($annotationId, $this->deleterId);

        $this->assertSoftDeleted('annotations', $annotationId);
        $this->assertSoftDeleted('items', $annotationId);

        // Parent survives.
        $this->assertNotSoftDeleted('announcement', $parent->getItemId());
        $this->assertNotSoftDeleted('items', $parent->getItemId());
    }

    /**
     * `link_items` referencing the annotation must be soft-deleted in both
     * directions (first_item_id / second_item_id) along with their `items`
     * twin rows — mirrors the uniform behaviour established for every
     * rubric deleter.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $parent = $this->createAnnouncement();
        $other = $this->createAnnouncement();
        $annotationId = $this->createAnnotation($parent->getItemId());

        $linkAsFirst = $this->createLinkItem($annotationId, $other->getItemId());
        $linkAsSecond = $this->createLinkItem($other->getItemId(), $annotationId);

        $this->deleter->deleteItem($annotationId, $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkAsFirst);
        $this->assertLinkItemSoftDeleted($linkAsSecond);
    }

    /**
     * All `links` rows referencing the annotation must be soft-deleted
     * regardless of link type / direction. Legacy cs_annotations_manager
     * did not clean `links` up; the new deleter fixes this gap.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $parent = $this->createAnnouncement();
        $other = $this->createAnnouncement();
        $annotationId = $this->createAnnotation($parent->getItemId());

        $this->createLink($annotationId, $other->getItemId(), 'relevant_for');
        $this->createLink($other->getItemId(), $annotationId, 'label_for');

        $this->deleter->deleteItem($annotationId, $this->deleterId);

        $aliveCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NULL
                  AND deleter_id IS NULL',
            ['id' => $annotationId]
        );
        self::assertSame(0, $aliveCount, 'every link referencing the deleted annotation must be soft-deleted');

        $softDeletedCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM links
                WHERE (from_item_id = :id OR to_item_id = :id)
                  AND deletion_date IS NOT NULL
                  AND deleter_id = :deleterId',
            ['id' => $annotationId, 'deleterId' => $this->deleterId]
        );
        self::assertSame(2, $softDeletedCount, 'both link types must carry the soft-delete marker with the correct deleter_id');
    }

    /**
     * The deleter must dispatch {@see ItemDeletedEvent} so downstream
     * subscribers (ElasticaSubscriber for ES cleanup, ItemSubscriber for
     * moderator mails, etc.) can hook in.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $parent = $this->createAnnouncement();
        $annotationId = $this->createAnnotation($parent->getItemId());

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteItem($annotationId, $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'AnnotationDeleter must dispatch ItemDeletedEvent so ES cleanup, mail notifications etc. can hook in.'
        );
    }

    /**
     * Regression safety net: deleting one annotation must not affect other
     * annotations on the same parent — guards against missing WHERE clauses
     * / context-wide UPDATEs.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherAnnotations(): void
    {
        $parent = $this->createAnnouncement();
        $target = $this->createAnnotation($parent->getItemId());
        $bystander = $this->createAnnotation($parent->getItemId());

        $this->deleter->deleteItem($target, $this->deleterId);

        $this->assertNotSoftDeleted('annotations', $bystander);
        $this->assertNotSoftDeleted('items', $bystander);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(AnnotationDeleter::class);

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

    private function createAnnotation(int $parentItemId): int
    {
        /** @var Annotations $annotation */
        $annotation = AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $parentItemId,
        ]);

        return $annotation->getItemId();
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
        // inserting into `link_items`. ItemDeletionHelper must soft-delete
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
