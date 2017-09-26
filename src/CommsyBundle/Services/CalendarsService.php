<?php

namespace CommsyBundle\Services;

use Cdb\Exception;
use CommsyBundle\Entity\Calendars;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Sabre\VObject;
use Sabre\VObject\ParseException;

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

    public function getListExternalCalendars () {
        $result = array();

        $repository = $this->em->getRepository('CommsyBundle:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.external_url <> \'\'')
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

    public function removeCalendar ($calendar) {
        $dateService = $this->serviceContainer->get('commsy_legacy.date_service');
        $dates = $dateService->getDatesByCalendarId($calendar->getId());

        foreach ($dates as $date) {
            $date->setCalendarId($this->getDefaultCalendar($date->getContextId())[0]->getId());
            $date->save();
        }

        $this->em->remove($calendar);

        $this->em->flush();
    }


    public function updateSynctoken ($calendarId) {
        $calendar = $this->getCalendar($calendarId);

        if (isset($calendar[0])) {
            $repository = $this->em->getRepository('CommsyBundle:Calendars');
            $query = $repository->createQueryBuilder('calendars')
                ->update()
                ->set('calendars.synctoken', ($calendar[0]->getSynctoken() + 1))
                ->where('calendars.id = :calendarId')
                ->setParameter('calendarId', $calendarId)
                ->getQuery();
            $query->getResult();
        }
    }

    public function importEvents ($icalData, $calendar, $external = false) {
        $dateService = $this->serviceContainer->get('commsy_legacy.date_service');

        try {
            $ical = VObject\Reader::read($icalData);

            $uids = [];
            if ($ical->VEVENT) {
                foreach ($ical->VEVENT as $event) {
                    $title = '';
                    if ($event->SUMMARY) {
                        $title = $event->SUMMARY->getValue();
                    }

                    $startDatetime = '';
                    if ($event->DTSTART) {
                        $startDatetime = $event->DTSTART->getDateTime();
                    }

                    $endDatetime = '';
                    if ($event->DTEND) {
                        $endDatetime = $event->DTEND->getDateTime();
                    } else {
                        $endDatetime = $startDatetime;
                    }

                    $location = '';
                    if ($event->LOCATION) {
                        $location = $event->LOCATION->getValue();
                    }

                    $description = '';
                    if ($event->DESCRIPTION) {
                        $description = $event->DESCRIPTION->getValue();
                    }

                    $uid = '';
                    if ($event->UID) {
                        $uid = $event->UID->getValue();
                        $uids[] = $uid;
                    }

                    $wholeDay = false;
                    if ($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}) {
                        if($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}->getValue() == 'TRUE') {
                            $wholeDay = true;
                        }
                    }

                    $attendee = '';
                    $attendeeArray = array();
                    if ($event->ORGANIZER) {
                        $tempOrganizerString = '';
                        if (isset($event->ORGANIZER['CN'])) {
                            $tempOrganizerString .= $event->ORGANIZER['CN'];
                        }
                        $attendeeArray[] = $tempOrganizerString.' (<a href="'.$event->ORGANIZER->getValue().'">'.str_ireplace('MAILTO:', '', $event->ORGANIZER->getValue()).'</a>)';
                    }
                    if ($event->ATTENDEE) {
                        foreach ($event->ATTENDEE as $tempAttendee) {
                            $tempAttendeeString = '';
                            if (isset($tempAttendee['CN'])) {
                                $tempAttendeeString .= $tempAttendee['CN'];
                            }
                            $attendeeArray[] = $tempAttendeeString.' (<a href="'.$tempAttendee->getValue().'">'.str_ireplace('MAILTO:', '', $tempAttendee->getValue()).'</a>)';
                        }
                    }
                    if (!empty($attendeeArray)) {
                        $attendee = implode("<br/>", array_unique($attendeeArray));
                    }

                    if ($external) {
                        $date = $dateService->getNewDate();
                    } else {
                        if (!$date = $dateService->getDateByUid($uid, $calendar->getId())) {
                            $date = $dateService->getNewDate();
                        }
                    }
                    $date->setContextId($calendar->getContextId());
                    $date->setTitle($title);
                    $date->setDateTime_start($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    $date->setStartingDay($startDatetime->format('Y-m-d'));
                    $date->setStartingTime($startDatetime->format('H:i'));
                    $date->setDateTime_end($endDatetime->format('Ymd') . 'T' . $endDatetime->format('His'));
                    $date->setEndingDay($endDatetime->format('Y-m-d'));
                    $date->setEndingTime($endDatetime->format('H:i'));
                    $date->setWholeDay($wholeDay);
                    $date->setCalendarId($calendar->getId());
                    $date->setPlace($location);
                    $date->setDescription($description . "<br/><br/>" . $attendee);
                    $date->setUid($uid);
                    if ($calendar->getCreatorId()) {
                        $date->setCreatorId($calendar->getCreatorId());
                        $date->setModifierId($calendar->getCreatorId());
                    } else {
                        $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();
                        $date->setCreatorId($legacyEnvironment->getRootUserItemID());
                        $date->setModifierId($legacyEnvironment->getRootUserItemID());
                    }

                    $eventDateTime = new \DateTime($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                    $nowDateTime = new \DateTime();
                    if ($eventDateTime < $nowDateTime) {
                        $date->setModificationDate($startDatetime->format('Ymd') . 'T' . $startDatetime->format('His'));
                        $date->setChangeModificationOnSave(false);
                    }

                    if ($external) {
                        $date->setExternal(true);
                    }
                    $date->save();
                }
            }

            foreach ($dateService->getListDates($calendar->getContextId(), null, null, null) as $date) {
                if ($date->getCalendarId() == $calendar->getId()) {
                    if (!in_array($date->getUid(), $uids)) {
                        $date->delete();
                    }
                }
            }

            if (!$external) {
                // check for dates with uid not in imported ical. Delete those.
            }

        } catch (ParseException $e) {
            return $e->getMessage();
        }

        return true;
    }

    public function createCalendar($roomItem, $title = null, $color = null, $default = null) {
        $translator =  $this->serviceContainer->get('translator');

        $calendar = new Calendars();

        if (!$title) {
            $title = $translator->trans('Standard', array(), 'date');
        }
        $calendar->setTitle($title);

        $calendar->setContextId($roomItem->getItemId());
        $calendar->setCreatorId($roomItem->getCreatorItem()->getItemId());

        if (!$color) {
            $color = '#ffffff';
        }
        $calendar->setColor($color);

        if ($default) {
            $calendar->setDefaultCalendar(true);
        }

        $calendar->setSynctoken(0);
        $this->em->persist($calendar);
        $this->em->flush();
    }
}