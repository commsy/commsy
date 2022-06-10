<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220610091500 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('alter table annotations add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table announcement add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table dates add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table discussions add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table files add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table labels add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table link_items add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table materials add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table portfolio add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table section add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table step add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table tag add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table tasks add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table todos add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table user add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table discussionarticles add link_modifier_item_date  datetime  NULL after modification_date;');
        $this->addSql('alter table items add link_modifier_item_date  datetime  NULL after modification_date;');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('alter table annotations DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table announcement DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table dates DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table discussions DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table files DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table labels DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table link_items DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table materials DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table portfolio DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table section DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table step DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table tag DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table tasks DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table todos DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table user DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table discussionarticles DROP COLUMN link_modifier_item_date;');
        $this->addSql('alter table discussionarticles DROP COLUMN items;');

    }
}
