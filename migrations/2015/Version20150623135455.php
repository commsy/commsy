<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;

use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20150623135455 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $schemaManager = $this->connection->createSchemaManager();

        $tables = [
            'annotations',
            'announcement',
            'assessments',
            'dates',
            'discussionarticles',
            'discussions',
            'files',
            'labels',
            'link_items',
            'materials',
            'portal',
            'portfolio',
            'room',
            'room_privat',
            'section',
            'server',
            'step',
            'tag',
            'tag2tag',
            'tasks',
            'todos',
            'zzz_annotations',
            'zzz_announcement',
            'zzz_assessments',
            'zzz_dates',
            'zzz_discussionarticles',
            'zzz_discussions',
            'zzz_files',
            'zzz_labels',
            'zzz_link_items',
            'zzz_materials',
            'zzz_room',
            'zzz_section',
            'zzz_step',
            'zzz_tag',
            'zzz_tag2tag',
            'zzz_tasks',
            'zzz_todos',
        ];

        foreach ($tables as $table) {
            $columns = $schemaManager->listTableColumns($table);

            if (array_filter($columns, fn($column) => $column->getName() === 'creator_id')) {
                $this->addSql('ALTER TABLE ' . $table . ' MODIFY creator_id INT(11) NULL');
            }

            if (array_filter($columns, fn($column) => $column->getName() === 'modifier_id')) {
                $this->addSql('ALTER TABLE ' . $table . ' MODIFY modifier_id INT(11) NULL');
            }
        }

        $columns = [
            'creator_id',
            'modifier_id',
            'deleter_id',
        ];

        foreach ($tables as $table) {
            foreach ($columns as $column) {
                $this->fixAssociations($table, $column, str_starts_with($table, 'zzz'));
            }
        }

        $roomColumns = $schemaManager->listTableColumns('room');

        if (array_filter($roomColumns, fn($column) => $column->getName() === 'description')) {
            $this->addSql('ALTER TABLE room DROP COLUMN description');
        }

        $zzzRoomColumns = $schemaManager->listTableColumns('zzz_room');
        if (array_filter($zzzRoomColumns, fn($column) => $column->getName() === 'description')) {
            $this->addSql('ALTER TABLE zzz_room DROP COLUMN description');
        }
    }

    public function postUp(Schema $schema) : void
    {
        $this->write('updating room configuration in room');
        $this->updateRoomConfiguration("room");

        $this->write('updating room configuration in zzz_room');
        $this->updateRoomConfiguration("zzz_room");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function fixAssociations($tableName, $columnName, $archived = false) {
        $userTable = $archived ? 'zzz_user' : 'user';

        $schemaManager = $this->connection->createSchemaManager();
        $roomColumns = $schemaManager->listTableColumns($tableName);

        if (array_filter($roomColumns, fn($column) => $column->getName() === $columnName)) {
            $this->addSql('UPDATE ' . $tableName . ' LEFT JOIN ' . $userTable . ' ON ' . $tableName . '.' . $columnName . ' = ' . $userTable . '.item_id SET ' . $tableName . '.' . $columnName . ' = NULL WHERE ' . $userTable . '.item_id IS NULL');
        }
    }

    private function updateRoomConfiguration($table) {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('r.item_id', 'r.extras')
            ->from($table, 'r');

        $rooms = $qb->executeQuery()->fetchAllAssociative();

        foreach ($rooms as $room) {
            $extras = DbConverter::convertToPHPValue($room['extras']);

            if (isset($extras['HOMECONF']) && !empty($extras['HOMECONF'])) {
                $homeConf = $this->migrateHomeConf($extras['HOMECONF']);
                $extras['HOMECONF'] = $homeConf;

                $this->connection->update($table, [
                    'extras' => serialize($extras),
                ], [
                    'item_id' => $room['item_id'],
                ]);
            }
        }
    }

    private function migrateHomeConf($homeConfiguration)
    {
        // old home configuration syntax looks like
        // [rubric]_[short|tiny|none]
        // since we now got a feed, we need to convert these values
        $convertMap = ['' => 'show', 'short' => 'show', 'tiny' => 'show', 'none' => 'hide', 'nodisplay' => 'hide', 'show' => 'show', 'hide' => 'hide'];

        $convertedConfiguration = [];

        $rubricConfigurations = explode(',', (string) $homeConfiguration);
        foreach ($rubricConfigurations as $rubricConfiguration) {
            [$rubric, $mode] = explode('_', $rubricConfiguration);

            $convertedConfiguration[] = $rubric . '_' . ($convertMap[$mode] ?: $convertMap['']);
        }

        return implode(',', $convertedConfiguration);
    }
}
