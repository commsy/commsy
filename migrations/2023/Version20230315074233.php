<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230315074233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'drop deprecated extras / remove invalid rubrics';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'room', 'item_id', [
            'ROOM_CONTEXT', 'SHOWADS', 'SHOWGOOGLEADS', 'SHOWAMAZONADS', 'SPONSORTITLE', 'SPONSORS', 'PLUGIN_CONFIG',
            'PLUGIN_CONFIG_DATA'
        ]);

        $this->updateRoomConfiguration("room");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function updateRoomConfiguration(string $table) {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('r.item_id', 'r.type', 'r.extras')
            ->from($table, 'r');

        $rooms = $qb->executeQuery()->fetchAllAssociative();

        foreach ($rooms as $room) {
            $extras = unserialize($room['extras']);

            if (isset($extras['HOMECONF']) && !empty($extras['HOMECONF'])) {
                $homeConf = $this->removeInvalidRubrics($room['type'], $extras['HOMECONF']);
                $extras['HOMECONF'] = $homeConf;

                $this->connection->update($table, [
                    'extras' => serialize($extras),
                ], [
                    'item_id' => $room['item_id'],
                ]);
            }
        }
    }

    private function removeInvalidRubrics(string $roomType, string $config): string
    {
        $allowedMap = [
            'community' => ['announcement', 'project', 'todo', 'date', 'material', 'discussion', 'user', 'topic'],
            'grouproom' => ['announcement', 'todo', 'date', 'material', 'discussion', 'user', 'topic'],
            'privateroom' => ['myroom', 'date', 'entry'],
            'project' => ['announcement', 'todo', 'date', 'material', 'discussion', 'user', 'group', 'topic'],
            'userroom' => ['announcement', 'todo', 'material', 'discussion', 'user'],
        ];
        $allowedTypes = $allowedMap[$roomType];

        $rubrics = explode(',', $config);

        $rubrics = array_filter($rubrics, function ($rubric) use ($allowedTypes) {
            if (!str_contains($rubric, '_')) {
                return false;
            }

            [$rubricType, $rubricConf] = explode('_', $rubric);
            if (empty($rubricType) || empty($rubricConf) ||
                !in_array($rubricType, $allowedTypes) ||
                !in_array($rubricConf, ['show', 'hide']))
            {
                return false;
            }

            return true;
        });

        return implode(',', array_values($rubrics));
    }
}
