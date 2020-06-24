<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200617133036 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update portal table';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table portal drop column `type`;');
        $this->addSql('alter table portal drop column url;');
        $this->addSql('alter table portal add description_de text null after title;');
        $this->addSql('alter table portal add description_en text null after description_de;');
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
                'description_de' => $extras['DESCRIPTION']['de'] ?? '',
                'description_en' => $extras['DESCRIPTION']['en'] ?? '',
            ], [
                'id' => $portal['id'],
            ]);

            unset($extras['DESCRIPTION']);

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
