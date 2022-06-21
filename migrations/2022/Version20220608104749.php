<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220608104749 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table discussions add description text null after title;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discussions DROP COLUMN description');
    }
}