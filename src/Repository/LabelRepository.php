<?php
namespace App\Repository;

use App\Entity\Labels;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LabelRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Labels::class);
    }

    public function findRoomHashtags($roomId)
    {
        $query = $this->createQueryBuilder('l')
            ->andWhere('l.contextId = :roomId')
            ->andWhere('l.type = :type')
            ->andWhere('l.deletionDate IS NULL')
            ->andWhere('l.deleter IS NULL')
            ->setParameter('roomId', $roomId)
            ->setParameter('type', 'buzzword')
            ->getQuery();

        return $query->getResult();
    }

    public function findLabelsByContextIdAndNameAndType($contextId, $name, $type)
    {
        $qb = $this->createQueryBuilder('l');

        return $qb
            ->where($qb->expr()->andX(
                $qb->expr()->eq('l.contextId', ':contextId'),
                $qb->expr()->eq('l.name', ':name'),
                $qb->expr()->eq('l.type', ':type'),
                $qb->expr()->isNull('l.deletionDate'),
                $qb->expr()->isNull('l.deleter')
            ))
            ->setParameters([
                'contextId' => $contextId,
                'name' => $name,
                'type' => $type,
            ])
            ->getQuery()
            ->getResult();
    }
}