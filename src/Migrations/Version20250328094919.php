<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250328094919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE user SET user.modification_date = user.creation_date WHERE user.modification_date IS NULL');
        $this->addSql('
            ALTER TABLE user
                DROP expire_date,
                CHANGE context_id context_id INT NOT NULL,
                CHANGE creator_id creator_id INT DEFAULT NULL,
                CHANGE creation_date creation_date DATETIME NOT NULL,
                CHANGE modification_date modification_date DATETIME NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE user
                ADD expire_date DATETIME DEFAULT NULL,
                CHANGE context_id context_id INT DEFAULT NULL,
                CHANGE creator_id creator_id INT DEFAULT 0 NOT NULL,
                CHANGE creation_date creation_date DATETIME DEFAULT \'0000-00-00 00:00:00\' NOT NULL,
                CHANGE modification_date modification_date DATETIME DEFAULT NULL
        ');
    }
}
