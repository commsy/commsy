<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150204211126 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE session');
        $this->addSql('CREATE TABLE sessions (sess_id VARBINARY(128) NOT NULL PRIMARY KEY, sess_data BLOB NOT NULL, sess_time INTEGER UNSIGNED NOT NULL, sess_lifetime MEDIUMINT NOT NULL) COLLATE utf8_bin, ENGINE = InnoDB;');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE sessions');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, session_id VARCHAR(150) NOT NULL COLLATE utf8_general_ci, session_key VARCHAR(30) NOT NULL COLLATE utf8_general_ci, session_value LONGTEXT NOT NULL COLLATE utf8_general_ci, created DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, INDEX session_id (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }
}
