<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221005161630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add slug to room & zzz_room';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room ADD slug VARCHAR(255) NULL;');
        $this->addSql('ALTER TABLE zzz_room ADD slug VARCHAR(255) NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP COLUMN slug');
        $this->addSql('ALTER TABLE zzz_room DROP COLUMN slug');
    }
}
