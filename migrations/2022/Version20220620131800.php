<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220620131800 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table external_viewer add deleter_id int(11) DEFAULT NULL;');
        $this->addSql('alter table external_viewer add deletion_date datetime DEFAULT NULL;');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table external_viewer DROP COLUMN deleter_id;');
        $this->addSql('alter table external_viewer DROP COLUMN deletion_date;');
    }
}
