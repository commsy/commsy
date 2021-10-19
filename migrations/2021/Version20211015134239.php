<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015134239 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Updating account and user tables (inactivity)';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY accounts_auth_source_id_fk');
        $this->addSql('ALTER TABLE accounts ADD activity VARCHAR(10) DEFAULT "active" NOT NULL, ADD last_login DATETIME DEFAULT NULL, CHANGE lastname lastname VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX accounts_auth_source_id_fk ON accounts');
        $this->addSql('CREATE INDEX IDX_CAC89EAC91C3C0F3 ON accounts (auth_source_id)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT accounts_auth_source_id_fk FOREIGN KEY (auth_source_id) REFERENCES auth_source (id)');

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
                a.last_login = u.lastlogin
            WHERE
                u.deleter_id IS NULL AND
                u.deletion_date IS NULL
        ');

        $this->addSql('
            UPDATE
                user AS u
            INNER JOIN
                portal AS p
            ON
                u.context_id = p.id
            SET
                u.lastlogin = NULL
            WHERE
                u.deleter_id IS NULL AND
                u.deletion_date IS NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_CAC89EAC91C3C0F3');
        $this->addSql('ALTER TABLE accounts DROP activity, DROP last_login, CHANGE lastname lastname VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('DROP INDEX idx_cac89eac91c3c0f3 ON accounts');
        $this->addSql('CREATE INDEX accounts_auth_source_id_fk ON accounts (auth_source_id)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EAC91C3C0F3 FOREIGN KEY (auth_source_id) REFERENCES auth_source (id)');
    }

    public function postUp(Schema $schema): void
    {
        $this->write('removing inactivity information from extras');
        $this->removeInactivityExtras('user');
    }

    private function removeInactivityExtras(string $table)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('t.item_id', 't.extras')
            ->from($table, 't')
            ->where('t.extras lIKE "%MAIL_SEND_LOCK%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_LOCKED%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_DELETE%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_NEXT_DELETE%"')
            ->orWhere('t.extras lIKE "%LOCK_SEND_MAIL_DATE%"')
            ->orWhere('t.extras lIKE "%LOCK%"')
            ->orWhere('t.extras lIKE "%NOTIFY_LOCK_DATE%"')
            ->orWhere('t.extras lIKE "%NOTIFY_DELETE_DATE%"');
        $entries = $qb->execute();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            $keys = ['MAIL_SEND_LOCK', 'MAIL_SEND_LOCKED', 'MAIL_SEND_DELETE', 'MAIL_SEND_NEXT_DELETE',
                'LOCK_SEND_MAIL_DATE', 'LOCK', 'NOTIFY_LOCK_DATE', 'NOTIFY_DELETE_DATE'];

            foreach ($keys as $key) {
                if (isset($extras[$key])) {
                    unset ($extras[$key]);
                }
            }

            $this->connection->update($table, [
                'extras' => serialize($extras),
            ], [
                'item_id' => $entry['item_id'],
            ]);
        }
    }
}
