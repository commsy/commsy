<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220803124700 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE discussionarticles SET description = CONCAT(\'<h3>\', subject, \'</h3>\', description)');
        $this->addSql('UPDATE zzz_discussionarticles SET description = CONCAT(\'<h3>\', subject, \'</h3>\', description)');
    }

    public function down(Schema $schema): void
    {

    }
}
