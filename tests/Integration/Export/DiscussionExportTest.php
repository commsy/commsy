<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Export;

use App\Entity\Discussions;
use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Services\LegacyEnvironment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\DiscussionArticleFactory;
use Tests\Factory\DiscussionFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * A discussion is a thread, and the thread is not a nesting of rows but a path
 * spelled out on each article. The export hands the articles over flat and lets
 * the path say who answered whom.
 */
final class DiscussionExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;

    #[WithStory(RoomWithMemberStory::class)]
    public function testADiscussionStandsInTheDocumentWithItsAuthor(): void
    {
        $itemId = $this->createDiscussion('Wie weiter?');

        $discussions = $this->discussions();

        $this->assertCount(1, $discussions);
        $this->assertSame($itemId, $discussions[0]['itemId']);
        $this->assertSame('Wie weiter?', $discussions[0]['title']);
        $this->assertSame($this->roomUser->getFullname(), $discussions[0]['creator']['name']);
    }

    /**
     * `0001.0002` is the second answer to the first article. The order the
     * articles come in is that path, so a reader sees the thread even before
     * rebuilding it.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testArticlesCarryTheThreadAsAPathAndComeInThatOrder(): void
    {
        $discussion = $this->createDiscussionEntity('Wie weiter?');

        $this->createArticle($discussion, 'Antwort auf den ersten Beitrag', '0001.0001');
        $this->createArticle($discussion, 'Erster Beitrag', '0001');
        $this->createArticle($discussion, 'Zweiter Beitrag', '0002');

        $articles = $this->discussions()[0]['articles'];

        $this->assertSame(
            ['0001', '0001.0001', '0002'],
            array_column($articles, 'position')
        );
        $this->assertSame('Erster Beitrag', $articles[0]['description']);
    }

    /**
     * An article has no title of its own — it is text by someone, at a time.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnArticleNamesWhoWroteItAndWhen(): void
    {
        $discussion = $this->createDiscussionEntity('Wie weiter?');
        $this->createArticle($discussion, 'Mein Beitrag', '0001');

        $article = $this->discussions()[0]['articles'][0];

        $this->assertSame($this->roomUser->getFullname(), $article['creator']['name']);
        $this->assertArrayHasKey('creationDate', $article);
        $this->assertArrayHasKey('modificationDate', $article);
        $this->assertArrayNotHasKey('title', $article);

        // An article is an entry in its own right and answers like one.
        $this->assertSame([], $article['files']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedArticleIsLeftOut(): void
    {
        $discussion = $this->createDiscussionEntity('Wie weiter?');
        $this->createArticle($discussion, 'Bleibt', '0001');
        $deleted = $this->createArticle($discussion, 'Gelöscht', '0002');

        $this->connection->executeStatement(
            'UPDATE discussionarticles SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->discussions()[0]['articles'], 'description'));
    }

    /**
     * An article can be a draft while its discussion is not.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftArticleIsLeftOut(): void
    {
        $discussion = $this->createDiscussionEntity('Wie weiter?');
        $draft = $this->createArticle($discussion, 'Entwurf', '0001');

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame([], $this->discussions()[0]['articles']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedDiscussionIsLeftOut(): void
    {
        $this->createDiscussion('Bleibt');
        $deleted = $this->createDiscussion('Gelöscht');

        $this->connection->executeStatement(
            'UPDATE discussions SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->discussions(), 'title'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutDiscussionsCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->discussions());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        return self::getContainer()->get(RoomExportBuilder::class)
            ->build($manager->find(Room::class, $this->room->getItemId()))
            ->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function discussions(): array
    {
        return $this->document()['rubrics']['discussion'];
    }

    private function createDiscussion(string $title): int
    {
        return $this->createDiscussionEntity($title)->getItemId();
    }

    private function createDiscussionEntity(string $title): Discussions
    {
        return DiscussionFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
        ]);
    }

    private function createArticle(Discussions $discussion, string $description, string $position): int
    {
        return DiscussionArticleFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'discussion' => $discussion,
            'description' => $description,
            'position' => $position,
        ])->getItemId();
    }
}
