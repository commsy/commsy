<?php

namespace CommsyBundle\Services;

use CommsyBundle\Entity\Invitations;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class CalendarsService
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

    public function getListCalendars ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.contextId = :contextId')
            ->setParameter('contextId', $contextId)
            ->getQuery();
        $calendars = $query->getResult();

        foreach ($calendars as $calendar) {
            $result[] = $calendar;
        }

        return $result;
    }
}