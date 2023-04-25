<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230228115913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');

        $this->updateRoomConfiguration("room");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function updateRoomConfiguration($table) {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('r.item_id', 'r.extras')
            ->from($table, 'r');

        $rooms = $qb->executeQuery()->fetchAllAssociative();

        foreach ($rooms as $room) {
            $extras = unserialize($room['extras']);

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
        $convertMap = [
            '' => 'show',
            'short' => 'show',
            'tiny' => 'show',
            'none' => 'hide',
            'nodisplay' => 'hide',
            'show' => 'show',
            'hide' => 'hide',
        ];

        $convertedConfiguration = [];

        $rubricConfigurations = explode(',', (string) $homeConfiguration);
        foreach ($rubricConfigurations as $rubricConfiguration) {
            if (!str_contains($rubricConfiguration, '_')) {
                continue;
            }

            [$rubric, $mode] = explode('_', $rubricConfiguration);

            if (!empty($rubric) && !empty($mode) && $convertMap[$mode]) {
                $convertedConfiguration[] = $rubric . '_' . $convertMap[$mode];
            }
        }

        return implode(',', $convertedConfiguration);
    }
}
