<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops the stored Etherpad pad id from materials.
 *
 * The id is `<group of the room>$<material id>` and therefore follows from
 * data we already have. Keeping a copy added nothing and could go stale: a
 * stored id that no longer matched the room's pads sent the code down a
 * "create it" path that Etherpad refused, and the refusal was written back
 * as an empty id, which switched the pad-to-description write-back off.
 */
final class Version20260813170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove the redundant etherpad_id from material extras';
    }

    public function up(Schema $schema): void
    {
        // Only the id goes; the `etherpad` flag stays, it is what marks a
        // material as pad-edited. removeExtra matches on substring, so the
        // longer key has to be named exactly.
        DbConverter::removeExtra($this->connection, 'materials', 'item_id', ['etherpad_id']);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'The pad id is derived from room and material and can be recomputed at any time.'
        );
    }
}
