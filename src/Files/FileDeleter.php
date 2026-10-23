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

namespace App\Files;

use App\Entity\Files;
use App\Entity\ItemLinkFile;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;

class FileDeleter
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%files_directory%')]
        private readonly string $filesDirectory,
    ) {
    }

    /**
     * Detaches every file on the item — or just $fileId — from one version
     * and ends the life of the ones that lost their last carrying entry.
     * Other versions and entries keep their links, and with them the file.
     *
     * One method rather than two calls, so a caller cannot stamp the link
     * and forget the file — which is the state that stranded 75k files:
     * neither sweep can reach a file whose links are stamped but whose own
     * row is untouched.
     *
     * @param int|null $versionId The single version to detach; null means
     *                            the unversioned rubrics' only version.
     * @param int|null $fileId    Restricts the detach to one attachment; a
     *                            file not linked to the item is left alone.
     */
    public function detachFromItem(int $itemId, int $deleterId, ?int $versionId = null, ?int $fileId = null): void
    {
        $fileIds = $this->findLinkedFileIds([$itemId]);
        if ($fileId !== null) {
            $fileIds = in_array($fileId, $fileIds, true) ? [$fileId] : [];
        }

        $this->softDeleteFileLink($itemId, $versionId ?? 0, $deleterId, $fileId);

        $this->softDeleteFilesWithoutLiveLinks($fileIds, $deleterId);
    }

    /**
     * The versioned variant: detaches the file from every version of the
     * item. Materials and sections carry one link row per version.
     */
    public function detachFromItemAllVersions(int $itemId, int $deleterId): void
    {
        $fileIds = $this->findLinkedFileIds([$itemId]);

        $this->entityManager->createQueryBuilder()
            ->update(ItemLinkFile::class, 'ilf')
            ->set('ilf.deletionDate', ':now')
            ->set('ilf.deleterId', ':deleterId')
            ->where('ilf.itemId = :itemId')
            ->setParameter('now', new DateTime())
            ->setParameter('deleterId', $deleterId)
            ->setParameter('itemId', $itemId)
            ->getQuery()->execute();

        $this->softDeleteFilesWithoutLiveLinks($fileIds, $deleterId);
    }

    /**
     * Every file id linked to any of the given items, regardless of the
     * link's own deletion state or version. Read BEFORE the links are
     * stamped, so the orphan sweep still knows which files to look at.
     *
     * @param int[] $itemIds
     *
     * @return int[]
     */
    public function findLinkedFileIds(array $itemIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $itemIds)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT IDENTITY(ilf.file) AS fileId')
            ->from(ItemLinkFile::class, 'ilf')
            ->where('ilf.itemId IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['fileId'], $rows);
    }

    /**
     * Soft-deletes every one of the given files that has no live
     * `item_link_file` row left, i.e. whose last carrying entry is gone.
     *
     * This is what ends a file's life. Stamping the link alone hides the
     * file but strands it: {@see hardDeleteExpiredFiles()} keys on
     * `files.deletion_date`, and {@see softDeleteUnlinkedFiles()} only
     * looks at files that never had a link. With the stamp here the file
     * follows the same course as every other soft-deleted row — swept
     * after the configured retention.
     *
     * @param int[] $fileIds
     */
    public function softDeleteFilesWithoutLiveLinks(array $fileIds, int $deleterId): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if ($ids === []) {
            return;
        }

        $this->entityManager->createQueryBuilder()
            ->update(Files::class, 'f')
            ->set('f.deletionDate', ':now')
            ->set('f.deleterId', ':deleterId')
            ->where('f.filesId IN (:fileIds)')
            ->andWhere('f.deletionDate IS NULL')
            ->andWhere($this->noLinkExists(aliveOnly: true))
            ->setParameter('now', new DateTime())
            ->setParameter('deleterId', $deleterId)
            ->setParameter('fileIds', $ids)
            ->getQuery()->execute();
    }

    /**
     * Soft-deletes uploads that never reached an entry: `files` rows with
     * no `item_link_file` row at all. Both upload paths link the file to
     * its item in the same request, so a row without any link means that
     * write never happened — the age guard keeps a request still in
     * flight out of the sweep.
     *
     * Files whose links exist but are stamped are deliberately left
     * alone: `cs_links_manager::linkFileByID()` revives such a row when
     * the same file is attached again, so a stamp here could outlive a
     * link that comes back.
     *
     * `deleter_id` stays null — no person is behind this deletion.
     *
     * @return int Number of files stamped
     */
    public function softDeleteUnlinkedFiles(int $minAgeHours = 24): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->update(Files::class, 'f')
            ->set('f.deletionDate', ':now')
            ->where('f.deletionDate IS NULL')
            ->andWhere('f.creationDate < :cutoff')
            ->andWhere($this->noLinkExists(aliveOnly: false))
            ->setParameter('now', new DateTime())
            ->setParameter('cutoff', new DateTimeImmutable('-' . $minAgeHours . ' hours'))
            ->getQuery()->execute();
    }

    /**
     * Physically removes every `files` row whose `deletion_date` is older
     * than `$days`, plus matching `item_link_file` rows and the file on
     * disk with its thumbnail.
     */
    public function hardDeleteExpiredFiles(int $days): void
    {
        /** @var list<array{filesId: int, filepath: string}> $expiredFiles */
        $expiredFiles = $this->entityManager->createQueryBuilder()
            ->select('f.filesId, f.filepath')
            ->from(Files::class, 'f')
            ->where('f.deletionDate IS NOT NULL')
            ->andWhere('f.deletionDate < :cutoff')
            ->setParameter('cutoff', new DateTimeImmutable('-' . $days . ' days'))
            ->getQuery()->getScalarResult();

        if ($expiredFiles === []) {
            return;
        }

        $fileIds = array_map(static fn (array $row): int => (int) $row['filesId'], $expiredFiles);

        // FK constraint: `item_link_file` rows point at `files` and must
        // go first.
        $this->entityManager->createQueryBuilder()
            ->delete(ItemLinkFile::class, 'ilf')
            ->where('ilf.file IN (:fileIds)')
            ->setParameter('fileIds', $fileIds)
            ->getQuery()->execute();

        $this->entityManager->createQueryBuilder()
            ->delete(Files::class, 'f')
            ->where('f.filesId IN (:fileIds)')
            ->setParameter('fileIds', $fileIds)
            ->getQuery()->execute();

        // The download reads a file from `filepath`, so that is what goes;
        // an image's thumbnail lies next to it. A path another row still
        // names stays.
        $paths = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => (string) $row['filepath'],
            $expiredFiles,
        ))));
        if ($paths === []) {
            return;
        }

        $stillNamed = $this->entityManager->createQueryBuilder()
            ->select('f.filepath')
            ->from(Files::class, 'f')
            ->where('f.filepath IN (:paths)')
            ->setParameter('paths', $paths)
            ->getQuery()->getSingleColumnResult();

        foreach (array_diff($paths, $stillNamed) as $relativePath) {
            $path = $this->pathInFilesDirectory($relativePath);
            if ($path === null) {
                continue;
            }

            foreach ([$path, $path . '_thumb'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Stamps the links of one version. Private on purpose: a link is only
     * let go through {@see detachFromItem()}, which also ends the file when
     * that was its last live link.
     *
     * @param int      $deleterId Audit stamp, supplied by the caller. A
     *                            deletion running in a background worker
     *                            has no current user to fall back on.
     * @param int|null $fileId    Restricts the stamp to one attachment;
     *                            null covers every file on the item.
     */
    private function softDeleteFileLink(
        int $itemId,
        int $versionId,
        int $deleterId,
        ?int $fileId = null,
    ): void {
        $qb = $this->entityManager->createQueryBuilder()
            ->update(ItemLinkFile::class, 'ilf')
            ->set('ilf.deletionDate', ':now')
            ->set('ilf.deleterId', ':deleterId')
            ->where('ilf.itemId = :itemId')
            ->andWhere('ilf.versionId = :versionId')
            ->setParameter('now', new DateTime())
            ->setParameter('deleterId', $deleterId)
            ->setParameter('itemId', $itemId)
            ->setParameter('versionId', $versionId);

        if ($fileId !== null) {
            $qb->andWhere('ilf.file = :fileId')
                ->setParameter('fileId', $fileId);
        }

        $qb->getQuery()->execute();
    }

    /**
     * The absolute path of a `filepath` value, or null when it does not
     * point below the files directory. Nothing outside is ever removed.
     */
    private function pathInFilesDirectory(string $filepath): ?string
    {
        $path = Path::canonicalize(Path::isAbsolute($filepath) ? $filepath : $this->projectDir . '/' . $filepath);
        $root = Path::canonicalize($this->filesDirectory);

        return $path !== $root && Path::isBasePath($root, $path) ? $path : null;
    }

    /**
     * `NOT EXISTS` on the link table, correlated to the `files` alias `f`.
     *
     * @param bool $aliveOnly Whether a link that merely carries a deletion
     *                        stamp still counts as a link
     */
    private function noLinkExists(bool $aliveOnly): string
    {
        $expr = $this->entityManager->getExpressionBuilder();

        $subQuery = $this->entityManager->createQueryBuilder()
            ->select('i.itemId')
            ->from(ItemLinkFile::class, 'i')
            ->where('IDENTITY(i.file) = f.filesId');

        if ($aliveOnly) {
            $subQuery->andWhere('i.deletionDate IS NULL');
        }

        return (string) $expr->not($expr->exists($subQuery->getDQL()));
    }
}
