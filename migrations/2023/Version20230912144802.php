<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230912144802 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Delete empty room slugs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM room_slug WHERE slug = "";');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
