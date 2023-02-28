<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230224143922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase max request and param length for log and log_archive table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log MODIFY request VARCHAR(2500) NULL');
        $this->addSql('ALTER TABLE log MODIFY param VARCHAR(2500) NULL');
        $this->addSql('ALTER TABLE log_archive MODIFY request VARCHAR(2500) NULL');
        $this->addSql('ALTER TABLE log_archive MODIFY param VARCHAR(2500) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE log MODIFY request VARCHAR(250) NULL');
        $this->addSql('ALTER TABLE log MODIFY param VARCHAR(250) NULL');
        $this->addSql('ALTER TABLE log_archive MODIFY request VARCHAR(250) NULL');
        $this->addSql('ALTER TABLE log_archive MODIFY param VARCHAR(250) NULL');
    }
}
