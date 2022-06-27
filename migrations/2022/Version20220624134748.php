<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220624134748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description field to discussions table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('alter table discussions add description text null after title;');
        $this->addSql('alter table zzz_discussions add description text null after title;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussions DROP COLUMN description');
        $this->addSql('ALTER TABLE zzz_discussions DROP COLUMN description');
    }
}
