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

    private $serviceContainer;

    public function __construct(EntityManager $entityManager, Container $container)
    {
        $this->em = $entityManager;
        $this->serviceContainer = $container;
    }

    public function getTranslations ($contextId) {
        
    }

    public function getTranslation ($contextId, $translationKey, $locale) {

    }
}