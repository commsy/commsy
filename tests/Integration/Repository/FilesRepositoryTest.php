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

namespace Tests\Integration\Repository;

use App\Entity\Files;
use App\Repository\FilesRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Factory\FilesFactory;

final class FilesRepositoryTest extends KernelTestCase
{
    private FilesRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(FilesRepository::class);
    }

    public function testGetNumFilesReturnsOneForMatchingPair(): void
    {
        /** @var Files $file */
        $file = FilesFactory::createOne([
            'contextId' => 50_001,
        ]);

        $count = $this->repository->getNumFiles($file->getFilesId(), 50_001);

        self::assertSame(1, (int) $count);
    }

    public function testGetNumFilesReturnsZeroWhenContextDoesNotMatch(): void
    {
        $file = FilesFactory::createOne([
            'contextId' => 50_002,
        ]);

        self::assertSame(0, (int) $this->repository->getNumFiles($file->getFilesId(), 99_999));
    }

    public function testFindAllLockedBeforeReturnsRowsWithLockingDateBeforeThreshold(): void
    {
        $past = new DateTime('-1 day');
        $future = new DateTime('+1 day');

        $expired = FilesFactory::createOne([
            'contextId' => 50_003,
            'lockingDate' => $past,
        ]);
        $stillLocked = FilesFactory::createOne([
            'contextId' => 50_003,
            'lockingDate' => $future,
        ]);
        $unlocked = FilesFactory::createOne([
            'contextId' => 50_003,
            'lockingDate' => null,
        ]);

        $threshold = new DateTime('now');
        $results = $this->repository->findAllLockedBefore($threshold);
        $ids = array_map(static fn (Files $f): int => $f->getFilesId(), $results);

        self::assertContains($expired->getFilesId(), $ids);
        self::assertNotContains($stillLocked->getFilesId(), $ids);
        self::assertNotContains($unlocked->getFilesId(), $ids);
    }
}
