<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230511120707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'file locking / relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE files ADD locking_id VARCHAR(1024) DEFAULT NULL, ADD locking_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE item_link_file CHANGE item_iid item_iid INT NOT NULL, CHANGE item_vid item_vid INT NOT NULL, CHANGE file_id file_id INT NOT NULL');
        $this->addSql('ALTER TABLE item_link_file ADD CONSTRAINT FK_3102B24993CB796C FOREIGN KEY (file_id) REFERENCES files (files_id)');
        $this->addSql('CREATE INDEX IDX_3102B24993CB796C ON item_link_file (file_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE item_link_file DROP FOREIGN KEY FK_3102B24993CB796C');
        $this->addSql('DROP INDEX IDX_3102B24993CB796C ON item_link_file');
        $this->addSql('ALTER TABLE item_link_file CHANGE item_iid item_iid INT DEFAULT 0 NOT NULL, CHANGE item_vid item_vid INT DEFAULT 0 NOT NULL, CHANGE file_id file_id INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE files DROP locking_id, DROP locking_date');
    }
}
