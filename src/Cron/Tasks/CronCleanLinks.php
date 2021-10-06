<?php

namespace App\Cron\Tasks;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class CronCleanLinks implements CronTaskInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function run(?DateTimeImmutable $lastRun): void
    {
        // Collect all links having non-existing items
        $conn = $this->entityManager->getConnection();
        $sql = '
            SELECT l.from_item_id, l.from_version_id, l.to_item_id, l.to_version_id FROM links l
            LEFT JOIN items i ON l.from_item_id = i.item_id
            WHERE i.item_id IS NULL
            UNION
            SELECT l.from_item_id, l.from_version_id, l.to_item_id, l.to_version_id FROM links l
            LEFT JOIN items i ON l.to_item_id = i.item_id
            WHERE i.item_id IS NULL
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAllAssociative();

        // Delete all or nothing
        foreach ($rows as $row) {
            $conn->executeStatement('DELETE FROM links WHERE from_item_id = ? AND from_version_id = ? AND to_item_id = ? AND to_version_id = ?', [$row['from_item_id'], $row['from_version_id'], $row['to_item_id'], $row['to_version_id']]);
        }
    }

    public function getSummary(): string
    {
        return 'Clean unneeded links';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}