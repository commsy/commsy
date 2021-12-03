<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 19.04.18
 * Time: 19:09
 */

namespace App\Database;


use App\Database\Resolve\DeleteDatabaseFilesResolution;
use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FixDBFiles implements DatabaseCheck
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(
        EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->entityManager = $entityManager;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    public function getPriority()
    {
        return 100;
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $io->text('Inspecting files');

        $qb = $this->entityManager->getConnection()->createQueryBuilder()
            ->select('f.*', 'i.context_id as portalId')
            ->from('files', 'f')
            ->innerJoin('f', 'items', 'i', 'f.context_id = i.item_id')
            ->where('f.deletion_date IS NULL');

        $files = $qb->execute();
        $discManager = $this->legacyEnvironment->getDiscManager();
        $fileSystem = new Filesystem();

        $problems = [];

        foreach ($files as $num => $file) {
            if ($io->isVerbose()) {
                $io->text('Looking for physical file with id "' . $file['files_id'] . '"');
            }

            $discManager->setPortalID($file['portalId']);
            $discManager->setContextID($file['context_id']);
            $filePath = $discManager->getFilePath() . $file['files_id'] . '.' . $this->getFileExtension($file);

            $conn = $this->entityManager->getConnection();

            $filesQb = $conn->createQueryBuilder()
                ->delete('files')
                ->where('files.files_id = :fileId');

            $ilfQb = $conn->createQueryBuilder()
                ->delete('item_link_file')
                ->where('item_link_file.file_id = :fileId');

            if (!$fileSystem->exists($filePath)) {
                $io->warning('Missing physical file - "' . $file['files_id'] . '" - " was expected to be found in "' . $filePath);

                $fileId = $file['files_id'];
                $filesQb->setParameter(":fileId", $fileId);
                $ilfQb->setParameter(":fileId", $fileId);

//                    $filesQb->execute();
//                    $ilfQb->execute();
            }
        }

        return true;
    }

    private function getFileExtension($file)
    {
        $filename = utf8_encode(rawurldecode($file['filename']));

        if (!empty($filename)) {
            return cs_strtolower(mb_substr(strrchr($filename, '.'), 1));
        }
    }
}