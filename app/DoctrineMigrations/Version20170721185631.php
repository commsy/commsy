<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170721185631 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE hash
            ADD caldav char(32) NULL;
        ');

        $this->addSql('CREATE INDEX caldav ON hash(caldav)');

        $this->addSql('
            ALTER TABLE zzz_hash
            ADD caldav char(32) NULL;
        ');

        $this->addSql('CREATE INDEX caldav ON zzz_hash(caldav)');

        $this->addSql('
            ALTER TABLE calendars
            ADD synctoken INT(11) NOT NULL DEFAULT 0;
        ');
    }

    public function postUp(Schema $schema) {
        $this->generateCalDAVHashes('hash');

        $this->generateCalDAVHashes('zzz_hash');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('
            ALTER TABLE hash
            DROP caldav;
        ');

        //$this->removeIndex('caldav', 'hash');

        $this->addSql('
            ALTER TABLE zzz_hash
            DROP caldav;
        ');

        //$this->removeIndex('caldav', 'zzz_hash');

        $this->addSql('
            ALTER TABLE calendars
            DROP synctoken;
        ');
    }

    /**
     * Removes an index after checking for existence
     *
     * @param $indexName name of the index to remove
     * @param $tableName name of the table containing the index
     */
    private function removeIndex($indexName, $tableName)
    {
        $schemaManager = $this->connection->getSchemaManager();
        $tableIndexes = $schemaManager->listTableIndexes($tableName);

        $filteredIndexes = array_filter($tableIndexes, function($index) use ($indexName) {
            return $index->getName() === $indexName;
        });

        if (!empty($filteredIndexes)) {
            $this->addSql('DROP INDEX ' . $indexName . ' ON ' . $tableName);
        }
    }

    private function generateCalDAVHashes ($dTable) {
        // get all hashes
        $queryBuilderHash = $this->connection->createQueryBuilder('CommsyBundle:Hash');
        $hashes = $queryBuilderHash->select('*')
            ->from($dTable)
            ->execute();

        $queryBuilderUser = $this->connection->createQueryBuilder('CommsyBundle:User');
        foreach ($hashes as $hash) {
            $users = $queryBuilderUser->select('*')
                ->from('user', 'u')
                ->where('u.item_id = :item_id')
                ->setParameter('item_id', $hash['user_item_id'])
                ->execute();

            foreach ($users as $user) {
                $queryBuilderHash->update('hash', 'h')
                    ->set("h.caldav", ":hash")
                    ->where("h.user_item_id = :itemId")
                    ->setParameter("hash", md5($user['user_id'].':CommSy:'.$hash['ical']))
                    ->setParameter("itemId", $user['item_id'])
                    ->execute();
            }
        }
    }
}
