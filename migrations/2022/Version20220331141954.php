<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331141954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove title from annotations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE annotations DROP COLUMN title;');
        $this->addSql('ALTER TABLE zzz_annotations DROP COLUMN title;');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
