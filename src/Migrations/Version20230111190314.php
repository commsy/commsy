<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230111190314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop table external2commsy_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE external2commsy_id');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
