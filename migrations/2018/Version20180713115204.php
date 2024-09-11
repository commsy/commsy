<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180713115204 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE dates
            ADD datetime_recurrence DATETIME NULL DEFAULT NULL;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            ADD datetime_recurrence DATETIME NULL DEFAULT NULL;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->addSql('
            ALTER TABLE dates
            DROP datetime_recurrence;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            DROP datetime_recurrence;
        ');
    }
}
