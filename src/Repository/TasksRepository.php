<?php
namespace App\Repository;

use App\Entity\Tasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class TasksRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Tasks::class);
    }

    public function getTasksByContextId($contextId)
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
                'status' => 'REQUEST',
            ]);
    }
}