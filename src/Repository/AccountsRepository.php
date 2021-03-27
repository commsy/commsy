<?php


namespace App\Repository;

use App\Entity\Account;
use App\Entity\AuthSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
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
     * @param array $fields
     * @return Account|mixed
     * @throws NonUniqueResultException
     */
    public function findOnByCredentials(array $fields)
    {
        return $this->findOneByCredentials($fields['username'], $fields['contextId'], $fields['authSource']);
    }

    /**
     * @param string $username
     * @param int $context
     * @param AuthSource $authSource
     * @return Account|null
     * @throws NonUniqueResultException
     */
    public function findOneByCredentials(string $username, int $context, AuthSource $authSource): ?Account
    {
        return $this->createQueryBuilder('a')
            ->where('a.username = :username')
            ->andWhere('a.authSource = :authSource')
            ->andWhere('a.contextId = :contextId')
            ->setParameters([
                'username' => $username,
                'contextId' => $context,
                'authSource' => $authSource,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}