<?php

namespace App\Repository;

use App\Entity\Terms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * TermRepositoryhe Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TermsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Terms::class);
    }
}
