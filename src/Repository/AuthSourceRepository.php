<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 28.07.18
 * Time: 11:42
 */

namespace App\Repository;

use App\Entity\AuthSource;
use App\Entity\Portal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AuthSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthSource::class);
    }

    /**
     * @param int $portalId
     * @return mixed
     */
    public function findByPortal(int $portalId)
    {
        return $this->createQueryBuilder('a')
            ->where('a.deleterId IS NULL')
            ->andWhere('a.deletionDate IS NULL')
            ->andWhere('a.contextId = :portalId')
            ->setParameter('portalId', $portalId)
            ->getQuery()
            ->getResult();
    }

    public function findByPortalAndTypeOriginName(Portal $portal, string $typeOriginName)
    {
        $returnVal = [];
        $results = $this->createQueryBuilder('a')
            ->where('a.portal = :portal')
            ->setParameter('portal', $portal)
            ->getQuery()
            ->getResult();
        foreach($results as $result){
            if($result->getSourceOriginName() == $typeOriginName){
                array_push($returnVal, $result);
            }
        }
        return $returnVal;
    }

    public function findByPortalAndTitle(Portal $portal, string $title)
    {
        $returnVal = [];
        $results = $this->createQueryBuilder('a')
            ->where('a.portal = :portal')
            ->setParameter('portal', $portal)
            ->getQuery()
            ->getResult();
        foreach($results as $result){
            if($result->getTitle() == $title){
                array_push($returnVal, $result);
            }
        }
        return $returnVal;
    }
}