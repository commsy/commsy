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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

final class FixFilepaths extends GeneralCheck
{
    private readonly cs_environment $legacyEnvironment;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        parent::__construct($entityManager);
    }

    public function resolve(SymfonyStyle $io): bool
    {
        $io->text('Inspecting filepaths in files table');

        $conn = $this->entityManager->getConnection();

        $qb = $conn->createQueryBuilder()
            ->select('f.files_id', 'f.portal_id', 'f.context_id', 'f.filepath')
            ->from('files', 'f')
            ->where('f.deletion_date IS NULL');

        $rows = $qb->executeQuery()->fetchAllAssociative();
        if (empty($rows)) {
            $io->success('No files found to inspect');
            return true;
        }

        $discManager = $this->legacyEnvironment->getDiscManager();

        $progressBar = new ProgressBar($io, count($rows));
        $progressBar->start();

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $progressBar->advance();

            $filesId = (int) $row['files_id'];
            $contextId = (int) $row['context_id'];
            $portalId = (int) $row['portal_id'];
            $currentFilepath = (string) ($row['filepath'] ?? '');

            $ext = strtolower((string) pathinfo($currentFilepath, PATHINFO_EXTENSION));
            if ($ext === '') {
                $skipped++;
                if ($io->isVerbose()) {
                    $io->note('Skipping files_id='.$filesId.' (no extension detected)');
                }
                continue;
            }

            $diskFilename = $discManager->getCurrentFileName($filesId, $ext);
            $expectedRelativePath = $discManager->getRelativeFilePath($portalId, $contextId, $diskFilename);

            if ($expectedRelativePath === $currentFilepath) {
                continue;
            }

            if ($io->isVerbose()) {
                $io->text(sprintf(
                    'Fixing files_id=%d: "%s" -> "%s"',
                    $filesId,
                    $currentFilepath,
                    $expectedRelativePath
                ));
            }

            $updateQb = $conn->createQueryBuilder();
            $updateQb
                ->update('files')
                ->set('filepath', ':filepath')
                ->where('files_id = :id')
                ->setParameter('filepath', $expectedRelativePath)
                ->setParameter('id', $filesId)
                ->executeStatement();

            $updated++;
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success(sprintf('Done. Updated: %d, skipped (no ext): %d', $updated, $skipped));

        return true;
    }
}
