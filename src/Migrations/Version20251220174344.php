<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220174344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sets context_id to NULL for root user in accounts and user table to allow relation mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX accounts_idx ON accounts');
        $this->addSql('ALTER TABLE accounts CHANGE context_id portal_id INT DEFAULT NULL');

        $this->addSql("UPDATE accounts SET portal_id = NULL WHERE username = 'root'");

        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EAC6B00C1CF FOREIGN KEY (portal_id) REFERENCES portal (id)');
        $this->addSql('CREATE INDEX IDX_CAC89EAC6B00C1CF ON accounts (portal_id)');
        $this->addSql('CREATE UNIQUE INDEX accounts_idx ON accounts (portal_id, username, auth_source_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX accounts_idx ON accounts');
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_CAC89EAC6B00C1CF');
        $this->addSql('DROP INDEX IDX_CAC89EAC6B00C1CF ON accounts');

        $this->addSql("UPDATE accounts SET portal_id = 99 WHERE username = 'root'");

        $this->addSql('ALTER TABLE accounts CHANGE portal_id context_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX accounts_idx ON accounts (context_id, username, auth_source_id)');
    }
}
