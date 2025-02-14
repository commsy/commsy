<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250130131207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add display_name to accounts table and displayname_mapping to auth_source table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts ADD display_name VARCHAR(100) DEFAULT NULL AFTER username');
        $this->addSql('ALTER TABLE auth_source ADD displayname_mapping VARCHAR(100) DEFAULT NULL AFTER username_mapping, CHANGE add_account add_account VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auth_source DROP displayname_mapping, CHANGE add_account add_account VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE accounts DROP display_name');
    }
}
