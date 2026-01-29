<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223190813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set empty user extras to NULL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user SET extras = NULL WHERE extras = ""');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE user SET extras = "" WHERE extras IS NULL');
    }
}
