<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\RoomCategories;
use CommsyBundle\Entity\RoomCategoriesLinks;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Sabre\VObject;

class TermsService
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

    public function getListTerms ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Terms');
        $query = $repository->createQueryBuilder('terms')
            ->select()
            ->where('terms.contextId = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();
        $terms = $query->getResult();

        foreach ($terms as $term) {
            $result[] = $term;
        }

        return $result;
    }

    public function getTerms ($termsId) {
        $repository = $this->em->getRepository('CommsyBundle:Terms');
        $query = $repository->createQueryBuilder('terms')
            ->select()
            ->where('terms.id = :termId')
            ->setParameter('termId', $termsId)
            ->getQuery();

        if (isset($query->getResult()[0])) {
            return $query->getResult()[0];
        }

        return null;
    }
}