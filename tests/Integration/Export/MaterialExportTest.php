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

use App\Entity\License;
use App\Entity\Materials;
use App\Entity\Room;
use App\Entity\User;
use App\Export\RoomExportBuilder;
use App\Services\LegacyEnvironment;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\FilesFactory;
use Tests\Factory\LabelFactory;
use Tests\Factory\LinkFactory;
use Tests\Factory\MaterialFactory;
use Tests\Factory\SectionFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;

/**
 * Materials are the first rubric that keeps versions, and a version is what
 * decides an entry's attachments and hashtags. The export carries the version
 * the room shows and nothing it has since lost.
 */
final class MaterialExportTest extends KernelTestCase
{
    use PrimesLegacyEnvironment;

    private Room $room;
    private User $roomUser;
    private Connection $connection;
    private string $fileDirectory;

    #[WithStory(RoomWithMemberStory::class)]
    public function testAMaterialStandsInTheDocumentWithItsAuthorAndItsFields(): void
    {
        $itemId = $this->createMaterial('Skript');

        $materials = $this->materials();

        $this->assertCount(1, $materials);
        $this->assertSame($itemId, $materials[0]['itemId']);
        $this->assertSame('Skript', $materials[0]['title']);
        $this->assertSame($this->roomUser->getFullname(), $materials[0]['creator']['name']);
        $this->assertArrayHasKey('author', $materials[0]);
        $this->assertArrayHasKey('publishingDate', $materials[0]);
        $this->assertArrayHasKey('workflowStatus', $materials[0]);
    }

