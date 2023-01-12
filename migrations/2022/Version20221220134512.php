<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221220134512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make all "public" editable discussion articles private';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE discussionarticles SET public = 0 WHERE public = 1');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
