<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getConfirmableUserByContextId($contextId)
    {
        $qb = $this->createQueryBuilder('r');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('r.contextId', ':contextId'),
                $qb->expr()->eq('r.status', ':status'),
                $qb->expr()->isNull('r.deletionDate'),
                $qb->expr()->isNull('r.deleterId')
            ))
            ->setParameters([
                'contextId' => $contextId,
                'status' => 1,
            ]);
    }


    /**
     * @param int $roomId
     * @return mixed
     */
    public function getModeratorsByRoomId(int $roomId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.status = 3')
            ->andWhere('u.contextId = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $roomId
     * @return mixed
     */
    public function getContactsByRoomId(int $roomId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.isContact = 1')
            ->andWhere('u.contextId = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $contextId
     * @return mixed
     */
    public function findActiveUsers(int $contextId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $contextId
     * @return mixed
     */
    public function findActiveUsersAsQuery(int $contextId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery();
    }

    public function getNumActiveUsersByContext(int $contextId): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.itemId) as num')
            ->where('u.contextId = :contextId')
            ->andWhere('u.deletionDate IS NULL')
            ->andWhere('u.deleterId IS NULL')
            ->setParameter('contextId', $contextId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}