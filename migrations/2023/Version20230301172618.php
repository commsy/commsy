<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301172618 extends AbstractMigration implements ContainerAwareInterface
{
    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDescription(): string
    {
        return 'add portal id to files / clear entries / fix paths / move userroom files';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM files WHERE files.context_id IS NULL');
        $this->addSql('DELETE FROM files WHERE files.filename NOT LIKE "%.%"');
        $this->addSql('ALTER TABLE files ADD portal_id INT AFTER files_id, CHANGE context_id context_id INT NOT NULL, CHANGE creation_date creation_date DATETIME NOT NULL, CHANGE size size INT DEFAULT NULL, CHANGE extras extras LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059B887E1DD FOREIGN KEY (portal_id) REFERENCES portal (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6354059B887E1DD ON files (portal_id)');

        // set portal id for project / community / privateroom / grouproom
        $this->addSql('
            UPDATE files AS f
            INNER JOIN items AS i ON f.context_id = i.item_id
            SET portal_id = i.context_id
            WHERE
            i.type IN (\'project\', \'community\', \'privateroom\', \'grouproom\')
        ');

        // set portal id for userroom
        $this->addSql('
            UPDATE files AS f
            INNER JOIN items AS i ON f.context_id = i.item_id
            INNER JOIN items AS i2 ON i.context_id = i2.item_id
            SET portal_id = i2.context_id
            WHERE
            i.type = \'userroom\'
        ');

        $this->addSql('DELETE FROM files WHERE files.portal_id IS NULL');
        $this->addSql('DELETE FROM files WHERE files.filepath = ""');
        $this->addSql('ALTER TABLE files MODIFY portal_id INT NOT NULL');
    }

    public function postUp(Schema $schema): void
    {
        $this->fixPath();
        $this->moveUserroomFiles();
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }

    private function fixPath()
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $qb = $queryBuilder
            ->select('f.files_id', 'f.portal_id', 'f.context_id', 'f.filename', 'f.filepath')
            ->from('files', 'f')
            ->where('f.filepath != ""')
            ->andWhere('f.filepath NOT LIKE CONCAT("%", f.files_id, "%")');

        $files = $qb->executeQuery()->fetchAllAssociative();

        foreach ($files as $file) {
            // fix wrong filepath
            if ($file['filepath']) {
                $lastSubstringBeginningWithDot = strrchr($file['filename'], '.');
                if ($lastSubstringBeginningWithDot) {
                    $fileExtension = substr($lastSubstringBeginningWithDot, 1);

                    $filePath = DbConverter::getFilePath((int) $file['portal_id'], (int) $file['context_id']);
                    $filePath .= $file['files_id'];
                    $filePath .= '.' . $fileExtension;

                    $filePath = stristr($filePath, 'files');

                    $this->connection->update('files', [
                        'filepath' => $filePath,
                    ], [
                        'files_id' => $file['files_id'],
                    ]);
                }
            }
        }
    }

    private function moveUserroomFiles()
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('f.context_id', 'f.filepath', 'i.context_id as pid')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
            ->where('i.type = "userroom"');
        $files = $qb->executeQuery()->fetchAllAssociative();

        $projectDirectory = $this->container->getParameter('kernel.project_dir');
        $filesDirectory = $this->container->getParameter('files_directory');

        foreach ($files as $file) {
            $filesystem = new Filesystem();

            $directory = $filesDirectory . '/' . $file['pid'];
            if ($filesystem->exists($directory)) {
                $contextLength = strlen($file['context_id']);
                $srcFolder = $directory . '/';
                for ($i = 0; $i < $contextLength; ++$i) {
                    if ($i > 0 && 0 == $i % 4) {
                        $srcFolder .= '/';
                    }

                    $srcFolder .= $file['context_id'][$i];
                }
                $srcFolder .= '_';

                if ($filesystem->exists($srcFolder)) {
                    $finderFileNames = (new Finder())->files()
                        ->in($srcFolder);

                    if ($finderFileNames->hasResults()) {
                        foreach ($finderFileNames as $finderFile) {
                            $filesystem->copy($finderFile->getPathname(), $projectDirectory . '/' . $file['filepath']);
                            $filesystem->remove($finderFile->getPathname());
                        }
                    }

                    $filesystem->remove($srcFolder);
                }

                $finderDirectory = (new Finder())->files()->in($directory);
                if (!$finderDirectory->hasResults()) {
                    $filesystem->remove($directory);
                }
            }
        }
    }
}
