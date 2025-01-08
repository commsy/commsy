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

use App\Entity\ItemLinkFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

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
}
