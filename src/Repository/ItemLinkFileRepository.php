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

namespace App\Repository;

use App\Entity\Files;
use App\Entity\ItemLinkFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ItemLinkFile>
 */
class ItemLinkFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ItemLinkFile::class);
    }

    /**
     * @return int[]
     */
    public function getLinkedFileIds(int $itemId, int $versionId): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT f.filesId
            FROM App\Entity\ItemLinkFile ilf
            JOIN ilf.file f
            WHERE ilf.deleterId IS NULL AND
            ilf.deletionDate IS NULL AND
            ilf.itemId = :itemId AND
            ilf.versionId = :versionId
        ');

        $query->setParameters(new ArrayCollection([
            new Parameter('itemId', $itemId),
            new Parameter('versionId', $versionId),
        ]));

        return $query->getSingleColumnResult();
    }

    /**
     * The live files linked to these item versions, each file once.
     *
     * A link belongs to a *version* of an entry: editing an entry so that it
     * loses an attachment leaves the old link on the old version. Asking for the
     * version an entry is exported at therefore yields what the entry shows
     * today, and not what it once carried.
     *
     * Legacy filters files by their deleter alone; both columns are filtered
     * here, which makes no difference to the rows but says what is meant.
     *
     * @param array<int, int> $versionByItemId version per item id
     *
     * @return Files[] keyed by file id
     */
    public function findLinkedFiles(array $versionByItemId): array
    {
        if ([] === $versionByItemId) {
            return [];
        }

        $links = $this->createQueryBuilder('ilf')
            ->select('ilf', 'f')
            ->join('ilf.file', 'f')
            ->andWhere('ilf.itemId IN (:itemIds)')
            ->andWhere('ilf.deleterId IS NULL')
            ->andWhere('ilf.deletionDate IS NULL')
            ->andWhere('f.deleterId IS NULL')
            ->andWhere('f.deletionDate IS NULL')
            ->setParameter('itemIds', array_keys($versionByItemId))
            ->getQuery()
            ->getResult();

        $files = [];
        foreach ($links as $link) {
            if ($link->getVersionId() !== $versionByItemId[$link->getItemId()]) {
                continue;
            }

            $file = $link->getFile();
            $files[$file->getFilesId()] = $file;
        }

        return $files;
    }

    /**
     * Whether the file is attached to the item — in the given version, or in
     * any when none is given. Stamped links do not count.
     */
    public function isAttached(int $fileId, int $itemId, ?int $versionId = null): bool
    {
        $qb = $this->createQueryBuilder('ilf')
            ->select('COUNT(ilf.itemId)')
            ->andWhere('IDENTITY(ilf.file) = :fileId')
            ->andWhere('ilf.itemId = :itemId')
            ->andWhere('ilf.deletionDate IS NULL')
            ->setParameter('fileId', $fileId)
            ->setParameter('itemId', $itemId);

        if (null !== $versionId) {
            $qb->andWhere('ilf.versionId = :versionId')
                ->setParameter('versionId', $versionId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Returns the distinct item ids the file is linked to, dropping
     * version duplicates. Active links only (deleter/deletion filter
     * on item_link_file).
     *
     * Used by {@see \App\Files\FilePermissionChecker} to fan out the
     * file's visibility / edit checks across its linked items.
     *
     * @return int[] item ids in ascending order
     */
    public function findLinkedItemIds(int $fileId): array
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT DISTINCT ilf.itemId
            FROM App\Entity\ItemLinkFile ilf
            JOIN ilf.file f
            WHERE f.filesId = :fileId
              AND ilf.deleterId IS NULL
              AND ilf.deletionDate IS NULL
            ORDER BY ilf.itemId
        ');

        $query->setParameter('fileId', $fileId);

        return array_map('intval', $query->getSingleColumnResult());
    }
}
