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

namespace App\Cron\Tasks;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class CronCleanLinkItems implements CronTaskInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
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
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();

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
