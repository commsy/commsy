<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231027142904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'log_archive -> log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO log(id, ip, agent, timestamp, request, post_content, method, uid, ulogin, cid, module, fct, param, iid, queries, time)
            SELECT id, ip, agent, timestamp, request, post_content, method, uid, ulogin, cid, module, fct, param, iid, queries, time FROM log_archive');
        $this->addSql('ALTER TABLE log DROP uid, DROP module, DROP fct, DROP param, DROP iid, DROP queries, DROP time, CHANGE ip ip VARCHAR(15) NOT NULL, CHANGE agent agent VARCHAR(250) NOT NULL, CHANGE timestamp timestamp DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE request request VARCHAR(2500) NOT NULL, CHANGE post_content post_content VARCHAR(2500) DEFAULT NULL, CHANGE method method VARCHAR(10) NOT NULL');
        $this->addSql('DROP INDEX cid ON log');
        $this->addSql('DROP INDEX timestamp ON log');
        $this->addSql('CREATE INDEX cid_timestamp_idx ON log (cid, timestamp)');
        $this->addSql('DROP TABLE log_archive');
        $this->addSql('DELETE FROM cron_task WHERE name = "App\\Cron\\Tasks\\CronRotateLogs"');

        DbConverter::removeExtra($this->connection, 'room', 'item_id', ['LOGARCHIVE']);
        DbConverter::removeExtra($this->connection, 'room_privat', 'item_id', ['LOGARCHIVE']);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
