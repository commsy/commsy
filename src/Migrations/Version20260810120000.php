<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260810120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add portal column for the portal-specific base URL advertised in outgoing mail';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal ADD base_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal DROP base_url');
    }
}
