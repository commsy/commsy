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

use App\Entity\ItemLinkFile;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;

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

        $repository = $this->entityManager->getRepository(ItemLinkFile::class);
        $qb = $repository->createQueryBuilder('ilf')
            ->update()
            ->set('ilf.deletionDate', ':deletionDate')
            ->set('ilf.deleterId', ':deleterId')
            ->andWhere('ilf.itemId = :itemId')
            ->andWhere('ilf.versionId = :versionId')
            ->setParameters(new ArrayCollection([
                new Parameter('deletionDate', new DateTime(), Types::DATETIME_MUTABLE),
                new Parameter('deleterId', $deleterId),
                new Parameter('itemId', $itemId),
                new Parameter('versionId', $versionId),
            ]));

        if ($fileId) {
            $qb->andWhere('ilf.fileId = :fileId')
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
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(
            'UPDATE files
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE files_id = :fileId',
            ['deleterId' => $deleterId, 'fileId' => $fileId]
        );

        $connection->executeStatement(
            'UPDATE item_link_file
                SET deletion_date = NOW(), deleter_id = :deleterId
                WHERE file_id = :fileId',
            ['deleterId' => $deleterId, 'fileId' => $fileId]
        );
    }

    /**
     * Soft-deletes every one of the given files that has no live
     * `item_link_file` row left, i.e. whose last carrying entry is gone.
     *
     * This is what ends a file's life. Stamping the link alone hides the
     * file but strands it: {@see hardDeleteExpiredFiles()} keys on
     * `files.deletion_date`, and `cs_file_manager::deleteUnneededFiles()`
     * counts a stamped link as a link, so neither sweep would ever reach
     * it again. With the stamp here the file follows the same course as
     * every other soft-deleted row — swept after the configured retention.
     *
     * @param int[] $fileIds
     */
    public function softDeleteFilesWithoutLiveLinks(array $fileIds, int $deleterId): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $fileIds))));
        if ($ids === []) {
            return;
        }

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE files f
                SET f.deletion_date = NOW(), f.deleter_id = :deleterId
                WHERE f.files_id IN (:fileIds)
                  AND f.deletion_date IS NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM item_link_file i
                          WHERE i.file_id = f.files_id AND i.deletion_date IS NULL
                  )',
            ['deleterId' => $deleterId, 'fileIds' => $ids],
            ['fileIds' => ArrayParameterType::INTEGER]
        );
    }

    /**
     * Physically removes every `files` row whose `deletion_date` is older
     * than `$days`, plus matching `item_link_file` rows and the actual
     * file on disk (via legacy `cs_disc_manager`).
     */
    public function hardDeleteExpiredFiles(int $days): void
    {
        $connection = $this->entityManager->getConnection();

        /** @var list<array{files_id: int|string, portal_id: int|string, context_id: int|string, filename: string}> $expiredFiles */
        $expiredFiles = $connection->fetchAllAssociative(
            'SELECT files_id, portal_id, context_id, filename
                FROM files
                WHERE deletion_date IS NOT NULL
                  AND deletion_date < DATE_SUB(CURRENT_DATE(), INTERVAL :days DAY)',
            ['days' => $days]
        );
        if (empty($expiredFiles)) {
            return;
        }

        $fileIds = array_map(static fn (array $row): int => (int) $row['files_id'], $expiredFiles);

        // FK constraint: `item_link_file` rows point at `files` and must
        // go first.
        $connection->executeStatement(
            'DELETE FROM item_link_file WHERE file_id IN (:fileIds)',
            ['fileIds' => $fileIds],
            ['fileIds' => ArrayParameterType::INTEGER]
        );

        $connection->executeStatement(
            'DELETE FROM files WHERE files_id IN (:fileIds)',
            ['fileIds' => $fileIds],
            ['fileIds' => ArrayParameterType::INTEGER]
        );

        // Filesystem cleanup: disc_manager computes the on-disk filename
        // from portal/context/files_id/filename, so feed one row at a time.
        $discManager = $this->legacyEnvironment->getDiscManager();
        foreach ($expiredFiles as $row) {
            $filename = 'cid' . $row['context_id'] . '_' . $row['files_id'] . '_' . $row['filename'];
            $discManager->setPortalID((int) $row['portal_id']);
            $discManager->setContextID((int) $row['context_id']);
            if ($discManager->existsFile($filename)) {
                $discManager->unlinkFile($filename);
            }
        }
    }
}
