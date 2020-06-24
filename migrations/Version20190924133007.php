<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190924133007 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update auth table to accounts';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE auth modify email VARCHAR(100) NOT NULL after username;');

        // This will drop the primary key only if it exists
        $schemaManager = $this->connection->getSchemaManager();
        $authTableIndexes = $schemaManager->listTableIndexes('auth');
        if (!empty(array_filter($authTableIndexes, function (Index $index) {
            return $index->isPrimary();
        }))) {
            $this->addSql('ALTER TABLE auth DROP PRIMARY KEY;');
        }

        $this->addSql('RENAME TABLE auth TO accounts;');
        $this->addSql('ALTER TABLE accounts ADD auth_source_id INT NULL;');
        $this->addSql('
            UPDATE
                accounts
            SET
                accounts.auth_source_id =
                (
                    SELECT DISTINCT
                        u.auth_source
                    FROM
                        user AS u
                    WHERE
                        u.user_id = accounts.username AND
                        u.context_id = accounts.context_id AND
                        u.deleter_id IS NULL AND
                        u.deletion_date IS NULL AND
                        u.auth_source != 0
                )
        ');
//        $this->addSql('UPDATE accounts INNER JOIN `user` AS u ON accounts.username = u.user_id AND accounts.context_id = u.context_id SET accounts.auth_source_id = u.auth_source;');
        $this->addSql('DELETE FROM accounts WHERE accounts.auth_source_id IS NULL;');
        $this->addSql('ALTER TABLE accounts ADD PRIMARY KEY (context_id, username, auth_source_id);');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT accounts_auth_source_id_fk FOREIGN KEY (auth_source_id) REFERENCES auth_source (id);');

        $this->addSql('
            INSERT INTO
                accounts (context_id, username, email, firstname, lastname, language, auth_source_id)
            SELECT
                u.context_id,
                u.user_id,
                u.email,
                u.firstname,
                u.lastname,
                \'browser\',
                u.auth_source
            FROM
                user AS u
            INNER JOIN
                portal AS p
            ON
                u.context_id = p.item_id
            INNER JOIN
                auth_source
            ON
                u.auth_source = auth_source.id
            LEFT JOIN
                accounts AS a
            ON
                u.context_id = a.context_id AND
                u.user_id = a.username AND
                u.auth_source = a.auth_source_id
            WHERE
                u.deleter_id IS NULL AND
                u.deletion_date IS NULL AND
                a.auth_source_id IS NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
