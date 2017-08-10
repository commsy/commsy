<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170810143230 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE dates
            ADD whole_day TINYINT NOT NULL DEFAULT 0;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            ADD whole_day TINYINT NOT NULL DEFAULT 0;
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
            DROP whole_day;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            DROP whole_day;
        ');
    }
}
