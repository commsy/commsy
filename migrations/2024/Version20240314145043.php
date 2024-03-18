<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240314145043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add on delete cascade to lock table foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `lock` DROP FOREIGN KEY FK_878F9B0E9B6B5FBA');
        $this->addSql('ALTER TABLE `lock` ADD CONSTRAINT FK_878F9B0E9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `lock` DROP FOREIGN KEY FK_878F9B0E9B6B5FBA');
        $this->addSql('ALTER TABLE `lock` ADD CONSTRAINT FK_878F9B0E9B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
    }
}
