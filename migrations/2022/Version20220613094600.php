<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220613094600 extends AbstractMigration
{
   public function up(Schema $schema): void
    {
        $this->addSql("update announcement set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update dates set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update discussions set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update materials set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update todos set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update labels set modification_date  =  creation_date where modification_date ='9999-00-00 00:00:00';");


    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
