<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260709201038 extends AbstractMigration
{
    private const KEY = 'ROOM_INVITATION_HELP';

    private const DE = 'Geben Sie hier die E-Mail Adresse für eine neue Einladung an. Der Empfänger erhält eine E-Mail mit einem einmalig nutzbaren Aktivierungscode.';

    private const EN = 'Please give the email address you want to send the invitation to. The invitee will receive an email with a one time usable invitation code.';

    public function getDescription(): string
    {
        return 'Add ROOM_INVITATION_HELP translation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'INSERT INTO translation (context_id, translation_key, translation_de, translation_en) '
            . 'SELECT portal.id, \'' . self::KEY . '\', \'' . self::DE . '\', \'' . self::EN . '\' FROM portal;'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM translation WHERE translation_key = \'' . self::KEY . '\'');
    }
}
