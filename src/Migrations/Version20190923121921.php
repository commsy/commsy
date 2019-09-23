<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190923121921 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add table for sessions';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            CREATE TABLE `sessions` (
                `sess_id` VARCHAR(128) NOT NULL PRIMARY KEY,
                `sess_data` BLOB NOT NULL,
                `sess_time` INTEGER UNSIGNED NOT NULL,
                `sess_lifetime` MEDIUMINT NOT NULL
            ) COLLATE utf8mb4_bin, ENGINE = InnoDB;
        ');
        $this->addSql('DROP TABLE session');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('
            CREATE table session (
                id int auto_increment primary key,
                session_id    varchar(150)                           not null,
                session_key   varchar(30)                            not null,
                session_value longtext                               not null,
                created       datetime default \'0000-00-00 00:00:00\' not null
            ) charset = utf8;
        ');
        $this->addSql('create index session_id on session (session_id);');
        $this->addSql('DROP TABLE sessions');
    }
}
