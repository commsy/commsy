<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230331074911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `lock` (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, item_id INT NOT NULL, token VARCHAR(255) NOT NULL, lock_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_878F9B0E9B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `lock` ADD CONSTRAINT FK_878F9B0E9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_878F9B0E126F525E ON `lock` (item_id)');

        $this->addSql('ALTER TABLE announcement DROP locking_date, DROP locking_user_id');
        $this->addSql('ALTER TABLE dates DROP locking_date, DROP locking_user_id');
        $this->addSql('ALTER TABLE discussions DROP locking_date, DROP locking_user_id');
        $this->addSql('ALTER TABLE labels DROP locking_date, DROP locking_user_id');
        $this->addSql('ALTER TABLE materials DROP locking_date, DROP locking_user_id');
        $this->addSql('ALTER TABLE todos DROP locking_date, DROP locking_user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_878F9B0E126F525E ON `lock`');
        $this->addSql('ALTER TABLE `lock` DROP FOREIGN KEY FK_878F9B0E9B6B5FBA');
        $this->addSql('DROP TABLE `lock`');

        $this->addSql('ALTER TABLE dates ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE todos ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussions ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE labels ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE materials ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE announcement ADD locking_date DATETIME DEFAULT NULL, ADD locking_user_id INT DEFAULT NULL');
    }
}
