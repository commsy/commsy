<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the picture of groups.
 *
 * It could no longer be set and was shown nowhere; it was only read to
 * overwrite the logo of the group's room. The file cleanup removes the
 * picture files once nothing refers to them.
 */
final class Version20261023210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the picture of groups';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'labels', 'item_id', ['LABELPICTURE']);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
