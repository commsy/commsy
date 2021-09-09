<?php

namespace App\Services;

use App\Entity\Calendars;
use App\Utils\DateService;
use App\Utils\RoomService;
use cs_environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Sabre\VObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class CalendarsService
{
    /**
     * @var EntityManagerInterface $em
     */
    private EntityManagerInterface $em;

    /**
     * @var Container
     */
    private Container $serviceContainer;

    /**
     * @var DateService
     */
    private DateService $dateService;

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    /**
     * @var TranslatorInterface
     */
    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        Container $container,
        DateService $dateService,
        LegacyEnvironment $legacyEnvironment,
        TranslatorInterface $translator
    ) {
        $this->em = $entityManager;
        $this->dateService = $dateService;
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->translator = $translator;
        $this->serviceContainer = $container;
    }

    public function getListCalendars ($contextId) {
        $result = array();

        $repository = $this->em->getRepository('App:Calendars');
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

        $repository = $this->em->getRepository('App:Calendars');
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
        $repository = $this->em->getRepository('App:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.id = :calendarId')
            ->setParameter('calendarId', $calendarId)
            ->getQuery();

        return $calendars = $query->getResult();
    }

    public function getDefaultCalendar ($contextId) {
        $repository = $this->em->getRepository('App:Calendars');
        $query = $repository->createQueryBuilder('calendars')
            ->select()
            ->where('calendars.context_id = :context_id AND calendars.default_calendar = 1')
            ->setParameter('context_id', $contextId)
            ->getQuery();

        return $calendars = $query->getResult();
    }

    /**
     * Deletes the given calendar and all of its contained date entries.
     * @var Calendars $calendar
     */
    public function removeCalendar(RoomService $roomService, $calendar)
    {
        $this->removeAllCalendarDates($roomService, $calendar);

        $this->em->remove($calendar);
        $this->em->flush();
    }


    /**
     * Deletes all date entries from the given calendar.
     * @var Calendars $calendar
     */
    public function removeAllCalendarDates(RoomService $roomService, $calendar)
    {
        $dates = $this->dateService->getDatesByCalendarId($calendar->getId());
        if (empty($dates)) {
            return;
        }

        $roomId = $calendar->getContextId();
        $roomItem = $roomService->getRoomItem($roomId);

        // NOTE: we execute the DeleteDate action (and not just \cs_item->delete()) since
        // this performs additional cleanup like removing any date items from the clipboard
        $action = $this->serviceContainer->get('commsy.action.delete.date');

        return $action->execute($roomItem, $dates);
    }


    public function updateSynctoken ($calendarId) {
        $calendar = $this->getCalendar($calendarId);

        if (isset($calendar[0])) {
            $repository = $this->em->getRepository('App:Calendars');
            $query = $repository->createQueryBuilder('calendars')
                ->update()
                ->set('calendars.synctoken', ($calendar[0]->getSynctoken() + 1))
                ->where('calendars.id = :calendarId')
                ->setParameter('calendarId', $calendarId)
                ->getQuery();
            $query->getResult();
        }
    }

    /**
     * Imports all events from the given iCalendar file(s) into the given CommSy calendar.
     * Note that if the given CommSy calendar already contains date items with the same UID, these date items will be
     * updated with the data from the imported iCalendar events. Any other date items in the given CommSy calendar (which
     * don't have a corresponding event with a matching UID in the given iCalendar data) will be deleted.
     * @param mixed $icalData The iCalendar file (or array of iCalendar files) containing VEVENT objects that shall be imported.
     * @param Calendars $calendar The CommSy calendar object that will contain the imported iCalendar events.
     * @param bool $external True if the given iCalendar data are from an external calendar source, otherwise false;
     * defaults to false.
     * @return bool|string Returns true if the events from the given iCalendar file(s) could be imported successfully,
     * otherwise returns the error message.
     */
    public function importEvents ($icalData, $calendar, $external = false) {
        try {
            if (is_array($icalData)) {
                // merge multiple iCalendar files into a single iCalendar
                $ical = new VObject\Component\VCalendar();
                foreach ($icalData as $icalFile) {
                    $tempIcal = VObject\Reader::read($icalFile);
                    foreach ($tempIcal->VEVENT as $event) {
                        $ical->add($event);
                    }
                }
            }
            else {
                $ical = VObject\Reader::read($icalData);
            }

            $roomId = $calendar->getContextId();

            $uids = [];
            if ($ical->VEVENT) {
                foreach ($ical->VEVENT as $event) {
                    $title = '';
                    if ($event->SUMMARY) {
                        $title = $event->SUMMARY->getValue();
                    }

                    // is the event an all-day event? (potentially spanning multiple days)
                    $wholeDay = $this->isAllDayEvent($event);

                    $currentTimeZone = new \DateTimeZone(date_default_timezone_get());

                    $startDatetime = '';
                    if ($event->DTSTART) {
                        $startDatetime = $event->DTSTART->getDateTime();
                        $startDatetime = $startDatetime->setTimezone($currentTimeZone);
                        if ($wholeDay) {
                            $startDatetime = $startDatetime->setTime(0, 0); // all-day events always start at midnight
                        }
                    }

                    $endDatetime = '';
                    if ($event->DTEND) {
                        $endDatetime = $event->DTEND->getDateTime();
                        $endDatetime = $endDatetime->setTimezone($currentTimeZone);
                    } else {
                        if ($event->DURATION) {
                            $dateInterval = $event->DURATION->getDateInterval();
                            $endDatetime = $startDatetime->add($dateInterval);
                        }
                    }

                    if (!empty($endDatetime)) {
                        if ($wholeDay) {
                            // NOTE: for all-day events, we substract 1 day from the end day; this is since
                            // DTEND is exclusive, not inclusive, so the given end day isn't part of the event
                            $endDatetime = $endDatetime->sub(new \DateInterval('P1D'));

                            $endDatetime = $endDatetime->setTime(0, 0); // 23:59 might be more appropriate?
                        }
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
                    $date = $this->dateService->getDateByUid($uid, $calendar->getId(), $roomId);
                    if (!$date) {
                        $date = $this->dateService->getNewDate();
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
                        $creatorId = $this->legacyEnvironment->getRootUserItemID();
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

            foreach ($this->dateService->getListDates($roomId, null, null, null) as $date) {
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

    /**
     * Returns true if the given ICALENDAR VEVENT is an all-day event (which may span multiple days),
     * otherwise returns false.
     * @var Sabre\VObject\Component\VEvent $event
     * @return bool
     */
    public function isAllDayEvent($event)
    {
        // 1. proprietary value defined
        if ($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}) {
            if ($event->{'X-MICROSOFT-CDO-ALLDAYEVENT'}->getValue() == 'TRUE') {
                return true;
            }
        }

        // alternatively, check for all of these cases:

        // 2. date-only DTSTART & DTEND defined
        // NOTE: DTEND is exclusive, not inclusive, so this would be an all-day event for 03-Dec-2018:
        //   DTSTART;VALUE=DATE:20181203
        //   DTEND;VALUE=DATE:20181204

        // 3. date-only DTSTART & DURATION of 1 (or more) full day(s) defined
        //   DTSTART;VALUE=DATE:20181203
        //   DURATION:P1D

        // 4. just date-only DTSTART defined (in ICALENDAR, the default duration is one day)
        //   DTSTART;VALUE=DATE:20181203

        $startDateTime = NULL;
        $formattedStartTime = '';
        if ($event->DTSTART) {
            $startDateTime = $event->DTSTART->getDateTime();
            $formattedStartTime = $startDateTime->format('H:i:s');
        }
        if (empty($startDateTime) || $formattedStartTime !== "00:00:00") {
            return false;
        }

        $dateInterval = new \DateInterval('P1D'); // ICALENDAR default
        $endDateTime = NULL;
        $formattedEndTime = '';
        if ($event->DTEND) {
            $endDateTime = $event->DTEND->getDateTime();
            $formattedEndTime = $endDateTime->format('H:i:s');
        }

        if (!empty($endDateTime)) {
            if ($formattedEndTime !== "00:00:00") {
                return false;
            }

            $dateInterval = $startDateTime->diff($endDateTime);

        } else {
            if ($event->DURATION) {
                $dateInterval = $event->DURATION->getDateInterval();
            }
        }

        return ($dateInterval->d >= 1 &&
                $dateInterval->h === 0 &&
                $dateInterval->i === 0 &&
                $dateInterval->s === 0 &&
                $dateInterval->f === 0.0);
    }

    public function createCalendar($roomItem, $title = null, $color = null, $default = null) {
        $calendar = new Calendars();

        if (!$title) {
            $title = $this->translator->trans('Standard', array(), 'date');
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