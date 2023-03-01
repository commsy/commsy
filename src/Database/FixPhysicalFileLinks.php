<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Database;

use App\Services\LegacyEnvironment;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class FixPhysicalFileLinks implements DatabaseCheck
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        private EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment,
        private LoggerInterface $cleanupLogger
    ) {
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

        $files = $qb->executeQuery()->fetchAllAssociative();
        $discManager = $this->legacyEnvironment->getDiscManager();
        $fileSystem = new Filesystem();

        foreach ($files as $file) {
            $discManager->setPortalID($file['portalId']);
            $discManager->setContextID($file['context_id']);
            $filePath = $discManager->getFilePath().$file['files_id'].'.'.$this->getFileExtension($file);

            $conn = $this->entityManager->getConnection();

            $filesQb = $conn->createQueryBuilder()
                ->delete('files')
                ->where('files.files_id = :fileId');

            $ilfQb = $conn->createQueryBuilder()
                ->delete('item_link_file')
                ->where('item_link_file.file_id = :fileId');

            if (!$fileSystem->exists($filePath)) {
                if ($io->isVerbose()) {
                    $io->note('Deleting file and link in database with id "'.$file['files_id'].'" - file was expected to be found in "'.$filePath.'"');
                }

                $fileId = $file['files_id'];
                $filesQb->setParameter(':fileId', $fileId);
                $ilfQb->setParameter(':fileId', $fileId);

                $conn->beginTransaction();
                try {
                    $this->cleanupLogger->info('Deleting file and link in database with id "'.$file['files_id'].'" - file was expected to be found in "'.$filePath.'"');
//                    $filesQb->execute();
//                    $ilfQb->execute();
                } catch (Exception) {
                    $conn->rollBack();
                }
            }
        }

        return true;
    }

    private function getFileExtension($file): string
    {
        $filename = utf8_encode(rawurldecode($file['filename']));

        if (!empty($filename)) {
            return cs_strtolower(mb_substr(strrchr($filename, '.'), 1));
        }

        return '';
    }
}
