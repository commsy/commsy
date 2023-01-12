<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221220144110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'remove title / subject from discussion articles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE discussionarticles SET description = CONCAT(\'<h3>\', subject, \'</h3>\', description)');
        $this->addSql('ALTER TABLE discussionarticles DROP COLUMN subject');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
