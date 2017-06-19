<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170616103508 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            CREATE TABLE calendars (
                id int(11) NOT NULL AUTO_INCREMENT,
                context_id int(11) NOT NULL,
                title varchar(255) NOT NULL,
                color varchar(255) NOT NULL,
                external_url varchar(255) NULL,
                default_calendar TINYINT NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            CREATE TABLE zzz_calendars (
                id int(11) NOT NULL AUTO_INCREMENT,
                context_id int(11) NOT NULL,
                title varchar(255) NOT NULL,
                color varchar(255) NOT NULL,
                external_url varchar(255) NULL,
                default_calendar TINYINT NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ');

        $this->addSql('
            ALTER TABLE dates
            ADD calendar_id int(11) NULL;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            ADD calendar_id int(11) NULL;
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE IF EXISTS calendars');

        $this->addSql('DROP TABLE IF EXISTS zzz_calendars');

        $this->addSql('
            ALTER TABLE dates
            DROP calendar_id;
        ');

        $this->addSql('
            ALTER TABLE zzz_dates
            DROP calendar_id;
        ');
    }

    public function postUp(Schema $schema) {
        $this->migrateColorsToCalendars('room');

        $this->migrateColorsToCalendars('zzz_room');
    }

    private function migrateColorsToCalendars ($dTable) {
        $queryBuilder = $this->connection->createQueryBuilder();

        // find all rooms
        $qb = $queryBuilder
            ->select('r.item_id')
            ->from($dTable, 'r');
        $rooms = $qb->execute();

        // Add calendars for each room -> one default + one for each color
        foreach ($rooms as $room) {
            // default calendar

            $this->write('updating calendars for room '.$room['item_id']);

            $queryBuilder
                ->insert('calendars')
                ->values(
                    array(
                        'id' => '?',
                        'context_id' => '?',
                        'title' => '?',
                        'color' => '?',
                        'external_url' => '?',
                        'default_calendar' => '?'
                    )
                )
                ->setParameter(0, '')
                ->setParameter(1, $room['item_id'])
                ->setParameter(2, 'Calendar')
                ->setParameter(3, '#ffffff')
                ->setParameter(4, '')
                ->setParameter(5, '1')
                ->execute();
            ;

            $colorArray = [
                            '#999999' => 'Grey',
                            '#CC0000' => 'Red',
                            '#FF6600' => 'Orange',
                            '#FFCC00' => 'Gold',
                            '#FFFF66' => 'Yellow',
                            '#33CC00' => 'Green',
                            '#00CCCC' => 'Turquoise',
                            '#3366FF' => 'Blue',
                            '#6633FF' => 'Purple',
                            '#CC33CC' => 'Magenta'
                          ];
            foreach ($colorArray as $color => $name) {
                $queryBuilder
                    ->insert('calendars')
                    ->values(
                        array(
                            'id' => '?',
                            'context_id' => '?',
                            'title' => '?',
                            'color' => '?',
                            'external_url' => '?',
                            'default_calendar' => '?'
                        )
                    )
                    ->setParameter(0, '')
                    ->setParameter(1, $room['item_id'])
                    ->setParameter(2, $name)
                    ->setParameter(3, $color)
                    ->setParameter(4, '')
                    ->setParameter(5, '0')
                    ->execute();
                ;
            }
        }
    }
}
