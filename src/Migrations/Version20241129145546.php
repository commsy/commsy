<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241129145546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set user_id column length to 100';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE user_id user_id VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
