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

namespace App\Repository;

use App\Entity\Section;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Reads the sections a material is made of.
 *
 * A section belongs to a *version* of its material: editing a material writes
 * its sections again under the new version, so the same section can stand at
 * several versions, each time with its own order.
 *
 * @extends ServiceEntityRepository<Section>
 */
class SectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Section::class);
    }

    /**
     * @return Section[] in the order the material puts them in
     */
    public function findForExport(int $materialItemId, int $versionId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.material', 'm')
            ->andWhere('m.itemId = :materialItemId')
            ->andWhere('s.versionId = :versionId')
            ->andWhere('s.deleter IS NULL')
            ->andWhere('s.deletionDate IS NULL')
            ->setParameter('materialItemId', $materialItemId)
            ->setParameter('versionId', $versionId)
            ->orderBy('s.number', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
