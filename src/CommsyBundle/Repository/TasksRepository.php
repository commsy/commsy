<?php
namespace CommsyBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TasksRepository extends EntityRepository
{
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