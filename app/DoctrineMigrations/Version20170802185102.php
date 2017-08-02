<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170802185102 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE dates
            ADD uid VARCHAR(255) NULL DEFAULT NULL;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            ADD uid VARCHAR(255) NULL DEFAULT NULL;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE dates
            DROP uid;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            DROP uid;
        ');
    }
}
