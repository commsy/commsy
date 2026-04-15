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
 * Database-level integration tests for {@see DiscussionDeleter}.
 *
 * Covers the generic `deleteItem()` path (whole discussion incl. articles)
 * as well as the discussion-specific `deleteArticle()` path with its two
 * sub-cases (leaf vs. article-with-answers).
 */
final class DiscussionDeleterTest extends KernelTestCase
{
    private Connection $connection;
    private DiscussionDeleter $deleter;
    private Room $room;
    private User $roomUser;
    private int $deleterId;

    /**
     * Basis-Kontrakt: die `discussions`-Zeile und der zugehörige `items`-Twin
     * sind nach dem Löschen soft-deleted.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesDiscussionAndItemsRows(): void
    {
        $discussion = $this->createDiscussion();

        $this->deleter->deleteItem($discussion->getItemId(), $this->deleterId);

        $this->assertSoftDeleted('discussions', $discussion->getItemId());
        $this->assertSoftDeleted('items', $discussion->getItemId());
    }

    /**
     * Beim Löschen einer Diskussion werden alle zugehörigen Beiträge in einem
     * Rutsch mit soft-deleted (inkl. ihrer `items`-Zeilen). Es entstehen keine
     * Tombstones — der ganze Thread stirbt.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteCascadesToAllArticles(): void
    {
        $discussion = $this->createDiscussion();
        $a = $this->createArticle($discussion);
        $b = $this->createArticle($discussion);
        $c = $this->createArticle($discussion);

        $this->deleter->deleteItem($discussion->getItemId(), $this->deleterId);

        foreach ([$a, $b, $c] as $article) {
            $this->assertSoftDeleted('discussionarticles', $article->getItemId());
            $this->assertSoftDeleted('items', $article->getItemId());
        }
    }

    /**
     * Links (buzzword_for, label_for, …) auf der Diskussion selbst **und** auf
     * den kaskadiert gelöschten Beiträgen werden soft-deleted. Fixt die
     * Legacy-Inkonsistenz, dass `cs_discussion_manager::delete()` gar keine
     * `links`-Bereinigung ausführte.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesAllLinks(): void
    {
        $discussion = $this->createDiscussion();
        $other = $this->createDiscussion();
        $article = $this->createArticle($discussion);

        $this->createLink($discussion->getItemId(), $other->getItemId(), 'buzzword_for');
        $this->createLink($article->getItemId(), $other->getItemId(), 'label_for');

        $this->deleter->deleteItem($discussion->getItemId(), $this->deleterId);

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

    /**
     * `link_items` referenzieren Items quer über Rubriken hinweg. Beim
     * Diskussion-Delete müssen Verknüpfungen in beiden Richtungen
     * (first_item_id / second_item_id) verschwinden — sowohl für die
     * Diskussion als auch für deren Beiträge.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteSoftDeletesLinkItems(): void
    {
        $discussion = $this->createDiscussion();
        $other = $this->createDiscussion();
        $article = $this->createArticle($discussion);

        $linkOnDiscussion = $this->createLinkItem($discussion->getItemId(), $other->getItemId());
        $linkOnArticle = $this->createLinkItem($other->getItemId(), $article->getItemId());

        $this->deleter->deleteItem($discussion->getItemId(), $this->deleterId);

        $this->assertLinkItemSoftDeleted($linkOnDiscussion);
        $this->assertLinkItemSoftDeleted($linkOnArticle);
    }

    /**
     * Ein {@see ItemDeletedEvent} wird für die Diskussion dispatcht
     * (triggert ES-Cleanup etc.). Für die kaskadiert gelöschten Beiträge
     * wird bewusst **kein** eigenes Event dispatcht — Beiträge haben keinen
     * eigenen ES-Index, sie werden als Teil der Diskussion indiziert.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDispatchesItemDeletedEvent(): void
    {
        $discussion = $this->createDiscussion();

        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(TraceableEventDispatcher::class, $dispatcher);
        $dispatcher->reset();

        $this->deleter->deleteItem($discussion->getItemId(), $this->deleterId);

        $dispatched = array_filter(
            $dispatcher->getCalledListeners(),
            fn(array $call): bool => $call['event'] === ItemDeletedEvent::NAME
        );
        self::assertNotEmpty(
            $dispatched,
            'DiscussionDeleter must dispatch ItemDeletedEvent so ES cleanup etc. can hook in.'
        );
    }

    /**
     * Regressionsschutz: Diskussionen in anderen Kontexten/Räumen (bzw.
     * parallele Diskussionen im selben Raum) dürfen nicht mit-soft-deleted
     * werden.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteDoesNotAffectOtherDiscussions(): void
    {
        $target = $this->createDiscussion();
        $bystander = $this->createDiscussion();

        $this->deleter->deleteItem($target->getItemId(), $this->deleterId);

        $this->assertNotSoftDeleted('discussions', $bystander->getItemId());
        $this->assertNotSoftDeleted('items', $bystander->getItemId());
    }

    /**
     * `deleteArticle()` auf einem Blatt-Beitrag (ohne Antworten) = regulärer
     * Soft-Delete in `discussionarticles` und `items`.
     */
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
     * Datenschutz-Pfad: hat ein Beitrag Antworten, bleibt die Zeile zwar
     * erhalten (sonst zerbricht die Thread-Hierarchie), aber Inhalt und Autor
     * werden physisch gepurged: description + subject leer, creator_id +
     * modifier_id NULL, public = -2 als UI-Marker für den Platzhaltertext.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testDeleteArticleWithChildrenPurgesContent(): void
    {
        $discussion = $this->createDiscussion();
        $parent = $this->createArticle($discussion, ['position' => '1']);
        $child = $this->createArticle($discussion, ['position' => '1.1']);

        $this->deleter->deleteArticle($parent->getItemId(), $this->deleterId);

        // Parent-Beitrag bleibt alive — sonst verschwände der Antwortzweig.
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

        // Kind bleibt unangetastet.
        $this->assertNotSoftDeleted('discussionarticles', $child->getItemId());
    }

    /**
     * Beim Einzelbeitrag-Löschen wird die Parent-Diskussion re-indiziert
     * (via legacy `updateElastic`) — Beiträge haben keinen eigenen Index.
     * Hier nicht beobachtbar ohne ES, aber wir stellen sicher, dass der
     * Code-Pfad nicht wirft.
     */
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
    }
}
