<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220224121901 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove "SERVER_NEWS" from portal extras';
    }

    public function up(Schema $schema) : void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('p.id', 'p.extras')
            ->from('portal', 'p')
            ->where('p.extras LIKE "%SERVER_NEWS%"');
        $entries = $qb->execute();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            if (isset($extras['SERVER_NEWS'])) {
                unset($extras['SERVER_NEWS']);
            }

            $this->connection->update('portal', [
                'extras' => serialize($extras),
            ], [
                'id' => $entry['id'],
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
