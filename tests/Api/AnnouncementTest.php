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

namespace Tests\Api;

use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Utils\CategoryService;
use Tests\Factory\AnnotationFactory;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The first rubric on the interface. What is pinned here every further rubric
 * is held to: the same entries the export carries, in the same shape, and not
 * one more.
 */
#[WithStory(RoomWithMemberStory::class)]
class AnnouncementTest extends AbstractApiTestCase
{
    private Room $room;
    private User $roomUser;
    private Connection $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
    }

    public function testARoomListsItsAnnouncements(): void
    {
        $itemId = $this->createAnnouncement('Erste Ankündigung', 'Der Text.');

        $announcements = $this->listAnnouncements();

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $announcements);
        $this->assertSame($itemId, $announcements[0]['itemId']);
        $this->assertSame('Erste Ankündigung', $announcements[0]['title']);
        $this->assertSame('Der Text.', $announcements[0]['description']);
        $this->assertSame($this->roomUser->getFullname(), $announcements[0]['creator']['name']);
    }

    /**
     * The interface and the export hand out the same data objects; this is what
     * keeps them from drifting apart.
     */
    public function testAnEntryAnswersExactlyAsTheExportDoes(): void
    {
        $itemId = $this->createAnnouncement('Mit Kommentar');
        $this->createAnnouncement('Ohne alles');
        $comment = AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $itemId,
            'description' => 'Guter Hinweis',
        ])->getItemId();

        // A comment carries its own references, which is what goes missing when a
        // decorating normalizer's marker travels into it.
        $category = (int) self::getContainer()->get(CategoryService::class)
            ->addTag('Vorlesung', $this->room->getItemId())
            ->getItemID();
        LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $comment,
            'secondItemId' => $category,
        ]);

        $managed = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $this->room->getItemId());
        $exported = self::getContainer()->get(RoomExportBuilder::class)->build($managed)->content;

        $listed = $this->listAnnouncements();

        $this->assertSame([$category], $listed[0]['annotations'][0]['categories']);
        $this->assertEquals($exported['rubrics']['announcement'], $listed);
    }

    public function testACommentHangsUnderTheEntryItWasWrittenOn(): void
    {
        $itemId = $this->createAnnouncement('Ankündigung');
        AnnotationFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'linkedItemId' => $itemId,
            'description' => 'Guter Hinweis',
        ]);

        $announcement = $this->listAnnouncements()[0];

        $this->assertCount(1, $announcement['annotations']);
        $this->assertSame('Guter Hinweis', $announcement['annotations'][0]['description']);
    }

    public function testASingleAnnouncementAnswersLikeItsListEntry(): void
    {
        $itemId = $this->createAnnouncement('Einzeln');

        $listed = $this->listAnnouncements()[0];
        $single = $this->request('/api/v2/announcements/'.$itemId)->toArray();

        $this->assertResponseIsSuccessful();
        $this->assertEquals($listed, $single);
    }

    public function testAReadOnlyTokenSuffices(): void
    {
        $this->createAnnouncement('Lesbar');

        $this->request($this->listUrl(), $this->getReadOnlyToken());

        $this->assertResponseIsSuccessful();
    }

    public function testAnAnonymousRequestIsRefused(): void
    {
        $this->createAnnouncement('Nicht für alle');

        static::createClient()->request('GET', $this->listUrl(), ['headers' => ['Accept' => 'application/json']]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testADeletedAnnouncementIsLeftOut(): void
    {
        $this->createAnnouncement('Bleibt');
        $deleted = $this->createAnnouncement('Gelöscht');

        $this->connection->executeStatement(
            'UPDATE announcement SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->listAnnouncements(), 'title'));

        $this->request('/api/v2/announcements/'.$deleted);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testADraftIsLeftOut(): void
    {
        $this->createAnnouncement('Fertig');
        $draft = $this->createAnnouncement('Entwurf');

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame(['Fertig'], array_column($this->listAnnouncements(), 'title'));

        $this->request('/api/v2/announcements/'.$draft);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testARoomWithoutAnnouncementsListsNone(): void
    {
        $this->assertSame([], $this->listAnnouncements());
    }

    public function testAnUnknownAnnouncementIsNotFound(): void
    {
        $this->request('/api/v2/announcements/999999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnUnknownRoomIsNotFound(): void
    {
        $this->request('/api/v2/rooms/999999999/announcements');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * The room resource answers 404 for a private room, so its content must not
     * be reachable by the side door.
     */
    public function testARoomThatTheRoomResourceDoesNotShowKeepsItsAnnouncements(): void
    {
        $private = RoomFactory::createOne([
            'type' => 'privateroom',
            'contextId' => $this->room->getContextId(),
            'portal' => $this->room->getPortal(),
        ]);

        $this->request('/api/v2/rooms/'.$private->getItemId().'/announcements');

        $this->assertResponseStatusCodeSame(404);
    }

    // ------------------------------------------------------------------

    private function listUrl(): string
    {
        return '/api/v2/rooms/'.$this->room->getItemId().'/announcements';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listAnnouncements(): array
    {
        return $this->request($this->listUrl())->toArray();
    }

    private function request(string $url, ?string $token = null): \ApiPlatform\Symfony\Bundle\Test\Response
    {
        return $this->createClientWithCredentials($token)->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
        ]);
    }

    private function createAnnouncement(string $title, ?string $description = null): int
    {
        return AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
            'description' => $description ?? 'Text',
        ])->getItemId();
    }
}
