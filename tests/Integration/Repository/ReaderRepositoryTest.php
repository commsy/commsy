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

use App\Entity\Reader;
use App\Repository\ReaderRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReaderRepositoryTest extends KernelTestCase
{
    private ReaderRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ReaderRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testFindOneByItemIdAndUserIdReturnsTheNewestVersion(): void
    {
        $itemId = 91_001;
        $userId = 91_002;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        // Two reader rows for the same (item, user) — different versions.
        // The query orders by versionId DESC and limits to 1.
        $this->connection->insert('reader', [
            'item_id' => $itemId,
            'version_id' => 1,
            'user_id' => $userId,
            'read_date' => $now,
        ]);
        $this->connection->insert('reader', [
            'item_id' => $itemId,
            'version_id' => 2,
            'user_id' => $userId,
            'read_date' => $now,
        ]);

        $reader = $this->repository->findOneByItemIdAndUserId($itemId, $userId);

        self::assertInstanceOf(Reader::class, $reader);
        self::assertSame(2, $reader->getVersionId(), 'must return the newest version');
    }

    public function testFindOneByItemIdAndUserIdReturnsNullWhenNoMatch(): void
    {
        self::assertNull(
            $this->repository->findOneByItemIdAndUserId(999_991, 999_992),
        );
    }
}
