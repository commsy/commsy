<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160719021757 extends AbstractMigration implements ContainerAwareInterface
{

    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

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
            $extras = $this->convertToPHPValue($material['extras']);

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

    private function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        if (empty($value)) {
            return array();
        }

        $value = preg_replace_callback('/s:(\d+):"(.*?)";(?=\}|i|s|a)/s', function($match) {
            $length = strlen($match[2]);
            $data = $match[2];

            return "s:$length:\"$data\";";
        }, $value );

        $val = @unserialize($value);
        if ($val === false && $value != 'b:0;') {
            // TODO: this is temporary, we need to fix db entries
            return array();

            //throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}
