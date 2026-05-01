<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413133513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add portal columns for user deletion strategy configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal ADD cascading_user_deletion_strategy TINYINT(1) DEFAULT 0 NOT NULL, ADD allow_user_defined_deletion_strategy TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE portal DROP cascading_user_deletion_strategy, DROP allow_user_defined_deletion_strategy');
    }
}
