<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230614081649 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make all filepath lowercase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE files SET filepath = LOWER(filepath)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
