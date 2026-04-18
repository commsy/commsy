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

    public function softDeleteFileLink(int $itemId, int $versionId, ?int $fileId = null): void
    {
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $repository = $this->entityManager->getRepository(ItemLinkFile::class);
        $qb = $repository->createQueryBuilder('ilf')
            ->update()
            ->set('ilf.deletionDate', ':deletionDate')
            ->set('ilf.deleterId', ':deleterId')
            ->andWhere('ilf.itemId = :itemId')
            ->andWhere('ilf.versionId = :versionId')
            ->setParameters(new ArrayCollection([
                new Parameter('deletionDate', new DateTime(), Types::DATETIME_MUTABLE),
                new Parameter('deleterId', $currentUser->getItemID()),
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
     * Replaces the legacy `cs_file_item::delete()` cascade (inherited from
     * `cs_item::delete()` → `cs_file_manager::delete()` →
     * `cs_link_item_file_manager::deleteByFileID()`). Legacy also invoked
     * `cs_link_item_manager::deleteLinksBecauseItemIsDeleted($fileId)`,
     * but files have no `items`-twin row, so that call is effectively a
     * no-op and is intentionally not reproduced here.
     *
     * The physical file on disk and the `files` row itself are only
     * removed by hard-delete (cron sweep, out of scope of this method).
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
     * Physically removes every `files` row whose `deletion_date` is older
     * than `$days`, plus the matching `item_link_file` rows and — via the
     * legacy `cs_disc_manager` — the actual file on disk.
     *
     * Replaces `cs_file_manager::deleteReallyOlderThan()`. The only part
     * that still touches legacy is `cs_disc_manager::unlinkFile()` for the
     * filesystem side-effect; the DB work happens via DBAL here so the
     * cron sweep can live in App-side services even while the legacy
     * filesystem helper stays. That legacy dependency is a deliberate,
     * scoped carry-over (#5082 design decision) and will be ported when
     * the storage layer itself gets lifted out of `legacy/`.
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

        // Foreign-key constraint: the `item_link_file` rows point at `files`
        // and must go first. Parity with legacy `cs_link_item_file_manager::
        // deleteByFileReally()`, which issues a hard DELETE (no soft-delete
        // here — the files row itself is about to disappear).
        $connection->executeStatement(
            'DELETE FROM item_link_file WHERE file_id IN (:fileIds)',
            ['fileIds' => $fileIds],
            ['fileIds' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $connection->executeStatement(
            'DELETE FROM files WHERE files_id IN (:fileIds)',
            ['fileIds' => $fileIds],
            ['fileIds' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        // Filesystem cleanup — see class-level comment for the legacy-dep
        // rationale. disc_manager computes the on-disk filename from
        // portal/context/files_id/filename, so we feed it one row at a
        // time.
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
