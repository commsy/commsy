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

class FileManager
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
}
