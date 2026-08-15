<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Entity\Room;
use App\Entity\RoomExport;
use App\Repository\RoomExportRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class RoomExportRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testAPendingExportBlocksAnother(): void
    {
        self::bootKernel();
        $room = $this->room();

        $this->repository()->save(new RoomExport($room));

        $this->assertNotNull($this->repository()->findBlockingFor($room));
    }

    public function testASettledExportBlocksNothing(): void
    {
        self::bootKernel();
        $room = $this->room();

        $finished = new RoomExport($room);
        $finished->markFinished('room-1-export-1.zip', 10);
        $this->repository()->save($finished);

        $failed = new RoomExport($room);
        $failed->markFailed('nope');
        $this->repository()->save($failed);

        $this->assertNull($this->repository()->findBlockingFor($room));
    }

    public function testAnExportOfAnotherRoomBlocksNothingHere(): void
    {
        self::bootKernel();
        $room = $this->room();
        $otherRoom = $this->room();

        $this->repository()->save(new RoomExport($otherRoom));

        $this->assertNull($this->repository()->findBlockingFor($room));
    }

    /**
     * A worker that was killed mid-run leaves its row claiming to be running.
     * Without the cut-off that row would block its room from ever being
     * exported again.
     */
    public function testARunThatDiedWithItsWorkerStopsBlocking(): void
    {
        self::bootKernel();
        $room = $this->room();

        $stale = new RoomExport($room);
        $stale->markRunning();
        $this->repository()->save($stale);

        $this->backdate($stale, '-2 hours');

        $this->assertNull($this->repository()->findBlockingFor($room));
    }

    public function testARecentRunStillBlocks(): void
    {
        self::bootKernel();
        $room = $this->room();

        $recent = new RoomExport($room);
        $recent->markRunning();
        $this->repository()->save($recent);

        $this->backdate($recent, '-5 minutes');

        $this->assertNotNull($this->repository()->findBlockingFor($room));
    }

    public function testExportsOfARoomComeBackNewestFirst(): void
    {
        self::bootKernel();
        $room = $this->room();

        $older = new RoomExport($room);
        $this->repository()->save($older);
        $this->backdate($older, '-3 hours');

        $newer = new RoomExport($room);
        $this->repository()->save($newer);

        $found = $this->repository()->findForRoom($room);

        $this->assertCount(2, $found);
        $this->assertSame($newer->getId(), $found[0]->getId());
    }

    private function room(): Room
    {
        return $this->managedRoom(
            RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId()
        );
    }

    /**
     * requestedAt is set in the constructor and has no setter — the row is a
     * record, not a form. Ageing it for the test goes through SQL.
     */
    private function backdate(RoomExport $export, string $modifier): void
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);

        $manager->getConnection()->executeStatement(
            'UPDATE room_export SET requested_at = :requestedAt WHERE id = :id',
            [
                'requestedAt' => (new DateTimeImmutable($modifier))->format('Y-m-d H:i:s'),
                'id' => $export->getId(),
            ]
        );

        // Refresh just this row rather than clearing the manager: clearing would
        // detach the room too, and the next export built on it would look like a
        // new entity to Doctrine.
        $manager->refresh($export);
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
