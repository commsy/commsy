<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409122021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on modifier_id in link_modifier_item to fix full table scans during cronharddelete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_modifier_id ON link_modifier_item (modifier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_modifier_id ON link_modifier_item');
    }
}
