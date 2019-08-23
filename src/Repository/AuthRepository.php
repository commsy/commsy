<?php


namespace App\Repository;


use App\Entity\Auth;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AuthRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Auth::class);
    }

    /**
     * @param string $usernameOrEmail
     * @param int $context
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function findOneByCredentials(string $usernameOrEmail, int $context)
    {
        return $this->createQueryBuilder('a')
            ->where('a.username = :query OR a.email = :query')
            ->andWhere('a.contextId = :contextId')
            ->setParameters([
                'query' => $usernameOrEmail,
                'contextId' => $context,
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }
}