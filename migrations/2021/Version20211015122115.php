<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211015122115 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove "EXPORT_TO_WIKI" from extras';
    }

    public function up(Schema $schema) : void
    {
        $schemaManager = $this->connection->getSchemaManager();

        $tables = $schemaManager->listTables();
        $tablesWithExtras = array_filter($tables, function ($table) {
            $columns = $table->getColumns();

            $withExtras = array_filter($columns, function ($column) {
                return $column->getName() === 'extras';
            });
            $withItemId = array_filter($columns, function ($column) {
                return $column->getName() === 'item_id';
            });

            return $withExtras && $withItemId;
        });

        foreach ($tablesWithExtras as $tableWithExtra) {
            $this->removeWikiExtras($tableWithExtra->getName());
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function removeWikiExtras(string $table)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('t.item_id', 't.extras')
            ->from($table, 't')
            ->where('t.extras LIKE "%EXPORT_TO_WIKI%"');
        $entries = $qb->execute();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            if (isset($extras['EXPORT_TO_WIKI'])) {
                unset($extras['EXPORT_TO_WIKI']);
            }

            $this->connection->update($table, [
                'extras' => serialize($extras),
            ], [
                'item_id' => $entry['item_id'],
            ]);
        }
    }
}
