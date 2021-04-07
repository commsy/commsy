<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210406133819 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'drop is_open_for_guests from portal table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table portal drop column is_open_for_guests;');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('alter table portal add is_open_for_guests tinyint default 1 null after activity;');
    }
}
