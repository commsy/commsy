<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
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
    public function getContactsByRoomId(int $roomId)
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
    public function getModeratorsByRoomId(int $roomId)
    {
        return $this->createQueryBuilder('u')
            ->where('u.isContact = 1')
            ->andWhere('u.contextId = :roomId')
            ->andWhere('u.deletionDate IS NULL')
            ->setParameter('roomId', $roomId)
            ->getQuery()
            ->getResult();
    }
}