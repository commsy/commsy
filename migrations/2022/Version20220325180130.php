<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325180130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove "TODOMANAGEMENT" from room extras';
    }

    public function up(Schema $schema) : void
    {
        $this->updateTable('room');
        $this->updateTable('zzz_room');
    }

    private function updateTable(string $tableName)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('r.item_id', 'r.extras')
            ->from($tableName, 'r')
            ->where('r.extras LIKE "%TODOMANAGEMENT%"');
        $entries = $qb->executeQuery()->fetchAllAssociative();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            if (isset($extras['TODOMANAGEMENT'])) {
                unset($extras['TODOMANAGEMENT']);
            }

            $this->connection->update($tableName, [
                'extras' => serialize($extras),
            ], [
                'item_id' => $entry['item_id'],
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
