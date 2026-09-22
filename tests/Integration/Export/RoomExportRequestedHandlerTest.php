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
use App\Entity\RoomExport;
use App\Export\RoomExportArchiver;
use App\Export\RoomExportBuilder;
use App\Export\RoomExportStatus;
use App\Export\RoomExportStorage;
use App\Message\RoomExportRequested;
use App\MessageHandler\RoomExportRequestedHandler;
use App\Repository\RoomExportRepository;
use App\Services\LegacyEnvironment;
use Psr\Log\NullLogger;
use RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\AnnouncementFactory;
use Tests\Factory\Concerns\PrimesLegacyEnvironment;
use Tests\Factory\FilesFactory;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Tests\Story\RoomWithMemberStory;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;
use ZipArchive;

/**
 * The handler is the only thing that turns a request into an archive, so what it
 * leaves behind on the row is what the settings page shows.
 */
class RoomExportRequestedHandlerTest extends KernelTestCase
{
    use Factories;
    use PrimesLegacyEnvironment;
    use ResetDatabase;

    public function testItBuildsTheArchiveAndRecordsTheResult(): void
    {
        self::bootKernel();

        $export = $this->requestExport();

        $this->handle($export);

        $this->assertSame(RoomExportStatus::Finished, $export->getStatus());
        $this->assertNotNull($export->getFilename());
        $this->assertNotNull($export->getFinishedAt());
        $this->assertGreaterThan(0, (int) $export->getSizeInBytes());
        $this->assertNull($export->getFailureReason());

        $storage = self::getContainer()->get(RoomExportStorage::class);
        $this->assertTrue($storage->exists($export), 'the archive is on disk');

        $zip = new ZipArchive();
        $zip->open($storage->pathFor($export));
        $document = json_decode((string) $zip->getFromName('export.json'), true, 512, JSON_THROW_ON_ERROR);
        $zip->close();

        $this->assertSame(1, $document['formatVersion']);
        $this->assertSame('Raum zum Export', $document['room']['title']);

        (new Filesystem())->remove($storage->directoryFor($export));
    }

    public function testAnExportThatHasAlreadySettledIsLeftAlone(): void
    {
        self::bootKernel();

        $export = $this->requestExport();
        $export->markFailed('an earlier attempt');
        $this->repository()->save($export);

        $this->handle($export);

        // A duplicate delivery must not resurrect a settled run.
        $this->assertSame(RoomExportStatus::Failed, $export->getStatus());
        $this->assertSame('an earlier attempt', $export->getFailureReason());
    }

    /**
     * The whole chain in one go: an entry's attachment has to be in the archive,
     * under the path the document names for it.
     */
    #[WithStory(RoomWithMemberStory::class)]
    public function testTheArchiveCarriesTheAttachmentsTheDocumentNames(): void
    {
        self::bootKernel();

        $room = RoomWithMemberStory::get('room');
        $roomUser = RoomWithMemberStory::get('roomUser');
        $this->primeLegacyEnvironment($room, $roomUser);

        $itemId = AnnouncementFactory::createOne([
            'room' => $room,
            'creator' => $roomUser,
            'title' => 'Mit Anhang',
        ])->getItemId();

        $fileId = $this->attachFile($itemId, $room->getItemId(), 'Protokoll.pdf', 'a document');

        $export = new RoomExport($this->managedRoom($room->getItemId()));
        $this->repository()->save($export);

        $this->handle($export);

        $storage = self::getContainer()->get(RoomExportStorage::class);

        $zip = new ZipArchive();
        $zip->open($storage->pathFor($export));
        $carried = $zip->getFromName('files/'.$fileId.'/Protokoll.pdf');
        $document = json_decode((string) $zip->getFromName('export.json'), true, 512, JSON_THROW_ON_ERROR);
        $zip->close();

        $this->assertSame('a document', $carried);
        $this->assertSame([$fileId], $document['rubrics']['announcement'][0]['files']);
        $this->assertSame('files/'.$fileId.'/Protokoll.pdf', $document['files'][0]['path']);

        (new Filesystem())->remove($storage->directoryFor($export));
    }

