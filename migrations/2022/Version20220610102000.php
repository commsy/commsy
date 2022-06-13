<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220610102000 extends AbstractMigration
{
   public function up(Schema $schema): void
    {
        $this->addSql('update announcement set link_modifier_item_date  =  modification_date;');
        $this->addSql('update dates set link_modifier_item_date  =  modification_date;');
        $this->addSql('update discussions set link_modifier_item_date  =  modification_date;');
        $this->addSql('update materials set link_modifier_item_date  =  modification_date;');
        $this->addSql('update todos set link_modifier_item_date  =  modification_date;');
        $this->addSql('update labels set link_modifier_item_date  =  modification_date;');


    }

    public function down(Schema $schema): void
    {
        $this->addSql('update announcement set link_modifier_item_date  =  null;');
        $this->addSql('update dates set link_modifier_item_date  =  null;');
        $this->addSql('update discussions set link_modifier_item_date  =  null;');
        $this->addSql('update materials set link_modifier_item_date  =  null;');
        $this->addSql('update todos set link_modifier_item_date  =  null;');
        $this->addSql('update labels set link_modifier_item_date  =  null;');


    }
}
