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
use Doctrine\ORM\UnexpectedResultException;
use Doctrine\Persistence\ManagerRegistry;

class PortalRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RoomRepository $roomRepository,
        private readonly RoomPrivateRepository $privateRoomRepository
    ) {
        parent::__construct($registry, Portal::class);
    }

    /**
     * Returns the portal of the room with the given room ID.
     *
     * @param int $contextId context ID of the room whose portal shall be returned
     * @throws UnexpectedResultException
     */
    public function findPortalByRoomContext(int $contextId): Portal
    {
        /** @var Portal $portal */
        $portal = $this->find($contextId);
        if (!$portal) {
            // NOTE: for user rooms, the context is its parent project room (whose context is the portal)
            $parentRoom = $this->roomRepository->find($contextId);
            $portal = $this->find($parentRoom->getContextId());
        }

        if (!$portal) {
            throw new UnexpectedResultException(sprintf('Could not fetch portal for room with context ID "%s".', $contextId));
        }

        return $portal;
    }

    /**
     * Returns the portal associated with (or hosting the room with) the given ID.
     *
     * @param int $id portal ID, or ID of a room whose portal shall be returned
     */
    public function findPortalById(int $id): ?Portal
    {
        /** @var Portal $portal */
        $portal = $this->find($id);
        if (!$portal) {
            $room = $this->roomRepository->find($id) ?? $this->privateRoomRepository->find($id);
            if ($room) {
                $portal = $this->findPortalByRoomContext($room->getContextId());
            }
        }

        return $portal;
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
}
