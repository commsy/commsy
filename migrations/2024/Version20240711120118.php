<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240711120118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account_settings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account_settings (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, name VARCHAR(255) NOT NULL, value JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_9D8B42739B6B5FBA (account_id), UNIQUE INDEX unique_account_name (account_id, name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account_settings ADD CONSTRAINT FK_9D8B42739B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_settings DROP FOREIGN KEY FK_9D8B42739B6B5FBA');
        $this->addSql('DROP TABLE account_settings');
    }
}
