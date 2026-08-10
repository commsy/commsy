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

use App\Entity\Portal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

class PortalRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RoomRepository $roomRepository
    ) {
        parent::__construct($registry, Portal::class);
    }

    /**
     * Returns the portal associated with (or hosting the room with) the given ID.
     *
     * @param int $id portal ID, or ID of a room whose portal shall be returned
     */
    public function findPortalById(int $id): ?Portal
    {
        try {
            /** @var Portal $portal */
            $portal = $this->find($id);
            if (!$portal) {
                $room = $this->roomRepository->find($id);
                if ($room) {
                    $portal = $room->getPortal();
                }
            }

            return $portal;
        } catch (RuntimeException) {
            return null;
        }
    }

    public function findActivePortals()
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findActivePortal(int $portalId): mixed
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->andWhere('p.id = :portalId')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findAllActive()
    {
        return $this->createQueryBuilder('p')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Bumps the activity counter as a statement rather than through the unit of work.
     *
     * Two reasons it is not a read-modify-write on the entity: it keeps the counter
     * out of the request's unit of work, and concurrent requests reading the same
     * value would lose increments. Doing it in the database avoids both.
     */
    public function incrementActivity(int $portalId): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.activity', 'p.activity + 1')
            ->where('p.id = :portalId')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->execute();
    }
}
