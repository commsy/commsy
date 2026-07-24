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

use App\Entity\Discussionarticles;
use App\Entity\Discussions;
use App\Entity\Room;
use App\Entity\User;
use App\Event\ItemDeletedEvent;
use App\Rubric\Discussion\DiscussionDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Pins the DiscussionDeleter contract: generic `softDeleteItem()` (whole
 * discussion incl. articles) and `deleteArticle()` (leaf vs. article-with-
 * answers GDPR purge).
 */
final class DiscussionDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private DiscussionDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesDiscussionAndItemsRows(): void
    {
        $discussion = $this->createDiscussion();

        $this->deleter->softDeleteItem($discussion->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('discussions', $discussion->getItemId());
        $this->assertSoftDeleted('items', $discussion->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCascadesToAllArticles(): void
    {
        $discussion = $this->createDiscussion();
        $a = $this->createArticle($discussion);
        $b = $this->createArticle($discussion);
        $c = $this->createArticle($discussion);

        $this->deleter->softDeleteItem($discussion->getItemId(), $this->deleterId);

        foreach ([$a, $b, $c] as $article) {
            $this->assertSoftDeleted('discussionarticles', $article->getItemId());
            $this->assertSoftDeleted('items', $article->getItemId());
        }
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $discussion = $this->createDiscussion();
        $other = $this->createDiscussion();
        $article = $this->createArticle($discussion);

        $this->createLink($discussion->getItemId(), $other->getItemId(), 'buzzword_for');
        $this->createLink($article->getItemId(), $other->getItemId(), 'label_for');

        $this->deleter->softDeleteItem($discussion->getItemId(), $this->deleterId);

        foreach ([$discussion->getItemId(), $article->getItemId()] as $sourceId) {
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
        $discussion = $this->createDiscussion();
        $other = $this->createDiscussion();
        $article = $this->createArticle($discussion);

        $linkOnDiscussion = $this->createLinkItem($discussion->getItemId(), $other->getItemId());
        $linkOnArticle = $this->createLinkItem($other->getItemId(), $article->getItemId());

        $this->deleter->softDeleteItem($discussion->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkOnDiscussion);
        $this->assertLinkItemSoftDeleted($linkOnArticle);
    }

    /**
     * Cascaded articles deliberately get no own event — they are indexed as
     * part of the parent discussion's ES document.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $discussion = $this->createDiscussion();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->softDeleteItem($discussion->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'DiscussionDeleter must dispatch ItemDeletedEvent so ES cleanup etc. can hook in.'
        );
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherDiscussions(): void
    {
        $target = $this->createDiscussion();
        $bystander = $this->createDiscussion();

        $this->deleter->softDeleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('discussions', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteArticleLeafSoftDeletesRow(): void
    {
        $discussion = $this->createDiscussion();
        $article = $this->createArticle($discussion, ['position' => '1']);

        $this->deleter->deleteArticle($article->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('discussionarticles', $article->getItemId());
        $this->assertSoftDeleted('items', $article->getItemId());
    }

    /**
     * GDPR path: articles with answers stay alive (thread hierarchy) but
     * content + author references are physically purged; public = -2 is the
     * UI placeholder marker.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteArticleWithChildrenPurgesContent(): void
    {
        $discussion = $this->createDiscussion();
        $parent = $this->createArticle($discussion, ['position' => '1']);
        $child = $this->createArticle($discussion, ['position' => '1.1']);

        $this->deleter->deleteArticle($parent->getItemId(), $this->deleterId);

        $row = $this->connection->fetchAssociative(
            'SELECT description, creator_id, modifier_id, public, deletion_date, deleter_id
                FROM discussionarticles WHERE item_id = :id',
            ['id' => $parent->getItemId()]
        );
        self::assertIsArray($row);
        self::assertNull($row['deletion_date'], 'article with children must stay alive');
        self::assertNull($row['deleter_id']);
        self::assertSame('', (string) $row['description'], 'description must be physically erased');
        self::assertNull($row['creator_id'], 'creator_id must be NULLed for anonymisation');
        self::assertNull($row['modifier_id'], 'modifier_id must be NULLed for anonymisation');
        self::assertSame(-2, (int) $row['public'], 'public = -2 keeps the legacy UI placeholder working');

        $this->assertNotSoftDeleted('discussionarticles', $child->getItemId());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteArticleDoesNotAffectOtherDiscussions(): void
    {
        $discussion = $this->createDiscussion();
        $article = $this->createArticle($discussion, ['position' => '1']);
        $bystander = $this->createDiscussion();

        $this->deleter->deleteArticle($article->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('discussions', $discussion->getItemId());
        $this->assertNotSoftDeleted('discussions', $bystander->getItemId());
    }

    /**
     * Hard-delete covers both `discussions` and `discussionarticles` —
     * closes the legacy gap where CronHardDelete only swept the 'discussion' type.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testHardDeleteOlderThanPhysicallyRemovesExpiredRowsIncludingArticles(): void
    {
        $expired = $this->createDiscussion();
        $expiredArticle = $this->createArticle($expired, ['position' => '1']);
        $recent = $this->createDiscussion();
        $alive = $this->createDiscussion();

        $this->deleter->softDeleteItem($expired->getItemId(), $this->deleterId);
        $this->deleter->softDeleteItem($recent->getItemId(), $this->deleterId);

        $this->connection->executeStatement(
            'UPDATE discussions SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expired->getItemId()]
        );
        $this->connection->executeStatement(
            'UPDATE discussionarticles SET deletion_date = DATE_SUB(NOW(), INTERVAL 40 DAY) WHERE item_id = :id',
            ['id' => $expiredArticle->getItemId()]
        );

        $affected = $this->deleter->hardDeleteOlderThan(30);

        self::assertGreaterThanOrEqual(2, $affected);
        $this->assertPhysicallyDeleted('discussions', $expired->getItemId());
        $this->assertPhysicallyDeleted('discussionarticles', $expiredArticle->getItemId());
        $this->assertSoftDeleted('discussions', $recent->getItemId());
        $this->assertNotSoftDeleted('discussions', $alive->getItemId());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
        $this->deleter = self::getContainer()->get(DiscussionDeleter::class);

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->deleterId = $this->roomUser->getItemId();
    }

    private function createDiscussion(): Discussions
    {
        return DiscussionFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
        ]);
    }

    private function createArticle(Discussions $discussion, array $overrides = []): Discussionarticles
    {
        return DiscussionArticleFactory::createOne(array_merge([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'discussion' => $discussion,
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
