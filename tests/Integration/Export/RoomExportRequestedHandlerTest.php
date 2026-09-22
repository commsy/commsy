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
use Psr\Log\NullLogger;
use RuntimeException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
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
