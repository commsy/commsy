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
            $roomId = $calendar->getContextId();

            $uids = [];
            if ($ical->VEVENT) {
                foreach ($ical->VEVENT as $event) {
                    $title = '';
                    if ($event->SUMMARY) {
                        $title = $event->SUMMARY->getValue();
                    }

                    $currentTimeZone = new \DateTimeZone(date_default_timezone_get());

                    $startDatetime = '';
                    if ($event->DTSTART) {
                        $startDatetime = $event->DTSTART->getDateTime();
                        $startDatetime = $startDatetime->setTimezone($currentTimeZone);
                    }

                    $endDatetime = '';
                    if ($event->DTEND) {
                        $endDatetime = $event->DTEND->getDateTime();
                        $endDatetime = $endDatetime->setTimezone($currentTimeZone);
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
                        // lowercase "mailto:" is required for proper comparison with the sanitized description from `cs_date_item->getDescription()` below
                        $organizerMailto = str_ireplace('MAILTO:', 'mailto:', $event->ORGANIZER->getValue());
                        $organizerEmail = str_ireplace('MAILTO:', '', $event->ORGANIZER->getValue());
                        $attendeeArray[] = $tempOrganizerString.' (<a href="'.$organizerMailto.'">'.$organizerEmail.'</a>)';
                    }
                    if ($event->ATTENDEE) {
                        foreach ($event->ATTENDEE as $tempAttendee) {
                            $tempAttendeeString = '';
                            if (isset($tempAttendee['CN'])) {
                                $tempAttendeeString .= $tempAttendee['CN'];
                            }
                            $attendeeMailto = str_ireplace('MAILTO:', 'mailto:', $tempAttendee->getValue());
                            $attendeeEmail = str_ireplace('MAILTO:', '', $tempAttendee->getValue());
                            $attendeeArray[] = $tempAttendeeString.' (<a href="'.$organizerMailto.'">'.$organizerEmail.'</a>)';
                        }
                    }
                    if (!empty($attendeeArray)) {
                        $attendee = implode("<br/>", array_unique($attendeeArray));
                    }

                    // try to find existing date item
                    $hasChanges = false;
                    $date = $dateService->getDateByUid($uid, $calendar->getId(), $roomId);
                    if (!$date) {
                        $date = $dateService->getNewDate();
                        if ($external) {
                            $date->setExternal(true);
                        }
                        $hasChanges = true; // all date item properties are new
                    }

                    // set (or update) date item properties
                    if ($hasChanges || $hasChanges = ($date->getContextID() !== $roomId)) {
                        $date->setContextID($roomId);
                    }

                    if ($hasChanges || $hasChanges = ($date->getTitle() !== $title)) {
                        $date->setTitle($title);
                    }

                    $dbStartDatetime = $startDatetime->format('Ymd') . 'T' . $startDatetime->format('His');
                    // compare DateTime objects to account for differing formats
                    if ($hasChanges || $hasChanges = (new \DateTime($date->getDateTime_start()) != $startDatetime)) {
                        $date->setDateTime_start($dbStartDatetime);
                    }

                    $dbStartingDay = $startDatetime->format('Y-m-d');
                    if ($hasChanges || $hasChanges = ($date->getStartingDay() !== $dbStartingDay)) {
                        $date->setStartingDay($dbStartingDay);
                    }

                    $dbStartingTime = $startDatetime->format('H:i');
                    if ($hasChanges || $hasChanges = ($date->getStartingTime() !== $dbStartingTime)) {
                        $date->setStartingTime($dbStartingTime);
                    }

                    $dbEndDatetime = $endDatetime->format('Ymd') . 'T' . $endDatetime->format('His');
                    if ($hasChanges || $hasChanges = (new \DateTime($date->getDateTime_end()) != $endDatetime)) {
                        $date->setDateTime_end($dbEndDatetime);
                    }

                    $dbEndingDay = $endDatetime->format('Y-m-d');
                    if ($hasChanges || $hasChanges = ($date->getEndingDay() !== $dbEndingDay)) {
                        $date->setEndingDay($dbEndingDay);
                    }

                    $dbEndingTime = $endDatetime->format('H:i');
                    if ($hasChanges || $hasChanges = ($date->getEndingTime() !== $dbEndingTime)) {
                        $date->setEndingTime($dbEndingTime);
                    }

                    if ($hasChanges || $hasChanges = ($date->isWholeDay() !== $wholeDay)) {
                        $date->setWholeDay($wholeDay);
                    }

                    $calendarId = $calendar->getId();
                    if ($hasChanges || $hasChanges = ((int)$date->getCalendarId() !== $calendarId)) {
                        $date->setCalendarId($calendarId);
                    }

                    if ($hasChanges || $hasChanges = ($date->getPlace() !== $location)) {
                        $date->setPlace($location);
                    }

                    $dbDescription = $description . "<br /><br />" . $attendee;
                    if ($hasChanges || $hasChanges = ($date->getDescription() !== $dbDescription)) {
                        $date->setDescription($dbDescription);
                    }

                    // for VCALENDAR 2.0, the UID should be globally unique and stable, but this isn't always the case in the real world
                    if ($hasChanges || $hasChanges = ($date->getUid() !== $uid)) {
                        $date->setUid($uid);
                    }

                    $creatorId = $calendar->getCreatorId();
                    if (!$creatorId) {
                        $legacyEnvironment = $this->serviceContainer->get('commsy_legacy.environment')->getEnvironment();
                        $creatorId = $legacyEnvironment->getRootUserItemID();
                    }
                    if ($hasChanges || $hasChanges = ((int)$date->getCreatorID() !== $creatorId)) {
                        $date->setCreatorID($creatorId);
                    }
                    if ($hasChanges || $hasChanges = ((int)$date->getModificatorID() !== $creatorId)) {
                        $date->setModifierID($creatorId);
                    }

                    if (!$hasChanges) {
                        // for existing date items, don't update their modification date if nothing has changed
                        $date->setChangeModificationOnSave(false);
                    } else {
                        // for date items that lie in the past, assign their start date as modification date
                        // (for upcoming date items which have changes, the current date will be used on save)
                        $eventDateTime = new \DateTime($dbStartDatetime);
                        $nowDateTime = new \DateTime();
                        if ($eventDateTime < $nowDateTime) {
                            $date->setModificationDate($dbStartDatetime);
                            $date->setChangeModificationOnSave(false);
                        }
                    }

                    $date->save();
                }
            }

            foreach ($dateService->getListDates($roomId, null, null, null) as $date) {
                if ($date->getCalendarId() == $calendar->getId()) {
                    if (!in_array($date->getUid(), $uids)) {
                        $date->delete();
                    }
                }
            }

        } catch (\Exception $e) {
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