<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251211153554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notify_community_mod_for_all_project_rooms column to portal table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal ADD notify_community_mod_for_all_project_rooms TINYINT(1) DEFAULT \'1\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal DROP notify_community_mod_for_all_project_rooms');
    }
}
