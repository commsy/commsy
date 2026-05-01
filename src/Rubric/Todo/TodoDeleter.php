<?php

namespace App\Rubric\Todo;

use App\Rubric\RubricDeleter;
use App\Services\LegacyEnvironment;
use cs_item;
use Doctrine\DBAL\Connection;

class TodoDeleter implements RubricDeleter
{
    public function __construct(
        private readonly LegacyEnvironment $legacyEnvironment,
        private readonly Connection $connection,
    ) {}

    public function rubricKey(): string
    {
        return 'todo';
    }

    public function findItemsCreatedBy(int $userId, int $contextId): iterable
    {
        $itemIds = $this->connection->fetchFirstColumn(
            'SELECT item_id FROM todos WHERE creator_id = :userId AND context_id = :contextId AND deleter_id IS NULL AND deletion_date IS NULL',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $manager = $this->legacyEnvironment->getEnvironment()->getTodosManager();
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
            'UPDATE todos SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE todos SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
