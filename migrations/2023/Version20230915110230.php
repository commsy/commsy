<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230915110230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pinned column to items table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items ADD pinned TINYINT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items DROP COLUMN pinned');
    }
}
