<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210519150306 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update auth_source table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth_source ADD auth_query VARCHAR(50) DEFAULT NULL AFTER auth_dn, DROP extras, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE enabled enabled TINYINT(1) NOT NULL, CHANGE `default` `default` TINYINT(1) NOT NULL, CHANGE add_account add_account ENUM(\'yes\', \'no\', \'invitation\'), CHANGE change_username change_username TINYINT(1) NOT NULL, CHANGE delete_account delete_account TINYINT(1) NOT NULL, CHANGE change_userdata change_userdata TINYINT(1) NOT NULL, CHANGE change_password change_password TINYINT(1) NOT NULL, CHANGE create_room create_room TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth_source ADD extras MEDIUMTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, DROP auth_query, CHANGE description description TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE enabled enabled TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE `default` `default` TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE add_account add_account VARCHAR(255) CHARACTER SET utf8 DEFAULT \'no\' NOT NULL COLLATE `utf8_general_ci`, CHANGE change_username change_username TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE delete_account delete_account TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE change_userdata change_userdata TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE change_password change_password TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE create_room create_room TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE type type VARCHAR(255) CHARACTER SET utf8 DEFAULT \'local\' COLLATE `utf8_general_ci`');
    }
}
