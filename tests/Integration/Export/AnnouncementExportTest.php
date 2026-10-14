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

use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Services\LegacyEnvironment;
use App\State\PageSize;
use App\Utils\CategoryService;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\AnnouncementFactory;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\FilesFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\LinkItemFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * The first rubric in the export. What is pinned here is what every further
 * rubric will be held to: no deleted entries, no drafts, the author named, and
 * the room's vocabulary referenced rather than repeated.
 */
final class AnnouncementExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;
    private string $fileDirectory;

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAnnouncementStandsInTheDocumentWithItsAuthor(): void
    {
        $itemId = $this->createAnnouncement('Erste Ankündigung', 'Der Text.');

        $announcements = $this->announcements();

        $this->assertCount(1, $announcements);
        $this->assertSame($itemId, $announcements[0]['itemId']);
        $this->assertSame('Erste Ankündigung', $announcements[0]['title']);
        $this->assertSame('Der Text.', $announcements[0]['description']);
        $this->assertSame($this->roomUser->getFullname(), $announcements[0]['creator']['name']);
        $this->assertArrayHasKey('enddate', $announcements[0]);
        $this->assertArrayHasKey('activationDate', $announcements[0]);
        $this->assertArrayHasKey('creationDate', $announcements[0]);
    }

    /**
     * The vocabulary stands once on the room; an entry points into it.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEntryReferencesTheRoomsVocabularyById(): void
    {
        $itemId = $this->createAnnouncement('Mit Schlagwort');
        $category = $this->createCategory('Vorlesung');
        $hashtag = $this->createHashtag('Prüfung');

        LinkItemFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'firstItemId' => $itemId,
            'secondItemId' => $category,
        ]);
        LinkFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'fromItemId' => $itemId,
            'toItemId' => $hashtag,
            'linkType' => 'buzzword_for',
        ]);

        $announcement = $this->announcements()[0];

        $this->assertSame([$category], $announcement['categories']);
        $this->assertSame([$hashtag], $announcement['hashtags']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testAnEntryWithoutVocabularyCarriesEmptyLists(): void
    {
        $this->createAnnouncement('Ohne alles');

        $announcement = $this->announcements()[0];

        $this->assertSame([], $announcement['categories']);
        $this->assertSame([], $announcement['hashtags']);
    }

    /**
     * The archive carries a file once, however many entries attach it; the
     * entries point at it by id.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAttachmentIsPointedAtByTheEntryAndDescribedOnce(): void
    {
        $first = $this->createAnnouncement('Mit Anhang');
        $second = $this->createAnnouncement('Auch mit Anhang');
        $file = $this->createFile('Protokoll.pdf', 'a document');

        $this->attach($file, $first);
        $this->attach($file, $second);

        $document = $this->document();

        $this->assertSame([$file], $document['rubrics']['announcement'][0]['files']);
        $this->assertSame([$file], $document['rubrics']['announcement'][1]['files']);
        $this->assertSame(
            [['id' => $file, 'name' => 'Protokoll.pdf', 'size' => 10, 'path' => 'files/'.$file.'/Protokoll.pdf']],
            $document['files']
        );
    }

    /**
     * Rows outlive their content. The entry keeps pointing at the attachment,
     * and the document says there is nothing to fetch.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAttachmentWhoseContentIsGoneHasNoPath(): void
    {
        $itemId = $this->createAnnouncement('Mit verlorenem Anhang');
        $file = $this->createFile('Weg.pdf', contents: null);

        $this->attach($file, $itemId);

        $document = $this->document();

        $this->assertSame([$file], $document['rubrics']['announcement'][0]['files']);
        $this->assertNull($document['files'][0]['path']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutAttachmentsCarriesAnEmptyFileList(): void
    {
        $this->createAnnouncement('Ohne Anhang');

        $document = $this->document();

        $this->assertSame([], $document['rubrics']['announcement'][0]['files']);
        $this->assertSame([], $document['files']);
    }

    #[WithStory(RoomWithMemberStory::class)]
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

        $this->assertSame(['Bleibt'], $this->titles());
    }

    /**
     * A draft is nobody's content yet. The flag sits on `items`, not on the
     * rubric table.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftIsLeftOut(): void
    {
        $this->createAnnouncement('Fertig');
        $draft = $this->createAnnouncement('Entwurf');

        $this->connection->executeStatement(
            'UPDATE items SET draft = 1 WHERE item_id = :id',
            ['id' => $draft]
        );

        $this->assertSame(['Fertig'], $this->titles());
    }

    /**
     * Announcements run out by design — two weeks after they were written. That
     * hides them in the room; it does not unsay them.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAnnouncementThatHasRunOutStaysInTheExport(): void
    {
        $this->createAnnouncement('Abgelaufen', enddate: new DateTime('-1 year'));

        $this->assertSame(['Abgelaufen'], $this->titles());
    }

    /**
     * The interface hands a rubric out a page at a time; the export is never a
     * page, whatever the largest one the interface allows.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheExportCarriesMoreThanAPageHolds(): void
    {
        for ($i = 0; $i <= PageSize::MAXIMUM; ++$i) {
            $this->createAnnouncement('Ankündigung '.$i);
        }

        $this->assertCount(PageSize::MAXIMUM + 1, $this->announcements());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutAnnouncementsCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->announcements());
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->fileDirectory = 'var/tmp/commsy-announcement-export-'.getmypid();

        $this->primeLegacyEnvironment($this->room, $this->roomUser);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->absolute($this->fileDirectory));

        parent::tearDown();
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
        $managed = self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $this->room->getItemId());

        return self::getContainer()->get(RoomExportBuilder::class)->build($managed)->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function announcements(): array
    {
        return $this->document()['rubrics']['announcement'];
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return array_column($this->announcements(), 'title');
    }

    private function createAnnouncement(string $title, ?string $description = null, ?DateTime $enddate = null): int
    {
        return AnnouncementFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
            'description' => $description ?? 'Text',
            'enddate' => $enddate,
        ])->getItemId();
    }

    /**
     * Writes the row the way an upload leaves it: the stored path is relative to
     * the project, the stored name is what someone uploaded.
     */
    private function createFile(string $name, ?string $contents): int
    {
        $relativePath = $this->fileDirectory.'/'.uniqid('file', true);

        if (null !== $contents) {
            (new Filesystem())->dumpFile($this->absolute($relativePath), $contents);
        }

        return FilesFactory::createOne([
            'contextId' => $this->room->getItemId(),
            'filename' => $name,
            'filepath' => $relativePath,
            'size' => null === $contents ? 0 : strlen($contents),
        ])->getFilesId();
    }

    private function attach(int $fileId, int $itemId): void
    {
        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => 0,
            'file_id' => $fileId,
        ]);
    }

    private function absolute(string $relativePath): string
    {
        return self::getContainer()->getParameter('kernel.project_dir').'/'.$relativePath;
    }

    private function createCategory(string $title): int
    {
        return (int) self::getContainer()->get(CategoryService::class)
            ->addTag($title, $this->room->getItemId())
            ->getItemID();
    }

    private function createHashtag(string $name): int
    {
        return LabelFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'type' => 'buzzword',
            'name' => $name,
        ])->getItemId();
    }
}
