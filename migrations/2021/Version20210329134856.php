<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210329134856 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Removes any user entries with a non-existing auth source';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('DELETE user FROM user LEFT JOIN auth_source ON user.auth_source = auth_source.id WHERE auth_source.id IS NULL;');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