    /**
     * Older versions are history the room does not show.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testOnlyTheVersionTheRoomShowsIsExported(): void
    {
        $itemId = $this->createMaterial('Erste Fassung');
        $this->addVersion($itemId, 'Zweite Fassung');

        $materials = $this->materials();

        $this->assertCount(1, $materials);
        $this->assertSame('Zweite Fassung', $materials[0]['title']);
    }

    /**
     * The case that decided the whole file layer: an attachment dropped while
     * editing stays on the old version, and reading the room's files as a whole
     * would put it back into the archive.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAttachmentDroppedInAnEarlierVersionStaysOut(): void
    {
        $itemId = $this->createMaterial('Skript');
        $version = $this->currentVersion($itemId);

        $dropped = $this->createFile('Entwurf.pdf', 'old');
        $kept = $this->createFile('Endfassung.pdf', 'new');

        $this->attach($dropped, $itemId, $version);
        $this->addVersion($itemId, 'Skript, überarbeitet');
        $this->attach($kept, $itemId, $this->currentVersion($itemId));

        $document = $this->document();

        $this->assertSame([$kept], $document['rubrics']['material'][0]['files']);
        $this->assertSame([$kept], array_column($document['files'], 'id'));
    }

    /**
     * Hashtags are versioned the same way.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAHashtagOfTheCurrentVersionIsTheOneExported(): void
    {
        $itemId = $this->createMaterial('Skript');
        $old = $this->createHashtag('Alt');
        $new = $this->createHashtag('Neu');

        $this->tag($itemId, $this->currentVersion($itemId), $old);
        $this->addVersion($itemId, 'Skript, überarbeitet');
        $this->tag($itemId, $this->currentVersion($itemId), $new);

        $this->assertSame([$new], $this->materials()[0]['hashtags']);
    }

    /**
     * The citation is content; the rest of `extras` is bookkeeping.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheCitationIsCarriedAndTheRestOfExtrasIsNot(): void
    {
        $itemId = $this->createMaterial('Aufsatz');

        $this->setExtras($itemId, ['BIB_KIND' => 'article', 'BIBLIOGRAPHIC' => 'In: Pädagogik 2003 Nr. 1']);

        $material = $this->materials()[0];

        $this->assertSame(['kind' => 'article', 'text' => 'In: Pädagogik 2003 Nr. 1'], $material['bibliographic']);
        $this->assertArrayNotHasKey('extras', $material);
    }

    /**
     * Legacy stores "no kind chosen" as a word rather than as nothing.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAMaterialWithoutACitationCarriesNone(): void
    {
        $itemId = $this->createMaterial('Skript');

        $this->setExtras($itemId, ['BIB_KIND' => 'none', 'BIBLIOGRAPHIC' => '']);

        $this->assertNull($this->materials()[0]['bibliographic']);
    }

    /**
     * A material points at one of the room's licences; the licence itself stands
     * on the room.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAMaterialPointsAtALicenceTheRoomDescribes(): void
    {
        $licenseId = $this->createLicense('CC BY-SA', 'Namensnennung, Weitergabe unter gleichen Bedingungen');
        $itemId = $this->createMaterial('Skript');

        $this->connection->executeStatement(
            'UPDATE materials SET license_id = :license WHERE item_id = :id',
            ['license' => $licenseId, 'id' => $itemId]
        );

        $document = $this->document();

        $this->assertSame($licenseId, $document['rubrics']['material'][0]['licenseId']);
        $this->assertSame(
            [['id' => $licenseId, 'title' => 'CC BY-SA', 'content' => 'Namensnennung, Weitergabe unter gleichen Bedingungen']],
            $document['room']['licenses']
        );
    }

    /**
     * A material is written in sections. They hang under it rather than standing
     * beside it, and they keep the order the material gives them.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAMaterialCarriesItsSectionsInTheOrderItGivesThem(): void
    {
        $material = $this->createMaterialEntity('Skript');
        $first = $this->createSection($material, 'Einleitung');
        $second = $this->createSection($material, 'Hauptteil');

        $this->numberSections([$first => 1, $second => 2]);

        $sections = $this->materials()[0]['sections'];

        $this->assertSame(['Einleitung', 'Hauptteil'], array_column($sections, 'title'));
        $this->assertSame([1, 2], array_column($sections, 'number'));
        $this->assertSame($first, $sections[0]['itemId']);

        // A section is an entry in its own right and answers like one.
        $this->assertSame([], $sections[0]['categories']);
        $this->assertSame([], $sections[0]['hashtags']);
        $this->assertSame([], $sections[0]['files']);
    }

    /**
     * Sections belong to a material version too, so an older version's sections
     * are as much history as the version itself.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testSectionsOfAnEarlierVersionStayOut(): void
    {
        $material = $this->createMaterialEntity('Skript');
        $this->createSection($material, 'Alte Gliederung');

        $this->addVersion($material->getItemId(), 'Skript, überarbeitet');

        $this->assertSame([], $this->materials()[0]['sections']);
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedSectionIsLeftOut(): void
    {
        $material = $this->createMaterialEntity('Skript');
        $this->createSection($material, 'Bleibt');
        $deleted = $this->createSection($material, 'Gelöscht');

        $this->connection->executeStatement(
            'UPDATE section SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], array_column($this->materials()[0]['sections'], 'title'));
    }

    /**
     * An attachment on a section has to reach the archive like any other — the
     * document names it, so the archive has to hold it.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testAnAttachmentOnASectionTravelsWithTheArchive(): void
    {
        $material = $this->createMaterialEntity('Skript');
        $sectionId = $this->createSection($material, 'Anhangskapitel');
        $file = $this->createFile('Tabelle.xlsx', 'numbers');

        $this->attach($file, $sectionId, $material->getVersionId());

        $document = $this->document();

        $this->assertSame([$file], $document['rubrics']['material'][0]['sections'][0]['files']);
        $this->assertSame([$file], array_column($document['files'], 'id'));
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADeletedMaterialIsLeftOut(): void
    {
        $this->createMaterial('Bleibt');
        $deleted = $this->createMaterial('Gelöscht');

        $this->connection->executeStatement(
            'UPDATE materials SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );
        $this->connection->executeStatement(
            'UPDATE items SET deleter_id = :deleter, deletion_date = NOW() WHERE item_id = :id',
            ['deleter' => $this->roomUser->getItemId(), 'id' => $deleted]
        );

        $this->assertSame(['Bleibt'], $this->titles());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testADraftIsLeftOut(): void
    {
        $this->createMaterial('Fertig');
        $draft = $this->createMaterial('Entwurf');

        $this->connection->executeStatement('UPDATE items SET draft = 1 WHERE item_id = :id', ['id' => $draft]);

        $this->assertSame(['Fertig'], $this->titles());
    }

    #[WithStory(RoomWithMemberStory::class)]
    public function testARoomWithoutMaterialsCarriesAnEmptyList(): void
    {
        $this->assertSame([], $this->materials());
        $this->assertSame([], $this->document()['room']['licenses']);
    }

    // ------------------------------------------------------------------

    protected function setUp(): void
    {
        self::bootKernel();

        $this->room = RoomWithMemberStory::get('room');
        $this->roomUser = RoomWithMemberStory::get('roomUser');
        $this->connection = self::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $this->fileDirectory = 'var/tmp/commsy-material-export-'.getmypid();

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
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->clear();

        return self::getContainer()->get(RoomExportBuilder::class)
            ->build($manager->find(Room::class, $this->room->getItemId()))
            ->content;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function materials(): array
    {
        return $this->document()['rubrics']['material'];
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return array_column($this->materials(), 'title');
    }

    private function createMaterial(string $title): int
    {
        return $this->createMaterialEntity($title)->getItemId();
    }

    private function createMaterialEntity(string $title): Materials
    {
        return MaterialFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'title' => $title,
        ]);
    }

    private function createSection(Materials $material, string $title): int
    {
        return SectionFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'material' => $material,
            'title' => $title,
        ])->getItemId();
    }

    /**
     * Legacy numbers sections as they are written; the test says which order it
     * means.
     *
     * @param array<int, int> $numberByItemId
     */
    private function numberSections(array $numberByItemId): void
    {
        foreach ($numberByItemId as $itemId => $number) {
            $this->connection->executeStatement(
                'UPDATE section SET number = :number WHERE item_id = :id',
                ['number' => $number, 'id' => $itemId]
            );
        }
    }

