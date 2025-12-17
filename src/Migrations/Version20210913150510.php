<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210913150510 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create table for cron tasks';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE cron_task (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, last_run DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE cron_task');
    }

    public function postUp(Schema $schema): void
    {
        $this->write('removing last cron run information from extras');
        $this->removeCronExtras('server', 'item_id');
        $this->removeCronExtras('portal', 'id');
        $this->removeCronExtras('room', 'item_id');
        $this->removeCronExtras('room_privat', 'item_id');
    }

    private function removeCronExtras(string $table, string $identityColumn)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('t.' . $identityColumn, 't.extras')
            ->from($table, 't')
            ->where('t.extras lIKE "%CRON_DAILY%"')
            ->orWhere('t.extras lIKE "%CRON_WEEKLY%"');
        $entries = $qb->executeQuery()->fetchAllAssociative();

        foreach ($entries as $entry) {
            $extras = DbConverter::convertToPHPValue($entry['extras']);

            if (isset($extras['CRON_DAILY'])) {
                unset($extras['CRON_DAILY']);
            }
            if (isset($extras['CRON_WEEKLY'])) {
                unset($extras['CRON_WEEKLY']);
            }

            $this->connection->update($table, [
                'extras' => serialize($extras),
            ], [
                $identityColumn => $entry[$identityColumn],
            ]);
        }
    }
}
