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

use App\Entity\Log;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    public function deleteOlderThen(int $numDays): void
    {
        $query = $this->getEntityManager()->createQuery("
            DELETE App\Entity\Log l WHERE l.timestamp < DATE_SUB(CURRENT_DATE(), :days, 'DAY')
        ")->setParameter('days', $numDays);
        $query->execute();
    }

    public function addLog(
        string $ip,
        string $userAgent,
        string $requestUri,
        string $postContent,
        string $method,
        ?string $username,
        ?int $contextId
    ) {
        $log = new Log();
        $log->setIp($ip);
        $log->setAgent($userAgent);
        $log->setRequest($requestUri);
        // May contain sensitive information that must be excluded or masked
        //$log->setPostContent($postContent);
        $log->setMethod($method);
        $log->setUlogin($username);
        $log->setCid($contextId);

        $em = $this->getEntityManager();
        $em->persist($log);
        $em->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getCountForContext(int $contextId): int
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT COUNT(l.id) FROM App\Entity\Log l WHERE l.cid = :contextId
        ")->setParameter('contextId', $contextId);

        return $query->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getCountByContextAndDateSpan(
        int $contextId,
        DateTimeInterface $lower,
        DateTimeInterface $upper
    ): array {
        $query = $this->getEntityManager()->createQuery("
            SELECT COUNT(l.id) as count, COUNT(DISTINCT l.ulogin) as distinctUserCount
            FROM App\Entity\Log l
            WHERE l.cid = :contextId AND
            l.timestamp >= :lower AND
            l.timestamp < :upper AND
            l.request LIKE '%/room/%'
        ")->setParameters([
            'contextId' => $contextId,
            'lower' => $lower,
            'upper' => $upper
        ]);

        return $query->getSingleResult();
    }
}
