<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190923152100 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Migrate authentication sources';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE auth_source CHANGE item_id id int AUTO_INCREMENT;
            ALTER TABLE auth_source CHANGE context_id portal_id int NULL;
            ALTER TABLE auth_source DROP COLUMN modifier_id;
            ALTER TABLE auth_source DROP COLUMN deleter_id;
            ALTER TABLE auth_source DROP COLUMN creation_date;
            ALTER TABLE auth_source DROP COLUMN modification_date;
            ALTER TABLE auth_source DROP COLUMN deletion_date;
            DROP INDEX creator_id ON auth_source;
            ALTER TABLE auth_source DROP COLUMN creator_id;
            DROP index context_id ON auth_source;
            CREATE index auth_source_portal_id_index ON auth_source (portal_id);
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
