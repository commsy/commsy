<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150623133246 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE external_viewer ADD PRIMARY KEY (item_id, user_id)');
        $this->addSql('ALTER TABLE zzz_external_viewer ADD PRIMARY KEY (item_id, user_id)');

        $this->addSql('ALTER TABLE workflow_read ADD PRIMARY KEY (item_id, user_id)');
        $this->addSql('ALTER TABLE zzz_workflow_read ADD PRIMARY KEY (item_id, user_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE external_viewer DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE zzz_external_viewer DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE workflow_read DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE zzz_workflow_read DROP PRIMARY KEY');
    }
}
