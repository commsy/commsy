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
        // Add new column
        $this->addSql('ALTER TABLE announcement ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE dates ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE discussions ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE materials ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE todos ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE labels ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE items ADD activation_date datetime NULL AFTER modification_date;');

        $this->addSql('ALTER TABLE zzz_announcement ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_dates ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_discussions ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_materials ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_todos ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_labels ADD activation_date datetime NULL AFTER modification_date;');
        $this->addSql('ALTER TABLE zzz_items ADD activation_date datetime NULL AFTER modification_date;');

        // Set the activation_date, if the modification_date is in the future
        $this->addSql('UPDATE announcement SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE dates SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE discussions SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE materials SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE todos SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE labels SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE items SET activation_date = modification_date WHERE modification_date > NOW();');

        $this->addSql('UPDATE zzz_announcement SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_dates SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_discussions SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_materials SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_todos SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_labels SET activation_date = modification_date WHERE modification_date > NOW();');
        $this->addSql('UPDATE zzz_items SET activation_date = modification_date WHERE modification_date > NOW();');

        // Set the modification date to creation date, if the modification_date is in the future
        $this->addSql("UPDATE announcement SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE dates SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE discussions SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE materials SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE todos SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE labels SET modification_date = creation_date WHERE modification_date > NOW();");

        $this->addSql("UPDATE zzz_announcement SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE zzz_dates SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE zzz_discussions SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE zzz_materials SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE zzz_todos SET modification_date = creation_date WHERE modification_date > NOW();");
        $this->addSql("UPDATE zzz_labels SET modification_date = creation_date WHERE modification_date > NOW();");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
