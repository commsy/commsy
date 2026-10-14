<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 */

declare(strict_types=1);

namespace Tests\Integration\Cron\Tasks;

use App\Cron\Tasks\CronCleanRoomExports;
use App\Cron\Tasks\CronCleanTemporary;
use App\Entity\Room;
use App\Entity\RoomExport;
use App\Repository\RoomExportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\PortalFactory;
use Tests\Factory\RoomFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * An export row names the person who asked for it. It goes with the archive,
 * not after it.
 */
class CronCleanRoomExportsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testARowGoesOnceItsArchiveIsGoneAtTheLatest(): void
    {
        self::bootKernel();
        $room = $this->room();

        $this->export($room, hoursAgo: CronCleanTemporary::LATEST_REMOVAL_HOURS + 1);
        $recent = $this->export($room, hoursAgo: CronCleanTemporary::LATEST_REMOVAL_HOURS - 1);

        $this->runTask();

        $this->assertSame([$recent->getId()], $this->remainingIds());
    }

    /**
     * How an export ended says nothing about how long its row is worth keeping.
     */
    public function testARowGoesWhateverBecameOfIt(): void
    {
        self::bootKernel();
        $room = $this->room();
        $beyond = CronCleanTemporary::LATEST_REMOVAL_HOURS + 1;

        $finished = $this->export($room, $beyond);
        $finished->markFinished('room-1-export-1.zip', 10);
        $failed = $this->export($room, $beyond);
        $failed->markFailed('it broke');
        $abandoned = $this->export($room, $beyond);
        $abandoned->markAbandoned();
        $this->save($finished, $failed, $abandoned);

        $this->runTask();

        $this->assertSame([], $this->remainingIds());
    }

    /**
     * It is housekeeping, not a room's own business.
     */
    public function testTheRowsOfEveryRoomAreSwept(): void
    {
        self::bootKernel();
        $beyond = CronCleanTemporary::LATEST_REMOVAL_HOURS + 1;

        $this->export($this->room(), $beyond);
        $this->export($this->room(), $beyond);

        $this->runTask();

        $this->assertSame([], $this->remainingIds());
    }

    public function testNothingToSweepIsNoTrouble(): void
    {
        self::bootKernel();

        $this->runTask();

        $this->assertSame([], $this->remainingIds());
    }

    private function runTask(): void
    {
        self::getContainer()->get(CronCleanRoomExports::class)->run(null);
        self::getContainer()->get(EntityManagerInterface::class)->clear();
    }

    /**
     * @return list<int>
     */
    private function remainingIds(): array
    {
        $ids = self::getContainer()->get(EntityManagerInterface::class)->getConnection()
            ->fetchFirstColumn('SELECT id FROM room_export ORDER BY id');

        return array_map('intval', $ids);
    }

    private function export(Room $room, int $hoursAgo): RoomExport
    {
        $export = new RoomExport($room);
        $this->save($export);

        // requestedAt is set in the constructor and has no setter — the row is a
        // record, not a form. Ageing it for the test goes through SQL.
        self::getContainer()->get(EntityManagerInterface::class)->getConnection()->executeStatement(
            'UPDATE room_export SET requested_at = :requestedAt WHERE id = :id',
            [
                'requestedAt' => (new \DateTimeImmutable("-{$hoursAgo} hours"))->format('Y-m-d H:i:s'),
                'id' => $export->getId(),
            ]
        );

        return $export;
    }

    private function save(RoomExport ...$exports): void
    {
        foreach ($exports as $export) {
            self::getContainer()->get(RoomExportRepository::class)->save($export);
        }
    }

    /**
     * RoomFactory writes its rows with raw SQL, so the object it returns is
     * detached; Doctrine needs the managed row.
     */
    private function room(): Room
    {
        return self::getContainer()->get(EntityManagerInterface::class)->find(
            Room::class,
            RoomFactory::createOne(['portal' => PortalFactory::createOne()])->getItemId()
        );
    }
}
