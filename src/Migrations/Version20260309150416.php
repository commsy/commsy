<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260309150416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix inconsistent draft status for recurring date items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE items
             SET draft = 0
             WHERE item_id IN (
                 SELECT d.item_id
                 FROM dates d
                 WHERE d.recurrence_id IS NOT NULL
                   AND d.recurrence_id IN (
                       SELECT d2.recurrence_id
                       FROM dates d2
                       INNER JOIN items i2 ON i2.item_id = d2.item_id
                       WHERE d2.recurrence_id IS NOT NULL
                         AND i2.draft = 0
                   )
             )'
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
