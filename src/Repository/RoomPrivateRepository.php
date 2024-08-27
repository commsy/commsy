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
use App\Entity\RoomPrivat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

class RoomPrivateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RoomPrivat::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByPortalIdAndAccount(int $portalId, Account $account): ?RoomPrivat
    {
        return $this->createQueryBuilder('rp')
            ->select('rp')
            ->innerJoin(User::class, 'u', Expr\Join::WITH, 'u.contextId = rp.itemId AND u.deleterId IS NULL AND u.deletionDate IS NULL')
            ->innerJoin(Account::class, 'a', Expr\Join::WITH, 'a.username = u.userId AND a.authSource = u.authSource')
            ->where('rp.contextId = :portalId')
            ->andWhere('rp.deleterId IS NULL')
            ->andWhere('rp.deletionDate IS NULL')
            ->andWhere('a.authSource = :authSource')
            ->andWhere('a.contextId = :portalId')
            ->andWhere('a.username = :username')
            ->orderBy('rp.creationDate', 'DESC')
            ->setParameters(new ArrayCollection([
                new Parameter('portalId', $portalId),
                new Parameter('username', $account->getUsername()),
                new Parameter('authSource', $account->getAuthSource()),
            ]))
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
