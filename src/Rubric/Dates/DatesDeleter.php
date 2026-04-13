<?php

namespace App\Rubric\Dates;

use App\Rubric\RubricDeleter;
use App\Services\LegacyEnvironment;
use cs_item;
use Doctrine\DBAL\Connection;

class DatesDeleter implements RubricDeleter
{
    public function __construct(
        private LegacyEnvironment $legacyEnvironment,
        private Connection $connection,
    ) {}

    public function rubricKey(): string
    {
        return 'date';
    }

    public function findItemsCreatedBy(int $userId, int $contextId): iterable
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM dates WHERE creator_id = :userId AND context_id = :contextId AND deleter_id IS NULL AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $manager = $this->legacyEnvironment->getEnvironment()->getDatesManager();
        foreach ($itemIds as $itemId) {
            $item = $manager->getItem((int) $itemId);
            if ($item !== null) {
                yield $item;
            }
        }
    }

    public function deleteItem(cs_item $item): void
    {
        $item->delete();
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE dates SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE dates SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
