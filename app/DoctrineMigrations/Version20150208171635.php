<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150208171635 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX context_id ON room');
        $this->addSql('DROP INDEX type ON room');
        $this->addSql('DROP INDEX status ON room');
        $this->addSql('DROP INDEX activity ON room');
        $this->addSql('DROP INDEX deletion_date ON room');
        $this->addSql('DROP INDEX room_description ON room');
        $this->addSql('DROP INDEX contact_persons ON room');
        $this->addSql('DROP INDEX title ON room');
        $this->addSql('DROP INDEX lastlogin ON room');
        $this->addSql('DROP INDEX status_2 ON room');
        $this->addSql('ALTER TABLE room DROP PRIMARY KEY');
        $this->addSql('UPDATE room SET description = COALESCE(description, room_description)');
        $this->addSql('ALTER TABLE room CHANGE item_id id INT NOT NULL, CHANGE creation_date created_at DATETIME NOT NULL, CHANGE modification_date modified_at DATETIME NOT NULL, CHANGE deletion_date deleted_at DATETIME DEFAULT NULL, CHANGE is_open_for_guests open_for_guests TINYINT(1) NOT NULL, CHANGE contact_persons contact_persons VARCHAR(255) NOT NULL, CHANGE lastlogin last_login DATETIME NOT NULL, CHANGE creator_id creator_id INT DEFAULT NULL, CHANGE extras extras LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', CHANGE status status SMALLINT NOT NULL, CHANGE activity activity INT NOT NULL, CHANGE type type VARCHAR(20) NOT NULL, CHANGE public public TINYINT(1) NOT NULL, CHANGE continuous continuous TINYINT(1) NOT NULL, CHANGE template template TINYINT(1) NOT NULL, CHANGE description description LONGTEXT NOT NULL, DROP room_description, CHANGE context_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B727ACA70 FOREIGN KEY (parent_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BD079F553 FOREIGN KEY (modifier_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BEAEF1DFE FOREIGN KEY (deleter_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX UNIQ_729F519B727ACA70 ON room (parent_id)');
        $this->addSql('ALTER TABLE room ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX creator_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519B61220EA6 ON room (creator_id)');
        $this->addSql('DROP INDEX modifier_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519BD079F553 ON room (modifier_id)');
        $this->addSql('DROP INDEX deleter_id ON room');
        $this->addSql('CREATE INDEX IDX_729F519BEAEF1DFE ON room (deleter_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B727ACA70');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B61220EA6');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BD079F553');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BEAEF1DFE');
        $this->addSql('DROP INDEX UNIQ_729F519B727ACA70 ON room');
        $this->addSql('ALTER TABLE room DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519B61220EA6');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BD079F553');
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BEAEF1DFE');
        $this->addSql('ALTER TABLE room CHANGE id item_id INT DEFAULT 0 NOT NULL, CHANGE created_at creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE modified_at modification_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL, CHANGE deleted_at deletion_date DATETIME DEFAULT NULL, CHANGE open_for_guests is_open_for_guests TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE contact_persons contact_persons VARCHAR(255) DEFAULT NULL COLLATE utf8_general_ci, ADD room_description VARCHAR(10000) DEFAULT NULL COLLATE utf8_general_ci, CHANGE last_login lastlogin DATETIME DEFAULT NULL, CHANGE creator_id creator_id INT DEFAULT 0 NOT NULL, CHANGE extras extras TEXT DEFAULT NULL COLLATE utf8_general_ci, CHANGE status status VARCHAR(20) NOT NULL COLLATE utf8_general_ci, CHANGE activity activity INT DEFAULT 0 NOT NULL, CHANGE type type VARCHAR(20) DEFAULT \'project\' NOT NULL COLLATE utf8_general_ci, CHANGE public public TINYINT(1) DEFAULT \'0\' NOT NULL, CHANGE continuous continuous TINYINT(1) DEFAULT \'-1\' NOT NULL, CHANGE template template TINYINT(1) DEFAULT \'-1\' NOT NULL, CHANGE description description TEXT DEFAULT NULL COLLATE utf8_general_ci, CHANGE parent_id context_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX context_id ON room (context_id)');
        $this->addSql('CREATE INDEX type ON room (type)');
        $this->addSql('CREATE INDEX status ON room (status)');
        $this->addSql('CREATE INDEX activity ON room (activity)');
        $this->addSql('CREATE INDEX deletion_date ON room (deletion_date)');
        $this->addSql('CREATE INDEX room_description ON room (room_description)');
        $this->addSql('CREATE INDEX contact_persons ON room (contact_persons)');
        $this->addSql('CREATE INDEX title ON room (title)');
        $this->addSql('CREATE INDEX lastlogin ON room (lastlogin)');
        $this->addSql('CREATE INDEX status_2 ON room (status)');
        $this->addSql('ALTER TABLE room ADD PRIMARY KEY (item_id)');
        $this->addSql('DROP INDEX idx_729f519b61220ea6 ON room');
        $this->addSql('CREATE INDEX creator_id ON room (creator_id)');
        $this->addSql('DROP INDEX idx_729f519beaef1dfe ON room');
        $this->addSql('CREATE INDEX deleter_id ON room (deleter_id)');
        $this->addSql('DROP INDEX idx_729f519bd079f553 ON room');
        $this->addSql('CREATE INDEX modifier_id ON room (modifier_id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519B61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BD079F553 FOREIGN KEY (modifier_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BEAEF1DFE FOREIGN KEY (deleter_id) REFERENCES user (id)');
    }
}
