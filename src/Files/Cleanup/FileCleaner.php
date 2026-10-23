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

declare(strict_types=1);

namespace App\Files\Cleanup;

use App\Entity\Files;
use App\Files\FileDeleter;
use App\Files\FilesDirectory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Acts on an audit, and never removes anything for good: rows are marked
 * deleted, so the regular final deletion removes them and their files after
 * the retention and a restore stays possible until then; files without a row
 * are moved into the {@see FileQuarantine}.
 */
readonly class FileCleaner
{
    public function __construct(
        private FileDeleter $fileDeleter,
        private FileQuarantine $quarantine,
        private FilesDirectory $filesDirectory,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $cleanupLogger,
    ) {
    }

    /**
     * @param list<FindingKind> $kinds          what to act on; unknown files never are
     * @param bool              $ignoreWarnings act even where the audit looks implausible
     *
     * @return array{done: array<string, int>, skipped: array<string, string>, run: ?string}
     */
    public function clean(AuditResult $audit, array $kinds, bool $ignoreWarnings = false, DateTimeImmutable $now = new DateTimeImmutable()): array
    {
        $done = [];
        $skipped = [];
        $run = null;

        foreach ($kinds as $kind) {
            $findings = $audit->of($kind);
            if ([] === $findings || FindingKind::Unknown === $kind) {
                continue;
            }

            if (FindingKind::MissingOnDisk === $kind && $audit->storageSuspicious && !$ignoreWarnings) {
                $skipped[$kind->value] = 'so many files are missing that the storage may not be mounted';
                continue;
            }
            if ($kind->concernsDisk() && $audit->databaseSuspicious && !$ignoreWarnings) {
                $skipped[$kind->value] = 'the disk does not fit the database, which may be the wrong or an older one';
                continue;
            }

            $done[$kind->value] = match ($kind) {
                FindingKind::UnlinkedRow => $this->fileDeleter->softDeleteUnlinkedFiles(FileAudit::MIN_AGE_HOURS),
                FindingKind::DeadLinksRow => $this->endDeadLinksRows($findings),
                FindingKind::MissingOnDisk => $this->endMissingOnDisk($findings),
                default => $this->quarantine($findings, $run ??= $this->quarantine->begin($now), $now),
            };
            $this->cleanupLogger->info("File cleanup: {$kind->value}: {$done[$kind->value]} done");
        }

        return ['done' => $done, 'skipped' => $skipped, 'run' => $run];
    }

    /**
     * @param list<Finding> $findings
     */
    private function endDeadLinksRows(array $findings): int
    {
        return $this->entityManager->wrapInTransaction(function () use ($findings): int {
            $fileIds = array_map(static fn (Finding $finding): int => (int) $finding->fileId, $findings);
            $fileIds = [...$fileIds, ...$this->fileDeleter->detachFromGoneEntries()];

            return $this->fileDeleter->softDeleteFilesWithoutLiveLinks($fileIds, null);
        });
    }

    /**
     * @param list<Finding> $findings
     */
    private function endMissingOnDisk(array $findings): int
    {
        $fileIds = array_map(static fn (Finding $finding): int => (int) $finding->fileId, $findings);
        $this->fileDeleter->endFilesMissingOnDisk($fileIds);

        return \count($fileIds);
    }

    /**
     * @param list<Finding> $findings
     */
    private function quarantine(array $findings, string $run, DateTimeImmutable $now): int
    {
        $moved = 0;
        foreach ($findings as $finding) {
            // exactly what the audit looked at; a directory is never re-read
            $paths = FindingKind::OrphanedDirectory === $finding->kind ? $finding->files : [(string) $finding->path];

            foreach ($paths as $path) {
                $reason = $this->reasonToKeep($path, $now);
                if (null !== $reason) {
                    $this->cleanupLogger->info("File cleanup: kept '$path': $reason");
                    continue;
                }

                try {
                    $this->quarantine->move($run, $path, $finding->kind->value.': '.$finding->reason);
                    ++$moved;
                } catch (RuntimeException $exception) {
                    $this->cleanupLogger->warning("File cleanup: kept '$path': {$exception->getMessage()}");
                }
            }
        }

        return $moved;
    }

    /**
     * Looks again right before the move: the file may have changed or got a
     * row since the audit.
     */
    private function reasonToKeep(string $path, DateTimeImmutable $now): ?string
    {
        $absolute = $this->filesDirectory->root().'/'.$path;
        clearstatcache(true, $absolute);
        if (!is_file($absolute) || is_link($absolute)) {
            return 'no longer a file';
        }
        if (filemtime($absolute) >= $now->modify('-'.FileAudit::MIN_AGE_HOURS.' hours')->getTimestamp()) {
            return 'changed since the audit';
        }
        if (1 === preg_match('/^(\d+)\./', basename($path), $match) && $this->fileRowExists((int) $match[1])) {
            return 'a file row with this id exists now';
        }

        return null;
    }

    /**
     * Asks the database, not the identity map.
     */
    private function fileRowExists(int $fileId): bool
    {
        return 0 < (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(f.filesId)')
            ->from(Files::class, 'f')
            ->where('f.filesId = :fileId')
            ->setParameter('fileId', $fileId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
