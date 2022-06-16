<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220426072549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add id column to accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY accounts_auth_source_id_fk');
        $this->addSql('ALTER TABLE accounts ADD id INT AUTO_INCREMENT NOT NULL FIRST, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE UNIQUE INDEX accounts_idx ON accounts (context_id, username, auth_source_id)');
        $this->addSql('DROP INDEX accounts_auth_source_id_fk ON accounts');
        $this->addSql('CREATE INDEX IDX_CAC89EAC91C3C0F3 ON accounts (auth_source_id)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT accounts_auth_source_id_fk FOREIGN KEY (auth_source_id) REFERENCES auth_source (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE accounts MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX accounts_idx ON accounts');
        $this->addSql('ALTER TABLE accounts DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY accounts_auth_source_id_fk');
        $this->addSql('ALTER TABLE accounts DROP id, CHANGE username username VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, CHANGE password_md5 password_md5 VARCHAR(32) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE password password VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE firstname firstname VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, CHANGE lastname lastname VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, CHANGE email email VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`, CHANGE language language VARCHAR(10) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('ALTER TABLE accounts ADD PRIMARY KEY (context_id, username, auth_source_id)');
        $this->addSql('DROP INDEX idx_cac89eac91c3c0f3 ON accounts');
        $this->addSql('CREATE INDEX accounts_auth_source_id_fk ON accounts (auth_source_id)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT accounts_auth_source_id_fk FOREIGN KEY (auth_source_id) REFERENCES auth_source (id)');
    }
}
