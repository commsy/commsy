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
use Doctrine\ORM\EntityManagerInterface;

class CronCleanLinks implements CronTaskInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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
        $result = $stmt->executeQuery();
        $rows = $result->fetchAllAssociative();

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
