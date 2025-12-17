<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210311145311 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add logo to server';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE server ADD logo_image_name VARCHAR(255) NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE server DROP COLUMN logo_image_name');
    }
}
