<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220107083123 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Delete item_backup table and related entries';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('DELETE FROM cron_task WHERE name = "App\Cron\Tasks\CronItemBackup";');
        $this->addSql('DROP TABLE item_backup');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('
            create table if not exists item_backup(
                item_id int not null primary key,
                backup_date datetime not null,
                modification_date datetime null,
                title varchar(255) not null,
                description text null,
                public tinyint(11) not null,
                special text charset ucs2 not null
            ) charset = utf8;
        ');
    }
}
