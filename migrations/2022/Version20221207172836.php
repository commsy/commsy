<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221207172836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "find date items with orphaned calendar IDs and reset them to the ID of their room's standard calendar";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE dates AS d
            INNER JOIN calendars AS c
            ON d.context_id = c.context_id
            SET d.calendar_id = c.id
            WHERE
                d.deleter_id IS NULL
                AND d.deletion_date IS NULL
                AND c.default_calendar = 1
                AND d.calendar_id != c.id
                AND d.calendar_id NOT IN (
                    SELECT id
                    FROM calendars
                    WHERE context_id = d.context_id
                );
        ');
    }
}
