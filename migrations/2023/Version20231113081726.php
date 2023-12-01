<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231113081726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop table noticed';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE noticed');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
