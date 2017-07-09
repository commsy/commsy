<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170709123535 extends AbstractMigration
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

        $this->removeIndex('caldav', 'hash');

        $this->addSql('
            ALTER TABLE zzz_hash
            DROP caldav;
        ');

        $this->removeIndex('caldav', 'zzz_hash');
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
}
