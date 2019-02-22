<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20160718213927 extends AbstractMigration implements ContainerAwareInterface
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
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files ADD filepath VARCHAR(255) NOT NULL AFTER filename');
        $this->addSql('ALTER TABLE zzz_files ADD filepath VARCHAR(255) NOT NULL AFTER filename');
    }

    public function postUp(Schema $schema)
    {
        $this->write('updating file paths in files');
        $this->updateFilePath('files');

        $this->write('updating file paths in zzz_files');
        $this->updateFilePath('zzz_files');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE files DROP COLUMN filepath');
        $this->addSql('ALTER TABLE zzz_files DROP COLUMN filepath');
    }

    private function updateFilePath($table)
    {
        $legacyEnvironment = $this->container->get('commsy_legacy.environment')->getEnvironment();
        $discManager = $legacyEnvironment->getDiscManager();

        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('f.files_id', 'f.context_id', 'f.filename', 'i.context_id as portal_id')
            ->from($table, 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id');

        $files = $qb->execute();

        foreach ($files as $file) {
            $fileContextId = $file['context_id'];

            if ($fileContextId) {
                $portalId = $file['portal_id'];

                $lastSubstringBeginningWithDot = strrchr($file['filename'], '.');
                if ($lastSubstringBeginningWithDot) {
                    $fileExtension = substr($lastSubstringBeginningWithDot, 1);

                    $filePath = $discManager->getFilePath($portalId, $fileContextId);
                    $filePath .= $file['files_id'];
                    $filePath .= '.' . $fileExtension;

                    $filePath = stristr($filePath, 'files');

                    $this->connection->update($table, [
                        'filepath' => $filePath,
                    ], [
                        'files_id' => $file['files_id'],
                    ]);
                }
            }
        }
    }
}
