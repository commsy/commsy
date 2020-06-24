<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;

use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160719021757 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->write("updating workflow states in materials");
        $this->updateWorkflowStates("materials");

        $this->write("updating workflow states in zzz_materials");
        $this->updateWorkflowStates("zzz_materials");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        $this->throwIrreversibleMigrationException();

    }

    private function updateWorkflowStates($table)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        
        $qb = $queryBuilder
            ->select('m.item_id', 'm.version_id', 'm.extras')
            ->from($table, 'm');

        $materials = $qb->execute();

        foreach ($materials as $material) {
            $extras = DbConverter::convertToPHPValue($material['extras']);

            $update = false;

            if (isset($extras['WORKFLOWVALIDITY'])) {
                if ($extras['WORKFLOWVALIDITY'] == 'on') {
                    $extras['WORKFLOWVALIDITY'] = '1';
                    $update = true;
                } else if ($extras['WORKFLOWVALIDITY'] == '0') {
                    $extras['WORKFLOWVALIDITY'] = '-1';
                    $update = true;
                }
            }

            if (isset($extras['WORKFLOWRESUBMISSION'])) {
                if ($extras['WORKFLOWRESUBMISSION'] == 'on') {
                    $extras['WORKFLOWRESUBMISSION'] = '1';
                    $update = true;
                } else if ($extras['WORKFLOWRESUBMISSION'] == '0') {
                    $extras['WORKFLOWRESUBMISSION'] = '-1';
                    $update = true;
                }
            }

            if ($update) {
                $this->connection->update($table, [
                    'extras' => serialize($extras),
                ], [
                    'item_id' => $material['item_id'],
                    'version_id' => $material['version_id'],
                ]);
            }
        }
    }
}
