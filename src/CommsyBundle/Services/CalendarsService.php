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
            ->where('calendars.context_id = :context_id')
            ->setParameter('context_id', $contextId)
            ->getQuery();
        $calendars = $query->getResult();

        foreach ($calendars as $calendar) {
            $result[] = $calendar;
        }

        return $result;
    }

    public function getCalendar ($calendarId) {
        $repository = $this->em->getRepository('CommsyBundle:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.id = :calendarId')
            ->setParameter('calendarId', $calendarId)
            ->getQuery();

        return $calendars = $query->getResult();
    }

    public function getDefaultCalendar ($contextId) {
        $repository = $this->em->getRepository('CommsyBundle:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.context_id = :context_id AND calendars.default_calendar = 1')
            ->setParameter('context_id', $contextId)
            ->getQuery();

        return $calendars = $query->getResult();
    }
}