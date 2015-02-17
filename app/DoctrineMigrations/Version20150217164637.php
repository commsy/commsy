<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150217164637 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX type ON items');
        $this->addSql('ALTER TABLE items CHANGE item_id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE items DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE items ADD creator_id INT DEFAULT NULL, ADD modifier_id INT DEFAULT NULL, ADD created_at DATETIME NOT NULL, CHANGE modification_date modified_at DATETIME NOT NULL, CHANGE deletion_date deleted_at DATETIME DEFAULT NULL, CHANGE type discriminator VARCHAR(255) NOT NULL, CHANGE context_id parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE items ADD CONSTRAINT FK_E11EE94D61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE items ADD CONSTRAINT FK_E11EE94DD079F553 FOREIGN KEY (modifier_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE items ADD CONSTRAINT FK_E11EE94DEAEF1DFE FOREIGN KEY (deleter_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E11EE94D61220EA6 ON items (creator_id)');
        $this->addSql('CREATE INDEX IDX_E11EE94DD079F553 ON items (modifier_id)');
        $this->addSql('CREATE INDEX IDX_E11EE94DEAEF1DFE ON items (deleter_id)');
        $this->addSql('CREATE INDEX discriminator_idx ON items (discriminator)');
        $this->addSql('ALTER TABLE items ADD PRIMARY KEY (item_id)');
        $this->addSql('ALTER TABLE items CHANGE item_id id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX context_id ON items');
        $this->addSql('CREATE INDEX parent_idx ON items (parent_id)');
        $this->addSql('UPDATE items AS i INNER JOIN (SELECT r.id, r.creator_id, r.modifier_id, r.created_at FROM room AS r) AS ro ON i.id = ro.id SET i.creator_id = ro.creator_id, i.created_at = ro.created_at, i.modifier_id = ro.modifier_id');

        $this->addSql('DROP INDEX UNIQ_729F519B727ACA70 ON room');
        $this->addSql('DROP INDEX IDX_729F519B61220EA6 ON room');
        $this->addSql('DROP INDEX IDX_729F519BD079F553 ON room');
        $this->addSql('DROP INDEX IDX_729F519BEAEF1DFE ON room');
        $this->addSql('ALTER TABLE room DROP parent_id, DROP creator_id, DROP modifier_id, DROP deleter_id, DROP created_at, DROP modified_at, DROP deleted_at, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BBF396750 FOREIGN KEY (id) REFERENCES items (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BBF396750');
        $this->addSql('ALTER TABLE room ADD parent_id INT DEFAULT NULL, ADD creator_id INT DEFAULT NULL, ADD modifier_id INT DEFAULT NULL, ADD deleter_id INT DEFAULT NULL, ADD created_at DATETIME NOT NULL, ADD modified_at DATETIME NOT NULL, ADD deleted_at DATETIME DEFAULT NULL, CHANGE description description LONGTEXT NOT NULL COLLATE utf8_general_ci, CHANGE last_login last_login DATETIME NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_729F519B727ACA70 ON room (parent_id)');
        $this->addSql('CREATE INDEX IDX_729F519B61220EA6 ON room (creator_id)');
        $this->addSql('CREATE INDEX IDX_729F519BD079F553 ON room (modifier_id)');
        $this->addSql('CREATE INDEX IDX_729F519BEAEF1DFE ON room (deleter_id)');
        $this->addSql('UPDATE room AS r INNER JOIN (SELECT i.id, i.creator_id, i.modifier_id, i.created_at FROM items AS i) AS it ON r.id = it.id SET r.creator_id = it.creator_id, r.created_at = it.created_at, r.modifier_id = it.modifier_id');

        $this->addSql('ALTER TABLE items DROP FOREIGN KEY FK_E11EE94D61220EA6');
        $this->addSql('ALTER TABLE items DROP FOREIGN KEY FK_E11EE94DD079F553');
        $this->addSql('ALTER TABLE items DROP FOREIGN KEY FK_E11EE94DEAEF1DFE');
        $this->addSql('DROP INDEX IDX_E11EE94D61220EA6 ON items');
        $this->addSql('DROP INDEX IDX_E11EE94DD079F553 ON items');
        $this->addSql('DROP INDEX IDX_E11EE94DEAEF1DFE ON items');
        $this->addSql('DROP INDEX discriminator_idx ON items');
        $this->addSql('ALTER TABLE items CHANGE id item_id INT NOT NULL');
        $this->addSql('ALTER TABLE items DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE items CHANGE discriminator type VARCHAR(15) NOT NULL COLLATE utf8_general_ci, CHANGE modified_at modification_date DATETIME DEFAULT NULL, DROP creator_id, DROP modifier_id, DROP created_at, CHANGE parent_id context_id INT DEFAULT NULL, CHANGE deleted_at deletion_date DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX type ON items (type)');
        $this->addSql('ALTER TABLE items ADD PRIMARY KEY (item_id)');
        $this->addSql('ALTER TABLE items CHANGE item_id item_id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('DROP INDEX parent_idx ON items');
        $this->addSql('CREATE INDEX context_id ON items (context_id)');
    }
}
