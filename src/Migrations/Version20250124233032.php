<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250124233032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert Version0000 migration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT IGNORE INTO migration_versions(version) VALUES("DoctrineMigrations\\\Version00000000000001")');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
