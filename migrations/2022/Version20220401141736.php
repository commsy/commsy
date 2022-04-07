<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220401141736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update user and zzz_user indices / adding generated persistent column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX is_contact ON user');
        $this->addSql('DROP INDEX user_context_id_index ON user');
        $this->addSql('DROP INDEX user_context_id_user_id_auth_source_index ON user');
        $this->addSql('DROP INDEX user_deleter_id_deletion_date_index ON user');
        $this->addSql('DROP INDEX status ON user');
        $this->addSql('ALTER TABLE user ADD not_deleted TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date');
        $this->addSql('CREATE INDEX deleted_idx ON user (deletion_date, deleter_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_non_soft_deleted_idx ON user (user_id, auth_source, context_id, not_deleted)');
        $this->addSql('DROP INDEX creator_id ON user');
        $this->addSql('CREATE INDEX creator_idx ON user (creator_id)');
        $this->addSql('CREATE INDEX context_idx ON user (context_id)');

        $this->addSql('DROP INDEX status ON zzz_user');
        $this->addSql('DROP INDEX is_contact ON zzz_user');
        $this->addSql('DROP INDEX deletion_date ON zzz_user');
        $this->addSql('DROP INDEX context_id ON zzz_user');
        $this->addSql('DROP INDEX deleter_id ON zzz_user');
        $this->addSql('ALTER TABLE zzz_user ADD not_deleted TINYINT(1) AS (IF (deleter_id IS NULL AND deletion_date IS NULL, 1, NULL)) PERSISTENT AFTER deletion_date');
        $this->addSql('CREATE INDEX deleted_idx ON zzz_user (deletion_date, deleter_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_non_soft_deleted_idx ON zzz_user (user_id, auth_source, context_id, not_deleted)');
        $this->addSql('DROP INDEX creator_id ON zzz_user');
        $this->addSql('CREATE INDEX creator_idx ON zzz_user (creator_id)');
        $this->addSql('DROP INDEX user_id ON zzz_user');
        $this->addSql('CREATE INDEX context_idx ON zzz_user (context_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX deleted_idx ON user');
        $this->addSql('DROP INDEX unique_non_soft_deleted_idx ON user');
        $this->addSql('ALTER TABLE user DROP not_deleted');
        $this->addSql('CREATE INDEX is_contact ON user (is_contact)');
        $this->addSql('CREATE INDEX user_context_id_index ON user (context_id)');
        $this->addSql('CREATE INDEX user_context_id_user_id_auth_source_index ON user (context_id, user_id, auth_source)');
        $this->addSql('CREATE INDEX user_deleter_id_deletion_date_index ON user (deleter_id, deletion_date)');
        $this->addSql('CREATE INDEX status ON user (status)');
        $this->addSql('DROP INDEX creator_idx ON user');
        $this->addSql('DROP INDEX context_idx ON user');
        $this->addSql('CREATE INDEX creator_id ON user (creator_id)');

        $this->addSql('DROP INDEX deleted_idx ON zzz_user');
        $this->addSql('DROP INDEX unique_non_soft_deleted_idx ON zzz_user');
        $this->addSql('ALTER TABLE zzz_user DROP not_deleted');
        $this->addSql('CREATE INDEX status ON zzz_user (status)');
        $this->addSql('CREATE INDEX is_contact ON zzz_user (is_contact)');
        $this->addSql('CREATE INDEX deletion_date ON zzz_user (deletion_date)');
        $this->addSql('CREATE INDEX context_id ON zzz_user (context_id)');
        $this->addSql('CREATE INDEX deleter_id ON zzz_user (deleter_id)');
        $this->addSql('CREATE INDEX user_id ON zzz_user (user_id)');
        $this->addSql('DROP INDEX creator_idx ON zzz_user');
        $this->addSql('DROP INDEX context_idx ON zzz_user');
        $this->addSql('CREATE INDEX creator_id ON zzz_user (creator_id)');
    }
}