    /**
     * The transport hands a message that was claimed by a worker which never
     * acknowledged it back out after an hour. By then the row may have been
     * settled as abandoned and a new export asked for, so the late copy has
     * nothing left to do.
     */
    public function testALateDeliveryOfAnAbandonedExportDoesNothing(): void
    {
        self::bootKernel();

        $export = $this->requestExport();
        $export->markAbandoned();
        $this->repository()->save($export);

        $this->handle($export);

        $this->assertSame(RoomExportStatus::Abandoned, $export->getStatus());
        $this->assertNull($export->getFilename());
    }

    /**
     * A failure belongs on the row, because the row is what the moderation sees.
     * Rethrowing would put it in the failed queue instead, where retrying the
     * same room with the same code would fail the same way.
     */
    public function testAFailureIsRecordedOnTheRowAndNotRethrown(): void
    {
        self::bootKernel();

        $export = $this->requestExport();

        $failing = new readonly class extends RoomExportArchiver {
            public function write(string $targetPath, array $document, array $files = []): int
            {
                throw new RuntimeException('disk went away');
            }
        };

        $handler = new RoomExportRequestedHandler(
            $this->repository(),
            self::getContainer()->get(RoomExportBuilder::class),
            $failing,
            self::getContainer()->get(RoomExportStorage::class),
            new NullLogger()
        );

        $handler(new RoomExportRequested((int) $export->getId()));

        $this->assertSame(RoomExportStatus::Failed, $export->getStatus());
        $this->assertSame('disk went away', $export->getFailureReason());
        $this->assertNotNull($export->getFinishedAt());
    }

    public function testAMissingRowIsNotAnError(): void
    {
        self::bootKernel();

        // The room was deleted while the message waited, taking its export rows
        // with it.
        $handler = self::getContainer()->get(RoomExportRequestedHandler::class);

        $handler(new RoomExportRequested(987654));

        $this->expectNotToPerformAssertions();
    }

    /**
     * Writes an uploaded file the way CommSy stores one and hangs it on the
     * entry: a path relative to the project, the uploaded name kept apart.
     */
    private function attachFile(int $itemId, int $contextId, string $name, string $contents): int
    {
        $relativePath = 'var/tmp/commsy-export-handler-'.getmypid().'/'.uniqid('file', true);
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        (new Filesystem())->dumpFile($projectDir.'/'.$relativePath, $contents);

        $fileId = FilesFactory::createOne([
            'contextId' => $contextId,
            'filename' => $name,
            'filepath' => $relativePath,
            'size' => strlen($contents),
        ])->getFilesId();

        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->insert('item_link_file', [
            'item_iid' => $itemId,
            'item_vid' => 0,
            'file_id' => $fileId,
        ]);

        return $fileId;
    }

    private function requestExport(): RoomExport
    {
        $room = $this->managedRoom(
            RoomFactory::createOne(['title' => 'Raum zum Export', 'portal' => PortalFactory::createOne()])->getItemId()
        );

        $export = new RoomExport($room, 4711, 'Moderierende Person');
        $this->repository()->save($export);

        return $export;
    }

    private function handle(RoomExport $export): void
    {
        $handler = self::getContainer()->get(RoomExportRequestedHandler::class);

        $handler(new RoomExportRequested((int) $export->getId()));
    }

    private function repository(): RoomExportRepository
    {
        return self::getContainer()->get(RoomExportRepository::class);
    }

    protected function getLegacyEnvironmentService(): LegacyEnvironment
    {
        return self::getContainer()->get(LegacyEnvironment::class);
    }

    /**
     * RoomFactory writes its rows with raw SQL, so the object it returns is
     * detached and its lifecycle-filled dates were never set. Anything that
     * hands the room to Doctrine or to the serializer needs the managed row.
     */
    private function managedRoom(int $itemId): Room
    {
        return self::getContainer()->get(EntityManagerInterface::class)
            ->find(Room::class, $itemId);
    }
}
