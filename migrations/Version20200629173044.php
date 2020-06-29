<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200629173044 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update portal table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table portal add terms_de text null after description_en;');
        $this->addSql('alter table portal add terms_en text null after terms_de;');
    }

    public function postUp(Schema $schema): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $qb = $queryBuilder
            ->select('p.id', 'p.extras')
            ->from('portal', '`p`');
        $portals = $qb->execute();

        foreach ($portals as $portal) {
            $extras = DbConverter::convertToPHPValue($portal['extras']);

            $this->connection->update('portal', [
                'terms_de' => $extras['AGBTEXTARRAY']['DE'] ?? '',
                'terms_en' => $extras['AGBTEXTARRAY']['EN'] ?? '',
            ], [
                'id' => $portal['id'],
            ]);

            unset($extras['AGBTEXTARRAY']);

            $this->connection->update('portal', [
                'extras' => sizeof($extras) === 0 ? null : serialize($extras),
            ], [
                'id' => $portal['id'],
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
