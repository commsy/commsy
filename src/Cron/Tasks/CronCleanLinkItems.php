<?php

namespace App\Cron\Tasks;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class CronCleanLinkItems implements CronTaskInterface
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
            SELECT l.item_id FROM link_items l
            LEFT JOIN items i ON l.first_item_id = i.item_id
            WHERE i.item_id IS NULL
            UNION
            SELECT l.item_id FROM link_items l
            LEFT JOIN items i ON l.second_item_id = i.item_id
            WHERE i.item_id IS NULL
        ';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAllAssociative();

        // Delete all or nothing
        $conn->transactional(function (Connection $conn) use ($rows) {
            foreach ($rows as $row) {
                $conn->executeStatement('DELETE FROM link_items WHERE item_id = ?', [$row['item_id']]);
                $conn->executeStatement('DELETE FROM items WHERE item_id = ?', [$row['item_id']]);
            }
        });
    }

    public function getSummary(): string
    {
        return 'Clean unneeded link items';
    }

    public function getPriority(): int
    {
        return self::PRIORITY_NORMAL;
    }
}