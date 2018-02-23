<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\Translation;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class TranslationService
{

    /**
     * @var EntityManager $em
     */
    private $em;


    /**
     * @var ContainerInterface $serviceContainer
     */
    private $serviceContainer;


    /**
     * TranslationService constructor.
     * @param EntityManager $entityManager
     * @param Container $container
     */
    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    /**
     * @param $contextId
     * @return array
     */
    public function getTranslations ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Translation');
        $query = $repository->createQueryBuilder('translation')
            ->select()
            ->where('translation.contextId = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();
        $translations= $query->getResult();

        foreach ($translations as $translation) {
            $result[] = $translation;
        }

        return $result;
    }

    /**
     * @param $translationId
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getTranslation ($translationId) {
        return $this->em->find('CommsyBundle\Entity\Translation', $translationId);
    }

    /**
     * @param $translationId
     * @return null|object
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function getTranslationByKey ($contextId, $translationKey, $locale) {
        $result = '';

        $repository = $this->em->getRepository('CommsyBundle:Translation');
        $query = $repository->createQueryBuilder('translation')
            ->select()
            ->where('translation.contextId = :context_id AND translation.translationKey = :translation_key')
            ->setParameter('context_id', $contextId)
            ->setParameter('translation_key', $translationKey)
            ->getQuery();
        $translation = $query->getResult();

        if ($translation[0]) {
            return $translation[0]->getTranslationForLocale($locale);
        }

        return $result;
    }
}