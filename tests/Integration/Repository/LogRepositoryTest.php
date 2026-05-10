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

use App\Repository\LogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class LogRepositoryTest extends KernelTestCase
{
    private LogRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(LogRepository::class);
        $this->connection = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getConnection();
    }

    public function testAddLogPersistsLogRow(): void
    {
        $contextId = 100_001;

        $this->repository->addLog(
            ip: '127.0.0.1',
            userAgent: 'phpunit',
            requestUri: '/room/123/material',
            postContent: '',
            method: 'GET',
            isAjax: false,
            username: 'tester',
            contextId: $contextId,
        );

        $row = $this->connection->fetchAssociative(
            'SELECT * FROM log WHERE cid = ? LIMIT 1',
            [$contextId],
        );
        self::assertNotEmpty($row);
        self::assertSame('127.0.0.1', $row['ip']);
        self::assertSame('GET', $row['method']);
    }

    public function testGetCountForContextCountsNonAjaxAndNonAssetRequests(): void
    {
        $contextId = 100_002;
        $this->insertLog($contextId, '/room/123/list', '2025-05-01 10:00:00');
        $this->insertLog($contextId, '/room/123/list', '2025-05-01 11:00:00');
        $this->insertLog($contextId, '/room/123/theme/background.css', '2025-05-01 12:00:00');
        $this->insertLog($contextId, '/room/123/list', '2025-05-01 13:00:00', ajax: 1);

        // Non-AJAX, non-asset paths only — distinct timestamps. Three
        // non-ajax rows but one is an asset; that leaves two timestamps.
        self::assertSame(2, $this->repository->getCountForContext($contextId));
    }

    public function testGetCountByContextAndDateSpanCountsRoomRequestsInWindow(): void
    {
        $contextId = 100_003;
        $this->insertLog($contextId, '/room/123/list', '2025-05-01 10:00:00', ulogin: 'a');
        $this->insertLog($contextId, '/room/123/list', '2025-05-01 11:00:00', ulogin: 'b');
        $this->insertLog($contextId, '/room/123/list', '2025-05-02 10:00:00', ulogin: 'a'); // outside window
        $this->insertLog($contextId, '/something-else',  '2025-05-01 11:00:00', ulogin: 'a'); // not /room/

        $lower = new DateTimeImmutable('2025-05-01 00:00:00');
        $upper = new DateTimeImmutable('2025-05-02 00:00:00');

        $row = $this->repository->getCountByContextAndDateSpan($contextId, $lower, $upper);

        self::assertSame(2, (int) $row['count']);
        self::assertSame(2, (int) $row['distinctUserCount']);
    }

    private function insertLog(
        int $cid,
        string $request,
        string $timestamp,
        int $ajax = 0,
        ?string $ulogin = 'tester',
    ): void {
        $this->connection->insert('log', [
            'cid' => $cid,
            'ip' => '127.0.0.1',
            'agent' => 'phpunit',
            'request' => $request,
            'method' => 'GET',
            'ajax' => $ajax,
            'ulogin' => $ulogin,
            'timestamp' => $timestamp,
        ]);
    }
}
