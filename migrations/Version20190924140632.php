<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190924140632 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Modify auth sources to extract information from extras into columns';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('alter table auth_source add enabled tinyint default 1 not null after title;');
        $this->addSql('alter table auth_source add type enum(\'local\', \'oidc\', \'ldap\') default \'local\' not null after title;');
        $this->addSql('alter table auth_source add `default` tinyint default 0 not null;');
        $this->addSql('alter table auth_source add add_account tinyint default 0 not null;');
        $this->addSql('alter table auth_source add change_username tinyint default 0 not null;');
        $this->addSql('alter table auth_source add delete_account tinyint default 0 not null;');
        $this->addSql('alter table auth_source add change_userdata tinyint default 0 not null;');
        $this->addSql('alter table auth_source add change_password tinyint default 0 not null;');
        $this->addSql('alter table auth_source add create_room tinyint default 1 not null;');
    }

    public function postUp(Schema $schema): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $qb = $queryBuilder
            ->select('as.id', 'as.extras')
            ->from('auth_source', '`as`');
        $authSources = $qb->execute();

        foreach ($authSources as $authSource) {
            $extras = DbConverter::convertToPHPValue($authSource['extras']);

            // TODO: Migrate other source types
            $type = null;
            switch ($extras['SOURCE']) {
                case 'MYSQL':
                    $type = 'local';
                    break;

                case 'OpenID Connect':
                    $type = 'oidc';
                    break;

                case 'LDAP':
                    $type = 'ldap';
                    break;
            }

            $this->connection->update('auth_source', [
                'enabled' => $extras['SHOW'] == '1' ? 1 : 0,
                'type' => $type,
                '`default`' => ($extras['COMMSY_DEFAULT'] ?? 0) == 1 ? 1 : 0,
                'add_account' => ($extras['CONFIGURATION']['ADD_ACCOUNT'] ?? 0) == 1 ? 1 : 0,
                'delete_account' => ($extras['CONFIGURATION']['DELETE_ACCOUNT'] ?? 0) == 1 ? 1 : 0,
                'change_username' => ($extras['CONFIGURATION']['CHANGE_USERID'] ?? 0) == 1 ? 1 : 0,
                'change_userdata' => ($extras['CONFIGURATION']['CHANGE_USERDATA'] ?? 0) == 1 ? 1 : 0,
                'change_password' => ($extras['CONFIGURATION']['CHANGE_PASSWORD'] ?? 0) == 1 ? 1 : 0,
                'create_room' => ($extras['USER_IS_ALLOWED_TO_CREATE_CONTEXT'] ?? 1) == 1 ? 1 : 0,
            ], [
                'id' => $authSource['id'],
            ]);

            unset($extras['SOURCE']);
            unset($extras['SHOW']);
            unset($extras['COMMSY_DEFAULT']);
            unset($extras['CONFIGURATION']['ADD_ACCOUNT']);
            unset($extras['CONFIGURATION']['DELETE_ACCOUNT']);
            unset($extras['CONFIGURATION']['CHANGE_USERID']);
            unset($extras['CONFIGURATION']['CHANGE_USERDATA']);
            unset($extras['CONFIGURATION']['CHANGE_PASSWORD']);
            unset($extras['USER_IS_ALLOWED_TO_CREATE_CONTEXT']);

            $this->connection->update('auth_source', [
                'extras' => (sizeof($extras) === 1 && sizeof($extras['CONFIGURATION']) === 0) ? null : serialize($extras),
            ], [
                'id' => $authSource['id'],
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();
    }
}
