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

use App\Entity\Room;
use App\Entity\RoomExport;
use App\Export\RoomExportStatus;
use DateInterval;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RoomExport>
 */
class RoomExportRepository extends ServiceEntityRepository
{
    /**
     * After this long without being picked up, a queued export is taken to be
     * lost. A worker claims a message within seconds, so anything older means
     * there is no worker, or the one that claimed the message died before it
     * could start.
     */
    private const PENDING_STALE_AFTER = 'PT15M';

    /**
     * After this long, a run that has not come back is taken to have died with
     * its worker. The window is generous because how long a run legitimately
     * takes follows from the size of the room.
     */
    private const RUNNING_STALE_AFTER = 'PT1H';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomExport::class);
    }

    public function save(RoomExport $export): void
    {
        $manager = $this->getEntityManager();
        $manager->persist($export);
        $manager->flush();
    }

    /**
     * The export that stands in the way of starting a new one for this room, or
     * null if none does. A stale export does not stand in the way.
     */
    public function findBlockingFor(Room $room): ?RoomExport
    {
        foreach ($this->findInProgressFor($room) as $export) {
            if (!$this->isStale($export)) {
                return $export;
            }
        }

        return null;
    }

    /**
     * Settles the exports of this room that nothing is working on any more.
     *
     * A worker can be restarted or killed at any moment, and a row it leaves
     * behind would otherwise keep claiming to be under way for as long as the
     * room exists. Settling it both tells the moderation the truth and frees the
     * room for another attempt.
     */
    public function releaseStale(Room $room): void
    {
        $released = false;

        foreach ($this->findInProgressFor($room) as $export) {
            if ($this->isStale($export)) {
                $export->markAbandoned();
                $this->getEntityManager()->persist($export);
                $released = true;
            }
        }

        if ($released) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return RoomExport[] newest first
     */
    public function findForRoom(Room $room, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.room = :room')
            ->setParameter('room', $room)
            ->orderBy('e.requestedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return RoomExport[] newest first; a room has a handful of these at most
     */
    private function findInProgressFor(Room $room): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.room = :room')
            ->andWhere('e.status IN (:inProgress)')
            ->setParameter('room', $room)
            ->setParameter('inProgress', [RoomExportStatus::Pending, RoomExportStatus::Running])
            ->orderBy('e.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Both windows are measured from the request: nothing records when a run
     * began, and a worker picks a message up within seconds of it arriving.
     */
    private function isStale(RoomExport $export): bool
    {
        $window = RoomExportStatus::Pending === $export->getStatus()
            ? self::PENDING_STALE_AFTER
            : self::RUNNING_STALE_AFTER;

        return $export->getRequestedAt() < (new DateTimeImmutable())->sub(new DateInterval($window));
    }
}
