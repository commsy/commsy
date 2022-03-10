<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220222152603 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update auth_source table, removing server context, setting foreign key';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE auth_source SET portal_id = NULL WHERE portal_id = 99');

        $this->addSql('DROP INDEX auth_source_portal_id_index ON auth_source');
        $this->addSql('CREATE INDEX portal_id ON auth_source (portal_id)');
        $this->addSql('ALTER TABLE auth_source ADD CONSTRAINT FK_7F29D891B887E1DD FOREIGN KEY (portal_id) REFERENCES portal (id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth_source DROP FOREIGN KEY FK_7F29D891B887E1DD');
        $this->addSql('DROP INDEX portal_id ON auth_source');
        $this->addSql('CREATE INDEX auth_source_portal_id_index ON auth_source (portal_id)');
    }
}
