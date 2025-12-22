<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220163431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add portal_id to room table and migrate data';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room ADD portal_id INT DEFAULT NULL AFTER context_id');
        $this->addSql('ALTER TABLE room ADD CONSTRAINT FK_729F519BB887E1DD FOREIGN KEY (portal_id) REFERENCES portal (id)');
        $this->addSql('CREATE INDEX IDX_729F519BB887E1DD ON room (portal_id)');
        $this->addSql('CREATE INDEX IDX_729F519BEAEF1DFE ON room (deleter_id)');

        // standard case: context_id = portal.id
        $this->addSql('UPDATE room r INNER JOIN portal p ON r.context_id = p.id SET r.portal_id = p.id WHERE r.portal_id IS NULL');

        // useroom case: context_id = project.item_id
        // Userroom context_id is a project room. We need to look up that project room's context_id.
        $this->addSql("UPDATE room r
            INNER JOIN room parent ON r.context_id = parent.item_id
            INNER JOIN portal p ON parent.context_id = p.id
            SET r.portal_id = p.id
            WHERE r.type = 'userroom' AND r.portal_id IS NULL");

        $this->addSql('ALTER TABLE room MODIFY portal_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP FOREIGN KEY FK_729F519BB887E1DD');
        $this->addSql('DROP INDEX IDX_729F519BB887E1DD ON room');
        $this->addSql('DROP INDEX IDX_729F519BEAEF1DFE ON room');
        $this->addSql('ALTER TABLE room DROP portal_id');
    }
}
