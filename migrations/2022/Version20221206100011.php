<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221206100011 extends AbstractMigration
{
    private const DE = "Ein frei wählbarer, eindeutiger Benutzername.";
    private const EN = "An arbitrary, unique username.";
    private const KEY = "REGISTRATION_USERNAME_HELP";

    public function getDescription(): string
    {
        return 'Add ' . self::KEY . ' translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO translation (context_id, translation_key, translation_de, translation_en) SELECT portal.id, \'REGISTRATION_USERNAME_HELP\', \'' . self::DE . '\', \'' . self::EN . '\' FROM portal;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translation WHERE translation_key = \'' . self::KEY . '\'');
    }
}
