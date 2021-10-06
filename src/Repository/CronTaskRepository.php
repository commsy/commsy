<?php

namespace App\Repository;

use App\Entity\CronTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CronTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method CronTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method CronTask[]    findAll()
 * @method CronTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CronTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronTask::class);
    }
}
