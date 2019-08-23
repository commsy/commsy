<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190708172814 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add password column for bcrypt, make old password optional, update existing columns.';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth ADD password VARCHAR(255) NULL AFTER user_id');
        $this->addSql('ALTER TABLE auth MODIFY password_md5 VARCHAR(32) NULL');
        $this->addSql('ALTER TABLE auth CHANGE commsy_id context_id int NOT NULL');
        $this->addSql('ALTER TABLE auth CHANGE user_id username VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth DROP password');
        $this->addSql('ALTER TABLE auth MODIFY password_md5 VARCHAR(32)');
        $this->addSql('ALTER TABLE auth CHANGE context_id commsy_id int NOT NULL');
        $this->addSql('ALTER TABLE auth CHANGE username user_id VARCHAR(32) NOT NULL');
    }
}
