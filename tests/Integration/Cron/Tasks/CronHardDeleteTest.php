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

namespace Tests\Integration\Cron\Tasks;

use App\Assessment\AssessmentDeleter;
use App\Cron\Tasks\CronHardDelete;
use App\Files\FileDeleter;
use App\Legacy\LegacyAuxHardDeleter;
use App\Room\RoomHardDeleter;
use App\Rubric\RubricHardDeleter;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * End-to-end smoke test for {@see CronHardDelete::run()}: pins that the
 * composition wires up correctly and sweeps every expected aux table. A
 * local ParameterBag overrides `commsy.settings.delete_days` so the fixture
 * can stay near NOW() without fighting the production grace window.
 */
final class CronHardDeleteTest extends KernelTestCase
{
    private Connection $connection;
    private CronHardDelete $cron;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->connection = $container->get(EntityManagerInterface::class)->getConnection();

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->with('commsy.settings.delete_days')->willReturn(30);

        $this->cron = new CronHardDelete(
            $parameterBag,
            $container->get(RoomHardDeleter::class),
            $container->get(RubricHardDeleter::class),
            $container->get(LegacyAuxHardDeleter::class),
            $container->get(FileDeleter::class),
            $container->get(AssessmentDeleter::class),
        );
    }

    public function testRunPhysicallyRemovesExpiredRowsAcrossAuxTables(): void
    {
        // Expired (past cutoff) — must go physically:
        $expiredAssessment = $this->insertAuxItem('assessment', -40);
        $this->insertAssessmentRow($expiredAssessment, -40);

        $expiredLinkItemId = $this->insertAuxItem('link_item', -40);
        $this->insertLinkItemRow($expiredLinkItemId, -40);

        $expiredTag = $this->insertAuxItem('tag', -40);
        $this->insertTagRow($expiredTag, -40);

        $expiredPivotLinkId = $this->insertTag2TagPivot(-40);

        $expiredTaskId = $this->insertAuxItem('task', -40);
        $this->insertTaskRow($expiredTaskId, -40);

        // Recent soft-delete (inside grace window) — must survive:
        $recentAssessment = $this->insertAuxItem('assessment', -5);
        $this->insertAssessmentRow($recentAssessment, -5);

        // Alive row — must never be touched:
        $aliveAssessment = $this->insertAuxItem('assessment', null);
        $this->insertAssessmentRow($aliveAssessment, null);

        $this->cron->run(null);

        // Expired rows physically gone:
        self::assertFalse($this->rowExists('assessments', 'item_id', $expiredAssessment));
        self::assertFalse($this->rowExists('link_items', 'item_id', $expiredLinkItemId));
        self::assertFalse($this->rowExists('tag', 'item_id', $expiredTag));
        self::assertFalse($this->rowExists('tag2tag', 'link_id', $expiredPivotLinkId));
        self::assertFalse($this->rowExists('tasks', 'item_id', $expiredTaskId));
        self::assertFalse(
            $this->rowExists('items', 'item_id', $expiredAssessment),
            'items twin of expired assessment must also be swept by hardDeleteItemsRows'
        );
        self::assertFalse($this->rowExists('items', 'item_id', $expiredLinkItemId));
        self::assertFalse($this->rowExists('items', 'item_id', $expiredTag));
        self::assertFalse($this->rowExists('items', 'item_id', $expiredTaskId));

        // Recent soft-delete survives:
        self::assertTrue($this->rowExists('assessments', 'item_id', $recentAssessment));

        // Alive row survives:
        self::assertTrue($this->rowExists('assessments', 'item_id', $aliveAssessment));
        self::assertTrue($this->rowExists('items', 'item_id', $aliveAssessment));
    }

    public function testRunEarlyReturnsWhenDeleteDaysIsMisconfigured(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->with('commsy.settings.delete_days')->willReturn('');

        $container = self::getContainer();
        $cron = new CronHardDelete(
            $parameterBag,
            $container->get(RoomHardDeleter::class),
            $container->get(RubricHardDeleter::class),
            $container->get(LegacyAuxHardDeleter::class),
            $container->get(FileDeleter::class),
            $container->get(AssessmentDeleter::class),
        );

        $expiredTag = $this->insertAuxItem('tag', -40);
        $this->insertTagRow($expiredTag, -40);

        $cron->run(null);

        self::assertTrue(
            $this->rowExists('tag', 'item_id', $expiredTag),
            'misconfigured delete_days must early-return before any sweep'
        );
    }

    // ------------------------------------------------------------------

    private function insertAuxItem(string $type, ?int $daysAgo): int
    {
        if ($daysAgo === null) {
            $this->connection->executeStatement(
                'INSERT INTO items (type, modification_date) VALUES (:type, NOW())',
                ['type' => $type]
            );
        } else {
            $this->connection->executeStatement(
                sprintf(
                    "INSERT INTO items (type, modification_date, deletion_date, deleter_id)
                        VALUES (:type, NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0)",
                    $daysAgo
                ),
                ['type' => $type]
            );
        }

        return (int) $this->connection->lastInsertId();
    }

    private function insertAssessmentRow(int $itemId, ?int $daysAgo): void
    {
        if ($daysAgo === null) {
            $this->connection->executeStatement(
                'INSERT INTO assessments (item_id, creator_id, creation_date, item_link_id, assessment)
                    VALUES (:itemId, 0, NOW(), 1, 5)',
                ['itemId' => $itemId]
            );
        } else {
            $this->connection->executeStatement(
                sprintf(
                    "INSERT INTO assessments (item_id, creator_id, creation_date, deletion_date, deleter_id, item_link_id, assessment)
                        VALUES (:itemId, 0, NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, 1, 5)",
                    $daysAgo
                ),
                ['itemId' => $itemId]
            );
        }
    }

    private function insertLinkItemRow(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO link_items (item_id, creation_date, deletion_date, deleter_id, first_item_id, second_item_id)
                    VALUES (:itemId, NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, 1, 2)",
                $daysAgo
            ),
            ['itemId' => $itemId]
        );
    }

    private function insertTagRow(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO tag (item_id, creation_date, modification_date, deletion_date, deleter_id, title)
                    VALUES (:itemId, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, :title)",
                $daysAgo
            ),
            ['itemId' => $itemId, 'title' => 'tag-' . $itemId]
        );
    }

    private function insertTag2TagPivot(int $daysAgo): int
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO tag2tag (from_item_id, to_item_id, creation_date, modification_date, deletion_date, deleter_id)
                    VALUES (1, 2, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0)",
                $daysAgo
            )
        );

        return (int) $this->connection->lastInsertId();
    }

    private function insertTaskRow(int $itemId, int $daysAgo): void
    {
        $this->connection->executeStatement(
            sprintf(
                "INSERT INTO tasks (item_id, creation_date, modification_date, deletion_date, deleter_id, title, status, linked_item_id)
                    VALUES (:itemId, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL %d DAY), 0, :title, 'OPEN', 0)",
                $daysAgo
            ),
            ['itemId' => $itemId, 'title' => 'task-' . $itemId]
        );
    }

    private function rowExists(string $table, string $pkColumn, int $id): bool
    {
        return (bool) $this->connection->fetchOne(
            sprintf('SELECT 1 FROM %s WHERE %s = :id', $table, $pkColumn),
            ['id' => $id]
        );
    }
}