    private function currentVersion(int $itemId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT MAX(version_id) FROM materials WHERE item_id = :id',
            ['id' => $itemId]
        );
    }

    /**
     * Editing a material writes a new row rather than changing the old one,
     * which is what keeps the older attachments where they are.
     */
    private function addVersion(int $itemId, string $title): void
    {
        $version = $this->currentVersion($itemId) + 1;

        $this->connection->executeStatement(
            'INSERT INTO materials (item_id, version_id, context_id, creator_id, creation_date, title, description, workflow_status)
             SELECT item_id, :version, context_id, creator_id, creation_date, :title, description, workflow_status
             FROM materials WHERE item_id = :id AND version_id = :current',
            [
                'version' => $version,
                'title' => $title,
                'id' => $itemId,
                'current' => $version - 1,
            ]
        );
    }

    /**
     * @param array<string, string> $extras
     */
    private function setExtras(int $itemId, array $extras): void
    {
        $this->connection->executeStatement(
            'UPDATE materials SET extras = :extras WHERE item_id = :id',
            ['extras' => serialize($extras), 'id' => $itemId]
        );
    }

    private function createLicense(string $title, string $content): int
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $license = new License();
        $license->setContextId($this->room->getItemId());
        $license->setTitle($title);
        $license->setContent($content);
        $license->setPosition(1);

        $manager->persist($license);
        $manager->flush();

        return (int) $license->getId();
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

    /**
     * A hashtag link records the version of the entry it was written on, which
     * the factory cannot know.
     */
    private function tag(int $itemId, int $versionId, int $hashtagId): void
    {
        LinkFactory::createOne([
            'room' => $this->room,
            'creator' => $this->roomUser,
            'fromItemId' => $itemId,
            'toItemId' => $hashtagId,
            'linkType' => 'buzzword_for',
        ]);

        $this->connection->executeStatement(
            'UPDATE links SET from_version_id = :version WHERE from_item_id = :item AND to_item_id = :hashtag',
            ['version' => $versionId, 'item' => $itemId, 'hashtag' => $hashtagId]
        );
    }

    private function createFile(string $name, string $contents): int
    {
        $relativePath = $this->fileDirectory.'/'.uniqid('file', true);
        (new Filesystem())->dumpFile($this->absolute($relativePath), $contents);

        return FilesFactory::createOne([
            'contextId' => $this->room->getItemId(),
            'filename' => $name,
            'filepath' => $relativePath,
            'size' => strlen($contents),
        ])->getFilesId();
    }

    private function attach(int $fileId, int $itemId, int $versionId): void
    {
        $this->connection->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => $versionId,
            'file_id' => $fileId,
        ]);
    }

    private function absolute(string $relativePath): string
    {
        return self::getContainer()->getParameter('kernel.project_dir').'/'.$relativePath;
    }
}
