<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240823135015 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate reader table';
    }

    public function up(Schema $schema): void
    {
        // This will delete all duplicates, but keep the newest one
        $this->addSql('CREATE TEMPORARY TABLE reader_tmp LIKE reader');
        $this->addSql('
            INSERT INTO reader_tmp
            SELECT item_id, version_id, user_id, MAX(read_date) AS read_date FROM reader
            GROUP BY item_id, version_id, user_id
        ');
        $this->addSql('TRUNCATE TABLE reader');
        $this->addSql('INSERT INTO reader SELECT * FROM reader_tmp');
        $this->addSql('DROP TEMPORARY TABLE reader_tmp');

        $this->addSql('ALTER TABLE reader ADD id INT AUTO_INCREMENT NOT NULL FIRST, CHANGE item_id item_id INT NOT NULL, CHANGE version_id version_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE read_date read_date DATETIME NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('CREATE INDEX reader_user_idx ON reader (user_id)');
        $this->addSql('CREATE UNIQUE INDEX reader_unique_idx ON reader (item_id, version_id, user_id, read_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX reader_user_idx ON reader');
        $this->addSql('DROP INDEX reader_unique_idx ON reader');
        $this->addSql('ALTER TABLE reader DROP id, CHANGE item_id item_id INT DEFAULT 0 NOT NULL, CHANGE version_id version_id INT DEFAULT 0 NOT NULL, CHANGE user_id user_id INT DEFAULT 0 NOT NULL, CHANGE read_date read_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL');
        $this->addSql('ALTER TABLE reader ADD PRIMARY KEY (item_id, version_id, user_id, read_date)');
    }
}
