<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128124048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add column portal_id to user table';
    }

    public function up(Schema $schema): void
    {
        // Add portal_id column to user table
        $this->addSql('ALTER TABLE user ADD portal_id INT DEFAULT NULL AFTER context_id');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649B887E1DD FOREIGN KEY (portal_id) REFERENCES portal (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649B887E1DD ON user (portal_id)');

        // Set portal_id for entries already having a portal_id as context_id
        $this->addSql('UPDATE user u INNER JOIN portal p ON u.context_id = p.id SET u.portal_id = p.id WHERE u.portal_id IS NULL;');

        // Set portal_id for entries in other room contexts (we use the auth_source as a trick here)
        $this->addSql('UPDATE user u INNER JOIN auth_source a ON u.auth_source = a.id SET u.portal_id = a.portal_id WHERE u.portal_id IS NULL AND a.portal_id IS NOT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649B887E1DD');
        $this->addSql('DROP INDEX IDX_8D93D649B887E1DD ON user');
        $this->addSql('ALTER TABLE user DROP portal_id');
    }
}
