<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Contract\ParameterBagAwareInterface;
use App\Utils\DbConverter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301172618 extends AbstractMigration implements ParameterBagAwareInterface
{
    private ParameterBagInterface $parameterBag;

    public function setParameterBag(ParameterBagInterface $parameterBag): void
    {
        $this->parameterBag = $parameterBag;
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
        $this->moveRoomFiles();
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
            ->where('f.filepath NOT LIKE CONCAT("%", f.files_id, "%")');

        $files = $qb->executeQuery()->fetchAllAssociative();

        foreach ($files as $file) {
            // fix wrong filepath
            $lastSubstringBeginningWithDot = strrchr((string) $file['filename'], '.');
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

    private function moveRoomFiles()
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('p.id')
            ->from('portal', 'p');

        $exclude = $qb->executeQuery()->fetchFirstColumn();
        $exclude[] = '99';

        $filesDirectory = $this->parameterBag->get('files_directory');

        $filesystem = new Filesystem();

        $dirFinder = new Finder();
        $dirFinder
            ->directories()
            ->in($filesDirectory)
            ->depth('== 0')
            ->name('/\d+/')
            ->exclude($exclude);

        $directories = iterator_to_array($dirFinder);
        /** @var SplFileInfo $directory */
        foreach ($directories as $directory) {
            $directoryPath = $directory->getPathname();
            $fileFinder = new Finder();
            $fileFinder
                ->files()
                ->in($directoryPath);

            $firstFolder = $directory->getBasename();

            $qb = $this->connection->createQueryBuilder()
                ->select('i.context_id')
                ->from('items', 'i')
                ->where('i.item_id = :itemId')
                ->setParameter('itemId', $firstFolder);

            $portalId = $qb->executeQuery()->fetchOne();

            if ($portalId) {
                /** @var SplFileInfo $file */
                foreach ($fileFinder as $file) {
                    $oldPathName = $file->getPathname();
                    $newPathName = "$filesDirectory/$portalId/{$file->getRelativePathname()}";

                    $this->write("Moving $oldPathName => $newPathName");
                    $filesystem->mkdir("$filesDirectory/$portalId/{$file->getRelativePath()}");
                    $filesystem->rename($oldPathName, $newPathName);
                }
            }

            $filesystem->remove($directoryPath);
        }
    }
}
