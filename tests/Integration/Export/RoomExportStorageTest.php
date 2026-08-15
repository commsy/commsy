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
use App\Export\RoomExportStorage;
use App\Repository\RoomExportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RoomExportStorageTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testArchivesLiveUnderTheSweptTemporaryDirectory(): void
    {
        self::bootKernel();

        $storage = self::getContainer()->get(RoomExportStorage::class);

        // Under files/temp on purpose: the cleanup cron sweeps that by age, and
        // an archive holding a whole room's content is meant to expire.
        $this->assertStringContainsString('/temp/room_export', $storage->directoryFor($this->export()));
    }

    public function testTheFileNameFollowsFromTheRow(): void
    {
        self::bootKernel();

        $export = $this->export();
        $storage = self::getContainer()->get(RoomExportStorage::class);

        $this->assertSame(
            sprintf('room-%d-export-%d.zip', $export->getRoom()->getItemId(), $export->getId()),
            $storage->filenameFor($export)
        );
    }

    public function testAnExportWithoutAFileNameDoesNotExistYet(): void
    {
        self::bootKernel();

        $storage = self::getContainer()->get(RoomExportStorage::class);

        $this->assertFalse($storage->exists($this->export()));
    }

    public function testAFinishedExportWhoseFileWasSweptDoesNotExistEither(): void
    {
        self::bootKernel();

        $export = $this->export();
        $export->markFinished('room-1-export-1.zip', 10);

        $storage = self::getContainer()->get(RoomExportStorage::class);

        $this->assertFalse($storage->exists($export), 'the row says finished, the disk says gone');
    }

    public function testAnArchiveOnDiskIsFound(): void
    {
        self::bootKernel();

        $export = $this->export();
        $storage = self::getContainer()->get(RoomExportStorage::class);
        $export->markFinished($storage->filenameFor($export), 10);

        (new Filesystem())->dumpFile($storage->pathFor($export), 'not really a zip');

        try {
            $this->assertTrue($storage->exists($export));
        } finally {
            (new Filesystem())->remove($storage->directoryFor($export));
        }
    }

    private function export(): RoomExport
    {
        $export = new RoomExport($this->managedRoom(
            RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId()
        ));
        self::getContainer()->get(RoomExportRepository::class)->save($export);

        return $export;
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
