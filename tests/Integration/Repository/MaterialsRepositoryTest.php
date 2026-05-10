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

use App\Entity\Materials;
use App\Repository\MaterialsRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MaterialsRepositoryTest extends KernelTestCase
{
    private MaterialsRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(MaterialsRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testFindLatestVersionByItemIdReturnsTheHighestVersionRow(): void
    {
        $itemId = $this->makeMaterialItem(110_001);

        $this->insertVersion($itemId, versionId: 1, title: 'v1');
        $this->insertVersion($itemId, versionId: 2, title: 'v2');
        $this->insertVersion($itemId, versionId: 3, title: 'v3');

        $latest = $this->repository->findLatestVersionByItemId($itemId);

        self::assertInstanceOf(Materials::class, $latest);
        self::assertSame(3, $latest->getVersionId());
        self::assertSame('v3', $latest->getTitle());
    }

    public function testFindLatestVersionByItemIdReturnsNullForUnknownItem(): void
    {
        self::assertNull($this->repository->findLatestVersionByItemId(999_999));
    }

    public function testCreateSearchQueryBuilderReturnsBuilderRestrictedToLatestVersion(): void
    {
        $qb = $this->repository->createSearchQueryBuilder();

        self::assertInstanceOf(QueryBuilder::class, $qb);
        self::assertStringContainsString('LEFT JOIN', $qb->getQuery()->getSQL());
    }

    public function testCreateSearchHydrationQueryBuilderHonoursAlias(): void
    {
        $qb = $this->repository->createSearchHydrationQueryBuilder('mtrl');

        self::assertInstanceOf(QueryBuilder::class, $qb);
        // Doctrine ORM mangles the alias into m0_/m1_ at the SQL level,
        // so we can't assert on the SQL alias. Instead verify that the
        // QueryBuilder uses our alias in its DQL root and that the
        // greatest-n-per-group self-join is in place.
        self::assertSame(['mtrl'], $qb->getRootAliases());
        self::assertStringContainsString('LEFT JOIN', $qb->getQuery()->getSQL());
    }

    private function makeMaterialItem(int $contextId): int
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->insert('items', [
            'context_id' => $contextId,
            'type' => 'material',
            'modification_date' => $now,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    private function insertVersion(int $itemId, int $versionId, string $title): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->connection->insert('materials', [
            'item_id' => $itemId,
            'version_id' => $versionId,
            'title' => $title,
            'creation_date' => $now,
            'modification_date' => $now,
            'public' => 1,
            'world_public' => 0,
            'new_hack' => 0,
            'workflow_status' => '3_none',
        ]);
    }
}
