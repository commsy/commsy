<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310120500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE accounts SET language = "de" WHERE language = "de"');
        $this->addSql('UPDATE accounts SET language = "en" WHERE language = "en"');
        $this->addSql('UPDATE accounts SET language = "browser" WHERE language = "browser"');

        $this->addSql('UPDATE accounts SET language = "browser" WHERE language != "de" AND language != "en" AND language != "browser"');
    }

    public function down(Schema $schema): void
    {
    }
}
