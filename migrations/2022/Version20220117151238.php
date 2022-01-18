<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220117151238 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add identity_provider column to auth_source';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth_source ADD identity_provider LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth_source DROP identity_provider CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`');
    }
}
