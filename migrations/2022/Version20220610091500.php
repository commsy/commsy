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
        $this->addSql('alter table announcement add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table dates add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table discussions add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table materials add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table todos add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table labels add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table items add activation_date datetime NULL after modification_date;');

        $this->addSql('update announcement set activation_date = modification_date;');
        $this->addSql('update dates set activation_date = modification_date;');
        $this->addSql('update discussions set activation_date = modification_date;');
        $this->addSql('update materials set activation_date = modification_date;');
        $this->addSql('update todos set activation_date = modification_date;');
        $this->addSql('update labels set activation_date = modification_date;');
        $this->addSql('update items set activation_date = modification_date;');

        $this->addSql("update announcement set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update dates set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update discussions set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update materials set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update todos set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update labels set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");

        $this->addSql('alter table zzz_announcement add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_dates add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_discussions add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_materials add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_todos add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_labels add activation_date datetime NULL after modification_date;');
        $this->addSql('alter table zzz_items add activation_date datetime NULL after modification_date;');

        $this->addSql('update zzz_announcement set activation_date = modification_date;');
        $this->addSql('update zzz_dates set activation_date = modification_date;');
        $this->addSql('update zzz_discussions set activation_date = modification_date;');
        $this->addSql('update zzz_materials set activation_date = modification_date;');
        $this->addSql('update zzz_todos set activation_date = modification_date;');
        $this->addSql('update zzz_labels set activation_date = modification_date;');
        $this->addSql('update zzz_items set activation_date = modification_date;');

        $this->addSql("update zzz_announcement set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update zzz_dates set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update zzz_discussions set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update zzz_materials set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update zzz_todos set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
        $this->addSql("update zzz_labels set modification_date = creation_date where modification_date ='9999-00-00 00:00:00';");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
