<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514125210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add account_id to user table / Add creator and modifier FK constraints';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD account_id INT DEFAULT NULL AFTER not_deleted');
        $this->addSql('ALTER TABLE user CHANGE creator_id creator_id INT DEFAULT NULL');

        // Set account_id for root and non-root
        $this->addSql('CREATE OR REPLACE INDEX tmp ON accounts (username, auth_source_id)');
        $this->addSql('UPDATE user u SET account_id = (SELECT a.id FROM accounts a WHERE a.username = \'root\') WHERE u.user_id = \'root\'');
        $this->addSql('UPDATE user u SET account_id = (SELECT a.id FROM accounts a INNER JOIN auth_source aso ON a.auth_source_id = aso.id WHERE a.username = u.user_id AND u.auth_source = a.auth_source_id AND a.context_id = aso.portal_id) WHERE u.user_id != \'root\'');
        $this->addSql('DROP INDEX IF EXISTS tmp ON accounts');

        // Set creator_id and modifier_id in room table to NULL, if the related user entry is missing or has no account
        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.creator_id = u.item_id SET r.creator_id = NULL WHERE r.creator_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');
        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.modifier_id = u.item_id SET r.modifier_id = NULL WHERE r.modifier_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');

        // Set creator_id and modifier_id in user table to NULL, if the related user entry is missing
        $this->addSql('UPDATE user u LEFT JOIN user u2 ON u.creator_id = u2.item_id SET u.creator_id = NULL WHERE u.creator_id IS NOT NULL AND u2.item_id IS NULL');
        $this->addSql('UPDATE user u LEFT JOIN user u2 ON u.modifier_id = u2.item_id SET u.modifier_id = NULL WHERE u.modifier_id IS NOT NULL AND u2.item_id IS NULL');

        // Set creator_id and modifier_id in room_private table to NULL, if the related user entry is missing or has no account
        $this->addSql('UPDATE room_privat AS rp LEFT JOIN user AS u ON rp.creator_id = u.item_id SET rp.creator_id = NULL WHERE rp.creator_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');
        $this->addSql('UPDATE room_privat AS rp LEFT JOIN user AS u ON rp.modifier_id = u.item_id SET rp.modifier_id = NULL WHERE rp.modifier_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');

        $this->addSql('DELETE FROM user WHERE user.modification_date IS NULL AND user.not_deleted IS NULL');

        // Delete user entries not having an account
//        $this->addSql('DELETE FROM user WHERE user.account_id IS NULL');

        // Clean up again
//        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.creator_id = u.item_id SET r.creator_id = NULL WHERE r.creator_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');
//        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.modifier_id = u.item_id SET r.modifier_id = NULL WHERE r.modifier_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');
//        $this->addSql('UPDATE user u LEFT JOIN user u2 ON u.creator_id = u2.item_id SET u.creator_id = NULL WHERE u.creator_id IS NOT NULL AND u2.item_id IS NULL');
//        $this->addSql('UPDATE user u LEFT JOIN user u2 ON u.modifier_id = u2.item_id SET u.modifier_id = NULL WHERE u.modifier_id IS NOT NULL AND u2.item_id IS NULL');
//        $this->addSql('UPDATE room_privat AS rp LEFT JOIN user AS u ON rp.creator_id = u.item_id SET rp.creator_id = NULL WHERE rp.creator_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');
//        $this->addSql('UPDATE room_privat AS rp LEFT JOIN user AS u ON rp.modifier_id = u.item_id SET rp.modifier_id = NULL WHERE rp.modifier_id IS NOT NULL AND (u.item_id IS NULL OR u.account_id IS NULL)');

        $this->addSql('ALTER TABLE user CHANGE account_id account_id INT DEFAULT NULL, CHANGE item_id item_id INT NOT NULL, CHANGE context_id context_id INT NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE modification_date modification_date DATETIME NOT NULL, CHANGE not_deleted not_deleted TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date, CHANGE status status SMALLINT NOT NULL, CHANGE visible visible TINYINT(1) NOT NULL, CHANGE extras extras LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64961220EA6 FOREIGN KEY (creator_id) REFERENCES user (item_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D079F553 FOREIGN KEY (modifier_id) REFERENCES user (item_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499B6B5FBA FOREIGN KEY (account_id) REFERENCES accounts (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649D079F553 ON user (modifier_id)');
        $this->addSql('CREATE INDEX IDX_8D93D6499B6B5FBA ON user (account_id)');
        $this->addSql('ALTER TABLE user RENAME INDEX context_idx TO IDX_8D93D6496B00C1CF');

        DbConverter::removeExtra($this->connection, 'user', 'item_id', [
            'CONFIG_NEW_UPLOAD_STATUS',
            'EXTERNALID',
            'TEMPORARY_LOCK',
            'LOCK'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64961220EA6');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649D079F553');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499B6B5FBA');
        $this->addSql('DROP INDEX IDX_8D93D649D079F553 ON user');
        $this->addSql('DROP INDEX IDX_8D93D6499B6B5FBA ON user');
        $this->addSql('ALTER TABLE user DROP account_id, CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE context_id context_id INT DEFAULT NULL, CHANGE creator_id creator_id INT DEFAULT 0 NOT NULL, CHANGE not_deleted not_deleted TINYINT(1) DEFAULT NULL, CHANGE status status TINYINT(1) DEFAULT 0 NOT NULL, CHANGE visible visible TINYINT(1) DEFAULT 1 NOT NULL, CHANGE extras extras MEDIUMTEXT DEFAULT NULL, CHANGE creation_date creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE modification_date modification_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX idx_8d93d6496b00c1cf TO context_idx');
    }
}
