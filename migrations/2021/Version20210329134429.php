<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210329134429 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add lock column to accounts';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE accounts ADD locked TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX IF EXISTS auth_source ON user;');
        $this->addSql('DROP INDEX IF EXISTS context_id ON user;');
        $this->addSql('DROP INDEX IF EXISTS user_id ON user;');
        $this->addSql('CREATE INDEX IF NOT EXISTS user_context_id_user_id_auth_source_index ON user (context_id, user_id, auth_source);');


        $this->addSql('
            UPDATE
                accounts AS a
            INNER JOIN
                user AS u
            ON
                a.context_id = u.context_id AND
                a.username = u.user_id AND
                a.auth_source_id = u.auth_source
            SET
                a.locked = 1
            WHERE
                u.status = 0 AND
                u.deleter_id IS NULL AND
                u.deletion_date IS NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IF EXISTS user_context_id_user_id_auth_source_index ON user;');
        $this->addSql('CREATE INDEX IF NOT EXISTS user_id ON user (user_id);');
        $this->addSql('CREATE INDEX IF NOT EXISTS context_id ON user (context_id);');
        $this->addSql('CREATE INDEX IF NOT EXISTS auth_source ON user (auth_source);');

        $this->addSql('ALTER TABLE accounts DROP locked');
    }
}
