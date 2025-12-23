<?php

declare(strict_types=1);

namespace App\Migrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251222201411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove values from portal extra column not needed anymore';
    }

    public function up(Schema $schema): void
    {
        DbConverter::removeExtra($this->connection, 'portal', 'id', [
            'PICTUREFILENAME',
            'WP_PORTAL_ACTIVE',
            'WP_URL',
            'TEMPORARY_LOCK',
            'DAYSBEFORE_EXPIRINGPW_SENDMAIL',
            'PASSWORD_EXPIRATION',
            'PASSWORD_GENERATION',
            'TRY_UNTIL_LOCK',
            'LOCK_INTERVAL',
            'LOCK_TIME',
            'COUNT_ROOM_REDUNDANCY',
            'COUNT_ROOM_PRIVATE',
            'COUNT_ROOM_GROUP',
            'COUNT_ROOM_COMMUNITY',
            'COUNT_ROOM_PROJECT',
            'SHOW_PRIVATE_ROOM_LINK',
            'DESCRIPTION_WELLCOME_2',
            'DESCRIPTION_WELLCOME_1',
            'AUTH_SHOW_LOGIN',
            'IMS_AUTH',
            'DEFAULT_AUTH',
            'USAGE_INFO_FORM_HEADER',
            'USAGE_INFO_HEADER',
            'USAGE_INFO_TEXT',
            'USAGE_INFO_FORM',
            'USAGE_INFO',
            'TIME_IN_FUTURE',
            'AUTHINFO',
            'NUMBERROOMSONHOME'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
