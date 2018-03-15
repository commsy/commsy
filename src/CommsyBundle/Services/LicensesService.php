<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\RoomCategories;
use CommsyBundle\Entity\RoomCategoriesLinks;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Sabre\VObject;

class LicensesService
{

    /**
     * @var EntityManager $em
     */
    private $em;

    private $serviceContainer;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    public function getListLicenses ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Licenses');
        $query = $repository->createQueryBuilder('licenses')
            ->select()
            ->where('licenses.contextId = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();
        $licenses = $query->getResult();

        foreach ($licenses as $license) {
            $result[] = $license;
        }

        return $result;
    }

    public function getLicense ($licenseId) {
        $repository = $this->em->getRepository('CommsyBundle:Licenses');
        $query = $repository->createQueryBuilder('licenses')
            ->select()
            ->where('licenses.id = :licenseId')
            ->setParameter('licenseId', $licenseId)
            ->getQuery();

        if (isset($query->getResult()[0])) {
            return $query->getResult()[0];
        }

        return null;
    }

    public function removeLicense ($licenseId) {
        $this->em->remove($licenseId);

        $this->em->flush();
    }
}
