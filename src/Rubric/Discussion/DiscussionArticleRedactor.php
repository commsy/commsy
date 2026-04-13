<?php

namespace App\Rubric\Discussion;

use App\Rubric\SubEntryRedactor;
use Doctrine\DBAL\Connection;

class DiscussionArticleRedactor implements SubEntryRedactor
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function redactContentOfUser(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            "UPDATE discussionarticles SET description = '' WHERE creator_id = :userId AND context_id = :contextId AND deleter_id IS NULL AND deletion_date IS NULL",
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }

    public function nullifyReferencesInContext(int $userId, int $contextId): void
    {
        $this->connection->executeStatement(
            'UPDATE discussionarticles SET creator_id = NULL WHERE creator_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );

        $this->connection->executeStatement(
            'UPDATE discussionarticles SET modifier_id = NULL WHERE modifier_id = :userId AND context_id = :contextId',
            ['userId' => $userId, 'contextId' => $contextId]
        );
    }
}
