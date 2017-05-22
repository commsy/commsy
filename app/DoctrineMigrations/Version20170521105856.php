<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170521105856 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX status_2 ON room');
        $this->addSql('DROP INDEX status_2 ON zzz_room');
        $this->addSql('DROP INDEX room_description ON room');
        $this->addSql('DROP INDEX room_description ON zzz_room');

        foreach ($schema->getTables() as $tableName => $table) {
            $this->addSql('ALTER TABLE ' . $tableName . ' ENGINE=InnoDB');
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        foreach ($schema->getTables() as $tableName => $table) {
            $this->addSql('ALTER TABLE ' . $tableName . ' ENGINE=MyISAM');
        }

        $this->addSql('CREATE INDEX status_2 ON room(status)');
        $this->addSql('CREATE INDEX status_2 ON zzz_room(status)');
        $this->addSql('CREATE INDEX room_description ON room(room_description)');
        $this->addSql('CREATE INDEX room_description ON zzz_room(room_description)');
    }
}
