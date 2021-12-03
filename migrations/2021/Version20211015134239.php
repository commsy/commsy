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
        $this->addSql('ALTER TABLE accounts ADD activity_state VARCHAR(15) DEFAULT "active" NOT NULL, ADD activity_state_updated DATETIME DEFAULT NULL, ADD last_login DATETIME DEFAULT NULL, CHANGE lastname lastname VARCHAR(50) NOT NULL');
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

        $this->addSql('ALTER TABLE portal ADD clear_inactive_accounts_feature_enabled TINYINT(1) DEFAULT \'0\' NOT NULL, ADD clear_inactive_accounts_notify_lock_days SMALLINT DEFAULT 180 NOT NULL, ADD clear_inactive_accounts_lock_days SMALLINT DEFAULT 30 NOT NULL, ADD clear_inactive_accounts_notify_delete_days SMALLINT DEFAULT 180 NOT NULL, ADD clear_inactive_accounts_delete_days SMALLINT DEFAULT 30 NOT NULL, ADD clear_inactive_rooms_feature_enabled TINYINT(1) DEFAULT \'0\' NOT NULL, ADD clear_inactive_rooms_notify_lock_days SMALLINT DEFAULT 180 NOT NULL, ADD clear_inactive_rooms_lock_days SMALLINT DEFAULT 30 NOT NULL, ADD clear_inactive_rooms_notify_delete_days SMALLINT DEFAULT 180 NOT NULL, ADD clear_inactive_rooms_delete_days SMALLINT DEFAULT 30 NOT NULL');

        $this->addSql('DROP INDEX deletion_date ON room');
        $this->addSql('DROP INDEX contact_persons ON room');
        $this->addSql('ALTER TABLE room ADD activity_state VARCHAR(15) DEFAULT \'active\' NOT NULL, ADD activity_state_updated DATETIME DEFAULT NULL, CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE modification_date modification_date DATETIME NOT NULL, CHANGE extras extras LONGTEXT DEFAULT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE continuous continuous SMALLINT DEFAULT -1 NOT NULL, CHANGE template template SMALLINT DEFAULT -1 NOT NULL');
        $this->addSql('CREATE INDEX delete_idx ON room (deleter_id, deletion_date)');
        $this->addSql('CREATE INDEX search_idx ON room (title, contact_persons)');
        $this->addSql('DROP INDEX creator_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519B61220EA6 ON room (creator_id)');
        $this->addSql('DROP INDEX modifier_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519BD079F553 ON room (modifier_id)');
        $this->addSql('DROP INDEX deleter_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519BEAEF1DFE ON room (deleter_id)');

        $this->addSql('DROP INDEX contact_persons ON zzz_room');
        $this->addSql('ALTER TABLE zzz_room ADD activity_state VARCHAR(15) DEFAULT \'active\' NOT NULL, ADD activity_state_updated DATETIME DEFAULT NULL, CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE modification_date modification_date DATETIME NOT NULL, CHANGE extras extras LONGTEXT DEFAULT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE continuous continuous SMALLINT DEFAULT -1 NOT NULL, CHANGE template template SMALLINT DEFAULT -1 NOT NULL');
        $this->addSql('CREATE INDEX IDX_538B256EEAEF1DFE ON zzz_room (deleter_id)');
        $this->addSql('CREATE INDEX delete_idx ON zzz_room (deleter_id, deletion_date)');
        $this->addSql('CREATE INDEX search_idx ON zzz_room (title, contact_persons)');
        $this->addSql('DROP INDEX creator_id ON zzz_room');
        $this->addSql('CREATE INDEX IDX_538B256E61220EA6 ON zzz_room (creator_id)');
        $this->addSql('DROP INDEX modifier_id ON zzz_room');
        $this->addSql('CREATE INDEX IDX_538B256ED079F553 ON zzz_room (modifier_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE accounts DROP FOREIGN KEY FK_CAC89EAC91C3C0F3');
        $this->addSql('ALTER TABLE accounts DROP activity_state, DROP activity_state_updated, DROP last_login, CHANGE lastname lastname VARCHAR(100) CHARACTER SET utf8 NOT NULL COLLATE `utf8_general_ci`');
        $this->addSql('DROP INDEX idx_cac89eac91c3c0f3 ON accounts');
        $this->addSql('CREATE INDEX accounts_auth_source_id_fk ON accounts (auth_source_id)');
        $this->addSql('ALTER TABLE accounts ADD CONSTRAINT FK_CAC89EAC91C3C0F3 FOREIGN KEY (auth_source_id) REFERENCES auth_source (id)');

        $this->addSql('ALTER TABLE portal DROP clear_inactive_accounts_feature_enabled, DROP clear_inactive_accounts_notify_lock_days, DROP clear_inactive_accounts_lock_days, DROP clear_inactive_accounts_notify_delete_days, DROP clear_inactive_accounts_delete_days, DROP clear_inactive_rooms_feature_enabled, DROP clear_inactive_rooms_notify_lock_days, DROP clear_inactive_rooms_lock_days, DROP clear_inactive_rooms_notify_delete_days, DROP clear_inactive_rooms_delete_days');

        $this->addSql('DROP INDEX delete_idx ON room');
        $this->addSql('DROP INDEX search_idx ON room');
        $this->addSql('ALTER TABLE room DROP activity_state, DROP activity_state_updated, CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE creation_date creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE modification_date modification_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE extras extras MEDIUMTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE type type VARCHAR(20) CHARACTER SET utf8 DEFAULT \'project\' NOT NULL COLLATE `utf8_general_ci`, CHANGE continuous continuous TINYINT(1) DEFAULT \'-1\' NOT NULL, CHANGE template template TINYINT(1) DEFAULT \'-1\' NOT NULL');
        $this->addSql('CREATE INDEX deletion_date ON room (deletion_date)');
        $this->addSql('CREATE INDEX contact_persons ON room (contact_persons)');
        $this->addSql('DROP INDEX idx_729f519bd079f553 ON room');
        $this->addSql('CREATE INDEX modifier_id ON room (modifier_id)');
        $this->addSql('DROP INDEX idx_729f519beaef1dfe ON room');
        $this->addSql('CREATE INDEX deleter_id ON room (deleter_id)');
        $this->addSql('DROP INDEX idx_729f519b61220ea6 ON room');
        $this->addSql('CREATE INDEX creator_id ON room (creator_id)');

        $this->addSql('DROP INDEX IDX_538B256EEAEF1DFE ON zzz_room');
        $this->addSql('DROP INDEX delete_idx ON zzz_room');
        $this->addSql('DROP INDEX search_idx ON zzz_room');
        $this->addSql('ALTER TABLE zzz_room DROP activity_state, DROP activity_state_updated, CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE creation_date creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE modification_date modification_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE extras extras MEDIUMTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_general_ci`, CHANGE type type VARCHAR(20) CHARACTER SET utf8 DEFAULT \'project\' NOT NULL COLLATE `utf8_general_ci`, CHANGE continuous continuous TINYINT(1) DEFAULT \'-1\' NOT NULL, CHANGE template template TINYINT(1) DEFAULT \'-1\' NOT NULL');
        $this->addSql('CREATE INDEX contact_persons ON zzz_room (contact_persons)');
        $this->addSql('DROP INDEX idx_538b256ed079f553 ON zzz_room');
        $this->addSql('CREATE INDEX modifier_id ON zzz_room (modifier_id)');
        $this->addSql('DROP INDEX idx_538b256e61220ea6 ON zzz_room');
        $this->addSql('CREATE INDEX creator_id ON zzz_room (creator_id)');
    }

    public function postUp(Schema $schema): void
    {
        $this->write('removing inactivity information from extras');
        $this->removeInactivityExtras('user');
        $this->removeInactivityExtras('zzz_user');
        $this->removeInactivityExtras('room');
        $this->removeInactivityExtras('zzz_room');

        $this->write('removing inactivity configuration from portal extras');
        $this->removeInactivityConfiguration();
    }

    private function removeInactivityExtras(string $table)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('t.item_id', 't.extras')
            ->from($table, 't')
            ->where('t.extras lIKE "%MAIL_SEND_LOCK%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_NEXT_LOCK%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_LOCKED%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_DELETE%"')
            ->orWhere('t.extras lIKE "%MAIL_SEND_NEXT_DELETE%"')
            ->orWhere('t.extras lIKE "%LOCK_SEND_MAIL_DATE%"')
            ->orWhere('t.extras lIKE "%LOCK%"')
            ->orWhere('t.extras lIKE "%NOTIFY_LOCK_DATE%"')
            ->orWhere('t.extras lIKE "%NOTIFY_DELETE_DATE%"')
            ->orWhere('t.extras lIKE "%ARCHIVE_SEND_MAIL_DATETIME%"')
            ->orWhere('t.extras lIKE "%DELETE_SEND_MAIL_DATETIME%"');
        $entries = $qb->execute();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            $keys = ['MAIL_SEND_LOCK', 'MAIL_SEND_NEXT_LOCK', 'MAIL_SEND_LOCKED', 'MAIL_SEND_DELETE',
                'MAIL_SEND_NEXT_DELETE', 'LOCK_SEND_MAIL_DATE', 'LOCK', 'NOTIFY_LOCK_DATE', 'NOTIFY_DELETE_DATE',
                'ARCHIVE_SEND_MAIL_DATETIME', 'DELETE_SEND_MAIL_DATETIME'];

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

    private function removeInactivityConfiguration()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('p.id', 'p.extras')
            ->from('portal', 'p')
            ->where('p.extras lIKE "%INACTIVITY_LOCK%"')
            ->orWhere('p.extras lIKE "%INACTIVITY_MAIL_BEFORE_LOCK%"')
            ->orWhere('p.extras lIKE "%INACTIVITY_DELETE%"')
            ->orWhere('p.extras lIKE "%INACTIVITY_MAIL_DELETE%"')
            ->orWhere('p.extras lIKE "%INACTIVITY_CHANGE_SETTING_TIME%"')
            ->orWhere('p.extras lIKE "%INACTIVITY_CONFIGURATION_DATE%"')
            ->orWhere('p.extras lIKE "%ARCHIVING_ROOMS_STATUS%"')
            ->orWhere('p.extras lIKE "%ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE%"')
            ->orWhere('p.extras lIKE "%ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE%"')
            ->orWhere('p.extras lIKE "%DELETING_ROOMS_STATUS%"')
            ->orWhere('p.extras lIKE "%ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE%"')
            ->orWhere('p.extras lIKE "%ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE%"')
            ->orWhere('p.extras lIKE "%DELETING_ROOMS_STATUS%"');
        $entries = $qb->execute();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            $keys = ['INACTIVITY_LOCK', 'INACTIVITY_MAIL_BEFORE_LOCK', 'INACTIVITY_DELETE', 'INACTIVITY_MAIL_DELETE',
                'INACTIVITY_CHANGE_SETTING_TIME', 'INACTIVITY_CONFIGURATION_DATE', 'ARCHIVING_ROOMS_STATUS',
                'ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_ARCHIVE', 'ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_ARCHIVE',
                'DELETING_ROOMS_STATUS', 'ARCHIVING_ROOMS_DAYS_UNUSED_BEFORE_DELETE',
                'ARCHIVING_ROOMS_DAYS_SEND_MAIL_BEFORE_DELETE'];

            foreach ($keys as $key) {
                if (isset($extras[$key])) {
                    unset ($extras[$key]);
                }
            }

            $this->connection->update('portal', [
                'extras' => serialize($extras),
            ], [
                'id' => $entry['id'],
            ]);
        }
    }
}
