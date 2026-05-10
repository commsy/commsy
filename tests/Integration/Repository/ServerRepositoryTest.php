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

use App\Entity\Server;
use App\Repository\ServerRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use TypeError;

/**
 * Repository contract for {@see ServerRepository::getServer()}: returns
 * the singleton server row (item_id=99) when it is not soft-deleted.
 * The test DB ships with this row pre-seeded — same as production,
 * where the server singleton is created by an installation migration.
 *
 * The deleter / deletion_date filter is the seam that broke silently
 * during the EntityUsersTrait rollout (Server.deleterId stopped existing
 * but the DQL still referenced `s.deleterId IS NULL`); this test pins
 * the filter so a future field-rename can't slip through unnoticed.
 *
 * NOTE: getServer() declares a non-nullable {@see Server} return type
 * but its body uses `getOneOrNullResult()` and can return null when the
 * server is soft-deleted — that mismatch raises a TypeError. Every
 * production caller currently assumes the singleton exists, so this
 * "soft-deleted" path is effectively a no-op in production. The tests
 * pin the *current* TypeError behavior; if/when the return type is
 * fixed to `?Server`, these tests should switch to assertNull().
 */
final class ServerRepositoryTest extends KernelTestCase
{
    private ServerRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(ServerRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testReturnsTheSingletonWhenActive(): void
    {
        $server = $this->repository->getServer();

        self::assertInstanceOf(Server::class, $server);
        self::assertSame(99, $server->getId());
    }

    public function testFiltersOutSoftDeletedServer(): void
    {
        // Soft-delete via raw DBAL — the entity has a generated PK we
        // can't manipulate via Doctrine.
        $this->connection->update(
            'server',
            [
                'deletion_date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'deleter_id' => 1,
            ],
            ['item_id' => 99],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        // No matching row → getOneOrNullResult returns null → return-type
        // violation. Pinning the current behavior; flip to assertNull
        // once the return type gets relaxed to ?Server.
        $this->expectException(TypeError::class);
        $this->repository->getServer();
    }

    public function testFiltersOutWhenOnlyDeletionDateIsSet(): void
    {
        $this->connection->update(
            'server',
            ['deletion_date' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
            ['item_id' => 99],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $this->expectException(TypeError::class);
        $this->repository->getServer();
    }

    public function testFiltersOutWhenOnlyDeleterIsSet(): void
    {
        $this->connection->update(
            'server',
            ['deleter_id' => 1],
            ['item_id' => 99],
        );
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        $this->expectException(TypeError::class);
        $this->repository->getServer();
    }
}
