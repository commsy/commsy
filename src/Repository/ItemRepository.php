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

use App\Entity\Items;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository as ServiceEntityRepositoryAlias;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepositoryAlias
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Items::class);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNumItems(int $itemId): int
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT COUNT(i.itemId) FROM App\Entity\Items i
            WHERE i.itemId = :itemId
        ');
        $query->setParameters([
            'itemId' => $itemId,
        ]);

        return $query->getSingleScalarResult();
    }

    public function getPinnedItemsByRoomId(int $roomId): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.pinned = 1')
            ->andWhere('i.contextId = :roomId')
            ->andWhere('i.draft = 0')
            ->andWhere('i.deletionDate IS NULL')
            ->andWhere('i.deleterId IS NULL')
            ->orderBy('i.modificationDate', 'DESC')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->execute();
    }

    public function getPinnedItemsByRoomIdAndType(int $roomId, array $types): array
    {
        // NOTE: if the Items class isn't defined as abstract class, we can use a native SQL query
        //       to query the `type` discriminator column
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult(Items::class, 'i');
        $rsm->addFieldResult('i', 'item_id', 'itemId');
        $rsm->addFieldResult('i', 'context_id', 'contextId');
        $rsm->addFieldResult('i', 'deleter_id', 'deleterId');
        $rsm->addFieldResult('i', 'deletion_date', 'deletionDate');
        $rsm->addFieldResult('i', 'modification_date', 'modificationDate');
        $rsm->addFieldResult('i', 'activation_date', 'activationDate');
        $rsm->addFieldResult('i', 'draft', 'draft');
        $rsm->addFieldResult('i', 'pinned', 'pinned');

        $params = [ $roomId ];
        $sql = '
                SELECT item_id, context_id, type, deleter_id, deletion_date, modification_date, activation_date, draft, pinned
                FROM items i
                WHERE (pinned = 1 AND i.context_id = ? AND i.draft = 0 AND i.deletion_date IS NULL AND i.deleter_id IS NULL)
        ';

        if (!empty($types)) {
            $params = array_merge($params, $types);
            $sql .= ' AND i.type IN (' . implode(', ', array_fill(0, count($types), '?')) . ')';
        }

        $sql .= ' ORDER BY i.modification_date DESC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        // ensure numerical array keys starting at 1
        $params = array_combine(range(1, count($params)), array_values($params));

        $query->setParameters($params);

        return $query->getResult();
    }
}
