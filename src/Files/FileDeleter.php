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
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class FileDeleter
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $environment,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->legacyEnvironment = $environment->getEnvironment();
    }

    /**
     * @param int      $deleterId Audit stamp, supplied by the caller. A
     *                            deletion running in a background worker
     *                            has no current user to fall back on.
     * @param int|null $fileId    Restricts the stamp to one attachment;
     *                            null covers every file on the item.
     */
    public function softDeleteFileLink(
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
     * Soft-deletes a file entry: marks the `files` row as deleted and
     * soft-deletes every `item_link_file` row that references it.
     *
     * Files have no `items`-twin row. Physical file removal happens in
     * {@see hardDeleteExpiredFiles()}.
     */
    public function softDeleteFile(int $fileId, int $deleterId): void
    {
        $now = new DateTime();

        $this->entityManager->createQueryBuilder()
            ->update(Files::class, 'f')
            ->set('f.deletionDate', ':now')
            ->set('f.deleterId', ':deleterId')
            ->where('f.filesId = :fileId')
            ->setParameter('now', $now)
            ->setParameter('deleterId', $deleterId)
            ->setParameter('fileId', $fileId)
            ->getQuery()->execute();

        $this->entityManager->createQueryBuilder()
            ->update(ItemLinkFile::class, 'ilf')
            ->set('ilf.deletionDate', ':now')
            ->set('ilf.deleterId', ':deleterId')
            ->where('ilf.file = :fileId')
            ->setParameter('now', $now)
            ->setParameter('deleterId', $deleterId)
            ->setParameter('fileId', $fileId)
            ->getQuery()->execute();
    }

    /**
     * Detaches every file on the item and ends the life of the ones that
     * lost their last carrying entry.
     *
     * One method rather than two calls, so a caller cannot stamp the link
     * and forget the file — which is the state that stranded 75k files:
     * neither sweep can reach a file whose links are stamped but whose own
     * row is untouched.
     *
     * @param int|null $versionId The single version to detach; null means
     *                            the unversioned rubrics' only version.
     */
    public function detachFromItem(int $itemId, int $deleterId, ?int $versionId = null): void
    {
        $fileIds = $this->findLinkedFileIds([$itemId]);

        $this->softDeleteFileLink($itemId, $versionId ?? 0, $deleterId);

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
     * than `$days`, plus matching `item_link_file` rows and the actual
     * file on disk (via legacy `cs_disc_manager`).
     */
    public function hardDeleteExpiredFiles(int $days): void
    {
        /** @var list<array{filesId: int, portalId: int|string|null, contextId: int, filename: string}> $expiredFiles */
        $expiredFiles = $this->entityManager->createQueryBuilder()
            ->select('f.filesId, IDENTITY(f.portal) AS portalId, f.contextId, f.filename')
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

        // Filesystem cleanup: disc_manager computes the on-disk filename
        // from portal/context/files_id/filename, so feed one row at a time.
        $discManager = $this->legacyEnvironment->getDiscManager();
        foreach ($expiredFiles as $row) {
            $filename = 'cid' . $row['contextId'] . '_' . $row['filesId'] . '_' . $row['filename'];
            $discManager->setPortalID((int) $row['portalId']);
            $discManager->setContextID((int) $row['contextId']);
            if ($discManager->existsFile($filename)) {
                $discManager->unlinkFile($filename);
            }
        }
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
