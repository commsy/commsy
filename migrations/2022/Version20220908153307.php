<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220908153307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add default filter value settings for workspace list in portal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal ADD default_filter_hide_templates TINYINT(1) DEFAULT 0 NOT NULL, ADD default_filter_hide_archived TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal DROP default_filter_hide_templates, DROP default_filter_hide_archived');
    }
}
