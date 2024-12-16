<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241001121006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate room table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE room SET template = 0 WHERE template = -1');
        $this->addSql('UPDATE room SET continuous = 0 WHERE continuous = -1');

        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.creator_id = u.item_id SET r.creator_id = NULL
                WHERE r.creator_id IS NOT NULL AND u.item_id IS NULL');

        $this->addSql('UPDATE room AS r LEFT JOIN user AS u ON r.modifier_id = u.item_id SET r.modifier_id = NULL
                WHERE r.modifier_id IS NOT NULL AND u.item_id IS NULL');

        $this->addSql('ALTER TABLE room DROP public, CHANGE extras extras LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', CHANGE continuous continuous TINYINT(1) DEFAULT 0 NOT NULL, CHANGE template template TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (item_id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BD079F553 FOREIGN KEY (modifier_id) REFERENCES user (item_id)');
        $this->addSql('DROP INDEX IDX_729F519BEAEF1DFE ON room');

        $this->addSql('ALTER TABLE room_privat CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE modification_date modification_date DATETIME NOT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE continuous continuous TINYINT(1) DEFAULT 0 NOT NULL, CHANGE template template TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE room_privat ADD CONSTRAINT FK_45609AA461220EA6 FOREIGN KEY (creator_id) REFERENCES user (item_id)');
        $this->addSql('ALTER TABLE room_privat ADD CONSTRAINT FK_45609AA4D079F553 FOREIGN KEY (modifier_id) REFERENCES user (item_id)');
        $this->addSql('CREATE INDEX IDX_45609AA4D079F553 ON room_privat (modifier_id)');

        $this->addSql('ALTER TABLE room_slug DROP FOREIGN KEY FK_FB99710654177093');
        $this->addSql('ALTER TABLE room_slug ADD CONSTRAINT FK_FB99710654177093 FOREIGN KEY (room_id) REFERENCES room (item_id)');

        DbConverter::removeExtra($this->connection, 'room', 'item_id', [
            'MEDIA_MDO_ACTIVE', 'MEDIA_MDO_KEY',
            'USAGE_INFO_FORM_TEXT'
        ]);

        DbConverter::removeExtra($this->connection, 'portal', 'id', [
            'USAGE_INFO_FORM_TEXT'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE room SET template = -1 WHERE template = 0');
        $this->addSql('UPDATE room SET continuous = -1 WHERE continuous = 0');

        $this->addSql('ALTER TABLE room_privat DROP FOREIGN KEY FK_45609AA461220EA6');
        $this->addSql('ALTER TABLE room_privat DROP FOREIGN KEY FK_45609AA4D079F553');
        $this->addSql('DROP INDEX IDX_45609AA4D079F553 ON room_privat');
        $this->addSql('ALTER TABLE room_privat CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE type type VARCHAR(20) DEFAULT \'privateroom\' NOT NULL, CHANGE continuous continuous TINYINT(1) DEFAULT -1 NOT NULL, CHANGE template template TINYINT(1) DEFAULT -1 NOT NULL, CHANGE creation_date creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE modification_date modification_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL');

        $this->addSql('ALTER TABLE room_slug DROP FOREIGN KEY FK_FB99710654177093');
        $this->addSql('ALTER TABLE room_slug ADD CONSTRAINT FK_FB99710654177093 FOREIGN KEY (room_id) REFERENCES room (item_id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B61220EA6');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BD079F553');
        $this->addSql('ALTER TABLE room ADD public TINYINT(1) DEFAULT 0 NOT NULL, CHANGE extras extras LONGTEXT DEFAULT NULL, CHANGE continuous continuous SMALLINT DEFAULT -1 NOT NULL, CHANGE template template SMALLINT DEFAULT -1 NOT NULL');
        $this->addSql('CREATE INDEX IDX_729F519BEAEF1DFE ON room (deleter_id)');

    }
}
