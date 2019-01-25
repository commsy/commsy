<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20150623135455 extends AbstractMigration implements ContainerAwareInterface
{
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $schemaManager = $this->connection->getSchemaManager();

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

            if (array_filter($columns, function($column) {
                return $column->getName() === 'creator_id';
            })) {
                $this->addSql('ALTER TABLE ' . $table . ' MODIFY creator_id INT(11) NULL');
            }

            if (array_filter($columns, function($column) {
                return $column->getName() === 'modifier_id';
            })) {
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
                $this->fixAssociations($table, $column, substr($table, 0, 3) === 'zzz');
            }
        }

        $roomColumns = $schemaManager->listTableColumns('room');

        if (array_filter($roomColumns, function($column) {
            return $column->getName() === 'description';
        })) {
            $this->addSql('ALTER TABLE room DROP COLUMN description');
        }

        $zzzRoomColumns = $schemaManager->listTableColumns('zzz_room');
        if (array_filter($zzzRoomColumns, function($column) {
            return $column->getName() === 'description';
        })) {
            $this->addSql('ALTER TABLE zzz_room DROP COLUMN description');
        }
    }

    public function postUp(Schema $schema)
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

        $schemaManager = $this->connection->getSchemaManager();
        $roomColumns = $schemaManager->listTableColumns($tableName);

        if (array_filter($roomColumns, function($column) use ($columnName) {
            return $column->getName() === $columnName;
        })) {
            $this->addSql('UPDATE ' . $tableName . ' LEFT JOIN ' . $userTable . ' ON ' . $tableName . '.' . $columnName . ' = ' . $userTable . '.item_id SET ' . $tableName . '.' . $columnName . ' = NULL WHERE ' . $userTable . '.item_id IS NULL');
        }
    }

    private function updateRoomConfiguration($table) {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('r.item_id', 'r.extras')
            ->from($table, 'r');

        $rooms = $qb->execute();

        foreach ($rooms as $room) {
            $extras = $this->convertToPHPValue($room['extras']);

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
        $convertMap = array(
            '' => 'show',
            'short' => 'show',
            'tiny' => 'show',
            'none' => 'hide',
            'nodisplay' => 'hide',
            'show' => 'show',
            'hide' => 'hide',
        );

        $convertedConfiguration = array();

        $rubricConfigurations = explode(',', $homeConfiguration);
        foreach ($rubricConfigurations as $rubricConfiguration) {
            list($rubric, $mode) = explode('_', $rubricConfiguration);

            $convertedConfiguration[] = $rubric . '_' . ($convertMap[$mode] ? $convertMap[$mode] : $convertMap['']);
        }

        return implode(',', $convertedConfiguration);
    }

    private function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return array();
        }

        $value = preg_replace_callback('/s:(\d+):"(.*?)";(?=\}|i|s|a)/s', function($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, $value );

        $val = @unserialize($value);
        if ($val === false && $value != 'b:0;') {
            // TODO: this is temporary, we need to fix db entries
            return array();

            //throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}
