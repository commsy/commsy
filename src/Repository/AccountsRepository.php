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

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class AccountsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * IMPORTANT: DO NOT DELETE!
     * This is used by the UniqueEntity annotation in App\Entity\Account.
     *
     * @param array $fields associative array of account credentials with keys: `username`, `portal`, `authSource`
     *
     * @throws NonUniqueResultException
     */
    public function findOneByCredentialsArray(array $fields): ?Account
    {
        return $this->findOneByCredentials($fields['username'], $fields['portal'], $fields['authSource']);
    }

    /**
     * Looks up an account by username and auth source, scoped to a portal
     * if one is given. The server-context root account and its auth source
     * have no portal binding (portal_id IS NULL) — pass $portal=null in
     * that case to find it.
     *
     * @throws NonUniqueResultException
     */
    public function findOneByCredentials(string $username, ?Portal $portal, AuthSource $authSource): ?Account
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.username = :username')
            ->andWhere('a.authSource = :authSource')
            ->setParameters(new ArrayCollection([
                new Parameter('username', $username),
                new Parameter('authSource', $authSource),
            ]));

        if ($portal !== null) {
            $qb->andWhere('a.portal = :portal')
                ->setParameter('portal', $portal);
        } else {
            $qb->andWhere('a.portal IS NULL');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByEmailAndPortalId(string $email, int $portalId)
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :email')
            ->andWhere('a.portal = :portalId')
            ->setParameters(new ArrayCollection([
                new Parameter('email', $email),
                new Parameter('portalId', $portalId),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function findAllExceptRoot()
    {
        return $this->createQueryBuilder('a')
            ->where('a.username != :rootUsername')
            ->setParameter('rootUsername', 'root')
            ->getQuery()
            ->getResult();
    }

    public function updateActivity(string $oldState, string $newState): void
    {
        $this->createQueryBuilder('a')
            ->update()
            ->set('a.activityState', ':newState')
            ->where('a.activityState = :oldState')
            ->setParameter('oldState', $oldState)
            ->setParameter('newState', $newState)
            ->getQuery()
            ->execute();
    }

    public function countByPortal()
    {
        return $this->createQueryBuilder('a')
            ->groupBy('a.portal')
            ->select('COUNT(a) as count', 'p as portal')
            ->innerJoin(Portal::class, 'p', Join::WITH, 'a.portal = p.id')
            ->where('p.deleter IS NULL')
            ->andWhere('p.deletionDate IS NULL')
            ->getQuery()
            ->getResult();
    }
}
