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
     * After this long, an export still claiming to be under way is taken to have
     * died with its worker. Without the cut-off a killed worker would block its
     * room from ever being exported again.
     */
    private const STALE_AFTER = 'PT1H';

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
     * null if none does. An export that has been running longer than
     * {@see self::STALE_AFTER} does not stand in the way.
     */
    public function findBlockingFor(Room $room): ?RoomExport
    {
        $threshold = (new DateTimeImmutable())->sub(new DateInterval(self::STALE_AFTER));

        return $this->createQueryBuilder('e')
            ->andWhere('e.room = :room')
            ->andWhere('e.status IN (:inProgress)')
            ->andWhere('e.requestedAt > :threshold')
            ->setParameter('room', $room)
            ->setParameter('inProgress', [RoomExportStatus::Pending, RoomExportStatus::Running])
            ->setParameter('threshold', $threshold)
            ->orderBy('e.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
}
