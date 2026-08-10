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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Parameter;
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
        bool $isAjax,
        ?string $username,
        ?int $contextId
    ): void {
        $log = (new Log())
            ->setIp($ip)
            ->setAgent($userAgent)
            ->setRequest($requestUri)
        // May contain sensitive information that must be excluded or masked
        //  ->setPostContent($postContent)
            ->setMethod($method)
            ->setAjax($isAjax)
            ->setUlogin($username)
            ->setCid($contextId)
        ;

        // Persisted through a unit of work of its own rather than the shared one.
        //
        // Logging runs from kernel.terminate, and flush() commits an entire unit of
        // work, not just the row being added. On the shared manager it would also
        // write whatever else the finished request left dirty — a form that failed
        // validation has already been mapped onto its entity by that point, so the
        // rejected values would reach the database.
        //
        // Connection, configuration and event manager are the shared ones, so mapping,
        // metadata cache and lifecycle callbacks (including the PrePersist that sets
        // the timestamp) behave exactly as on the default manager. Only the set of
        // tracked entities differs, and it holds nothing but this row.
        //
        // Deliberately built here instead of declaring a second entity manager in
        // doctrine.yaml: that would require the entity in a namespace of its own and
        // would split the schema commands, which then silently check one manager only.
        // Not worth it for an interim measure — the intended destination is to take
        // access logging out of Doctrine (Monolog, with the room statistics reading an
        // aggregated counter), or to dispatch it once the messenger transport no
        // longer lives in this database.
        $shared = $this->getEntityManager();
        $isolated = new EntityManager(
            $shared->getConnection(),
            $shared->getConfiguration(),
            $shared->getEventManager()
        );

        $isolated->persist($log);
        $isolated->flush();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function getCountForContext(int $contextId): int
    {
        $query = $this->getEntityManager()->createQuery("
            SELECT COUNT(DISTINCT l.timestamp)
            FROM App\Entity\Log l
            WHERE l.cid = :contextId AND
            (l.ajax = 0 OR l.ajax IS NULL) AND
            l.request NOT LIKE '%theme/background%' AND
            l.request NOT LIKE '%image%' AND
            l.request NOT LIKE '%logo%'
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
        ")->setParameters(new ArrayCollection([
            new Parameter('contextId', $contextId),
            new Parameter('lower', $lower),
            new Parameter('upper', $upper),
        ]));

        return $query->getSingleResult();
    }
}
