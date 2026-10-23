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
use App\Entity\Items;
use App\Entity\Portal;
use App\Entity\Room;
use App\Entity\Server;
use App\Files\FileDeleter;
use App\Files\FilesDirectory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Compares the `files` table, its links and the files directory and reports
 * what does not fit. It only reads; {@see FileCleaner} acts on the result.
 *
 * Whatever a row or a setting still refers to is never reported for the
 * disk, whichever directory it lies in. Besides, two warnings stop the
 * cleanup from acting on a picture that cannot be right: many files missing
 * at once (a storage not mounted), and much of the disk unknown to the
 * database — or ids on disk the database has never handed out (the wrong or
 * an older database).
 */
readonly class FileAudit
{
    /** Younger files and rows may belong to an upload still under way. */
    public const int MIN_AGE_HOURS = 24;

    /** Top-level folders that hold no entry content and are never looked into. */
    public const array SKIPPED = ['temp', FileQuarantine::DIRECTORY];

    /**
     * More missing files than this share of a portal's live rows look like a
     * storage that is not mounted rather than like lost files.
     */
    public const float MISSING_SHARE_LIMIT = 0.02;

    /**
     * More files without any reference than this share of the files examined
     * look like the wrong database rather than like leftovers.
     */
    public const float STRAY_SHARE_LIMIT = 0.5;

    /** Below this many, a share says nothing. */
    private const int SHARE_MINIMUM = 20;

    private const string UPLOAD = '/^(\d+)\.[^\/]*$/';
    private const string OLD_UPLOAD = '/^cid\d+_(\d+)_/';
    private const string GENERATED_IMAGE = '/^cid\d+_/';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileDeleter $fileDeleter,
        private FilesDirectory $filesDirectory,
    ) {
    }

    public function audit(DateTimeImmutable $now = new DateTimeImmutable()): AuditResult
    {
        if (!is_dir($this->filesDirectory->root())) {
            throw new RuntimeException("The files directory '{$this->filesDirectory->root()}' does not exist.");
        }

        $references = new FileReferences($this->entityManager, $this->filesDirectory)->read();
        $cutoff = $now->modify('-'.self::MIN_AGE_HOURS.' hours');

        $missing = $this->missingOnDisk($references, $cutoff);
        $disk = $this->walkFilesDirectory($references, $cutoff);

        return new AuditResult(
            [
                ...$this->rowFindings(FindingKind::UnlinkedRow, $this->fileDeleter->findUnlinkedFiles(self::MIN_AGE_HOURS), 'no entry was ever linked to it'),
                ...$this->rowFindings(FindingKind::DeadLinksRow, $this->fileDeleter->findFilesWithoutLiveEntry(), 'every entry it is linked to is gone'),
                ...$missing['findings'],
                ...$disk['findings'],
            ],
            $missing['suspicious'],
            $disk['suspicious'],
        );
    }

    /**
     * @param list<array{fileId: int, filepath: string, bytes: int}> $rows
     *
     * @return list<Finding>
     */
    private function rowFindings(FindingKind $kind, array $rows, string $reason): array
    {
        return array_map(
            static fn (array $row): Finding => new Finding($kind, $row['fileId'], $row['filepath'], $row['bytes'], $reason),
            $rows,
        );
    }

    /**
     * Live rows, old enough, whose file is neither where the row says nor
     * anywhere the disc manager would put an upload of that id.
     *
     * @return array{findings: list<Finding>, suspicious: bool}
     */
    private function missingOnDisk(FileReferences $references, DateTimeImmutable $cutoff): array
    {
        $findings = [];
        $live = [];
        $missing = [];
        foreach ($references->liveRows() as $row) {
            $live[$row['portalId']] = ($live[$row['portalId']] ?? 0) + 1;
            if ($row['createdAt'] >= $cutoff || $this->isOnDisk($row)) {
                continue;
            }

            $missing[$row['portalId']] = ($missing[$row['portalId']] ?? 0) + 1;
            $findings[] = new Finding(FindingKind::MissingOnDisk, $row['fileId'], null, $row['bytes'], 'the file is not on disk');
        }

        // one storage not mounted shows as one portal missing much
        $suspicious = false;
        foreach ($missing as $portalId => $count) {
            $suspicious = $suspicious || $count > max(self::SHARE_MINIMUM, $live[$portalId] * self::MISSING_SHARE_LIMIT);
        }

        return ['findings' => $findings, 'suspicious' => $suspicious];
    }

    /**
     * @param array{fileId: int, paths: list<string>, directories: list<string>} $row
     */
    private function isOnDisk(array $row): bool
    {
        foreach ($row['paths'] as $path) {
            if (is_file($path)) {
                return true;
            }
        }
        foreach ($row['directories'] as $directory) {
            if ([] !== (glob($directory.'/'.$row['fileId'].'.*', GLOB_NOSORT) ?: [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{findings: list<Finding>, suspicious: bool}
     */
    private function walkFilesDirectory(FileReferences $references, DateTimeImmutable $cutoff): array
    {
        $root = $this->filesDirectory->root();

        /** @var array<string, list<SplFileInfo>> $byDirectory direct files per directory */
        $byDirectory = [];
        $findings = [];
        $finder = new Finder()
            ->in($root)
            ->ignoreDotFiles(false)
            ->ignoreVCS(false)
            ->exclude(self::SKIPPED);
        foreach ($finder as $entry) {
            if ($entry->isLink()) {
                $findings[] = new Finding(FindingKind::Unknown, null, $entry->getRelativePathname(), 0, 'a link');
                continue;
            }
            if ($entry->isFile() && $entry->getMTime() < $cutoff->getTimestamp()) {
                // a key of digits only would turn into an int
                $byDirectory['/'.$entry->getRelativePath()][] = $entry;
            }
        }

        $contexts = $this->existingContexts(array_map(static fn (string $key): string => substr($key, 1), array_keys($byDirectory)));
        $newestId = $this->newestIds();
        $examined = 0;
        $unreferenced = 0;
        $fromTheFuture = false;

        foreach ($byDirectory as $key => $files) {
            $directory = substr($key, 1);
            $topLevel = explode('/', $directory)[0];
            $context = $this->filesDirectory->parseContextDirectory($directory);

            $orphaned = [];
            $orphanedBytes = 0;
            foreach ($files as $file) {
                ++$examined;
                $name = $file->getFilename();
                if ($this->isReferenced($references, $file, $context['contextId'] ?? null)) {
                    continue;
                }
                ++$unreferenced;

                if ('portal' === $topLevel || 'server' === $topLevel) {
                    $findings[] = $this->fileFinding(FindingKind::StrayImage, $file, 'no portal or server refers to this logo');
                } elseif (null === $context) {
                    --$unreferenced;
                    $findings[] = $this->fileFinding(FindingKind::Unknown, $file, 'not in a context directory');
                } elseif (!isset($contexts[$context['contextId']])) {
                    $fromTheFuture = $fromTheFuture || $context['contextId'] > $newestId['item'];
                    $orphaned[] = $file->getRelativePathname();
                    $orphanedBytes += (int) $file->getSize();
                } elseif (1 === preg_match(self::UPLOAD, $name, $match)) {
                    $fromTheFuture = $fromTheFuture || (int) $match[1] > $newestId['file'];
                    $findings[] = $this->fileFinding(FindingKind::StrayUpload, $file, 'no file row with this id');
                } elseif (1 === preg_match(self::GENERATED_IMAGE, $name)) {
                    $findings[] = $this->fileFinding(FindingKind::StrayImage, $file, 'no setting refers to this image');
                } else {
                    --$unreferenced;
                    $findings[] = $this->fileFinding(FindingKind::Unknown, $file, 'unknown kind of file');
                }
            }

            if ([] !== $orphaned) {
                $findings[] = new Finding(
                    FindingKind::OrphanedDirectory, null, $directory, $orphanedBytes,
                    \count($orphaned).' files of context '.$context['contextId'].', which no longer exists',
                    $orphaned,
                );
            }
        }

        $suspicious = $fromTheFuture || $unreferenced > max(self::SHARE_MINIMUM, $examined * self::STRAY_SHARE_LIMIT);

        return ['findings' => $findings, 'suspicious' => $suspicious];
    }

    /**
     * Whether a row or a setting still refers to the file: its path, the id
     * of an upload (also of a CommSy 6 one) with a row, deleted or not, or
     * an image named in the settings of its context.
     */
    private function isReferenced(FileReferences $references, SplFileInfo $file, ?int $contextId): bool
    {
        $name = $file->getFilename();

        return $references->isReferenced($file->getPathname())
            || (1 === preg_match(self::UPLOAD, $name, $match) && $references->hasFileRow((int) $match[1]))
            || (1 === preg_match(self::OLD_UPLOAD, $name, $match) && $references->hasFileRow((int) $match[1]))
            || (null !== $contextId && $references->isImageReferenced($contextId, $name));
    }

    /**
     * The highest ids handed out: a context or an upload on disk above them
     * cannot have been deleted, the database must be older than the disk.
     *
     * @return array{item: int, file: int}
     */
    private function newestIds(): array
    {
        $highest = fn (string $entity, string $field): int => (int) $this->entityManager->createQueryBuilder()
            ->select("MAX(t.$field)")->from($entity, 't')->getQuery()->getSingleScalarResult();

        return [
            'item' => max($highest(Items::class, 'itemId'), $highest(Portal::class, 'id')),
            'file' => $highest(Files::class, 'filesId'),
        ];
    }

    /**
     * Context ids of the given directories that still exist as an entry or
     * a room (deleted or not), a portal or the server.
     *
     * @param list<string> $directories
     *
     * @return array<int, true>
     */
    private function existingContexts(array $directories): array
    {
        $ids = [];
        foreach ($directories as $directory) {
            $context = $this->filesDirectory->parseContextDirectory($directory);
            if (null !== $context) {
                $ids[$context['contextId']] = true;
            }
        }

        $existing = [];
        foreach (array_chunk(array_keys($ids), 1000) as $chunk) {
            foreach ([[Items::class, 'itemId'], [Room::class, 'itemId'], [Portal::class, 'id'], [Server::class, 'id']] as [$entity, $field]) {
                foreach ($this->entityManager->createQueryBuilder()
                    ->select("t.$field")
                    ->from($entity, 't')
                    ->where("t.$field IN (:ids)")
                    ->setParameter('ids', $chunk)
                    ->getQuery()->getSingleColumnResult() as $id) {
                    $existing[(int) $id] = true;
                }
            }
        }

        return $existing;
    }

    private function fileFinding(FindingKind $kind, SplFileInfo $file, string $reason): Finding
    {
        return new Finding($kind, null, $file->getRelativePathname(), (int) $file->getSize(), $reason);
    }
}
