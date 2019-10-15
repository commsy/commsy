<?php


namespace App\Repository;


use App\Entity\Account;
use App\Entity\AuthSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AccountsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * @param string $usernameOrEmail
     * @param int $context
     * @param int $authSourceId
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByCredentials(string $usernameOrEmail, int $context, AuthSource $authSource)
    {
        return $this->createQueryBuilder('a')
            ->where('a.username = :query OR a.email = :query')
            ->andWhere('a.authSource = :authSource')
            ->andWhere('a.contextId = :contextId')
            ->setParameters([
                'query' => $usernameOrEmail,
                'contextId' => $context,
                'authSource' => $authSource,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}