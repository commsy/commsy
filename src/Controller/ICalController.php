<?php

namespace App\Controller;

use App\Services\LegacyEnvironment;
use cs_dates_item;
use cs_environment;
use cs_item;
use cs_list;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Eluceo\iCal\Domain\Entity\Attendee;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Entity\TimeZone;
use Eluceo\iCal\Domain\ValueObject\Date as EventDate;
use Eluceo\iCal\Domain\ValueObject\DateTime as EventDateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\MultiDay;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Domain\ValueObject\Uri;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ICalController extends AbstractController
{

    /**
     * @var cs_environment
     */
    protected cs_environment $legacyEnvironment;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    /**
     * @required
     * @param LegacyEnvironment $legacyEnvironment
     */
    public function setLegacyEnvironment(LegacyEnvironment $legacyEnvironment): void
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/ical/{contextId}")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $contextId
     * @return Response
     * @throws Exception
     */
    public function getContentAction(
        Request $request,
        LegacyEnvironment $environment,
        int $contextId
    ): Response {
        $legacyEnvironment = $environment->getEnvironment();

        $legacyEnvironment->setCurrentContextID($contextId);
        $currentContextItem = $legacyEnvironment->getCurrentContextItem();

        $granted = false;
        if ($currentContextItem->isOpenForGuests()) {
            $granted = true;
        } else {
            if (!$currentContextItem->isPortal() && !$currentContextItem->isServer()) {
                if (!$currentContextItem->isLocked()) {
                    if ($request->query->has('hid')) {
                        $hash = $request->query->get('hid');

                        $hashManager = $legacyEnvironment->getHashManager();
                        if ($hashManager->isICalHashValid($hash, $currentContextItem)) {
                            $granted = true;
                        }
                    }
                }
            }
        }

        if (!$granted) {
            throw new AccessDeniedHttpException();
        }

        if ($request->query->has('hid')) {
            $hash = $request->query->get('hid');
            $hashManager = $legacyEnvironment->getHashManager();
            $userItem = $hashManager->getUserByICalHash($hash);
            $legacyEnvironment->setCurrentUserItem($userItem);
        }

        // export
        $export = $request->query->has('export');

        // export
        $calendarId = $request->query->get('calendar_id');
        if (!$calendarId) {
            $calendarId = $currentContextItem->getDefaultCalendarId();
        }

        $calendar = $this->createCalendar($currentContextItem, $export, $calendarId);
        $calendarComponent = (new CalendarFactory())->createCalendar($calendar);

        // prepare response
        $response = new Response($calendarComponent);
        $response->headers->set('Content-Type', 'text/calendar');
        $response->setCharset('utf-8');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $contextId . '.ics'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @throws Exception
     */
    private function createCalendar(
        $currentContextItem,
        $export,
        $calendarId
    ): Calendar {
        // setup calendar
        $calendar = new Calendar($this->generateEvents($currentContextItem, $export, $calendarId));
        $calendar->setProductIdentifier('www.commsy.net');

        // time zone
        $dtz = new DateTimeZone($this->getParameter('commsy.dates.timezone'));
        $timeZone = TimeZone::createFromPhpDateTimeZone(
            $dtz,
            (new DateTimeImmutable())->sub(new DateInterval('P5Y')),
            (new DateTimeImmutable())->add(new DateInterval('P5Y')),
        );
        $calendar->addTimeZone($timeZone);

        return $calendar;
    }

    /**
     * @throws Exception
     */
    private function generateEvents(cs_item $currentContextItem, bool $export, int $calendarId): Generator
    {
        $dateList = $this->getDateList($currentContextItem, $export, $calendarId);

        // get ids auf all items
        $itemIdArray = [];
        foreach ($dateList as $date) {
            /** @var $date */
            $itemIdArray[] = $date->getItemID();
        }

        // get a list of all related users
        $linkItemManager = $this->legacyEnvironment->getLinkItemManager();
        $linkItemManager->setTypeLimit('user');
        $linkItemManager->setIDArrayLimit($itemIdArray);
        $linkItemManager->setRoomLimit($currentContextItem->getItemID());
        $linkItemManager->select2(false);
        $linkedUserList = $linkItemManager->get();

        // build linked user array
        $linkedUserArray = [];
        foreach ($itemIdArray as $itemId) {
            $tempArray = [];

            foreach ($linkedUserList as $linkedUser) {
                if ($linkedUser->getFirstLinkedItemID() == $itemId) {
                    $tempArray[] = $linkedUser->getSecondLinkedItemID();
                }
            }

            $linkedUserArray[$itemId] = $tempArray;
        }

        // array with user ids
        $userIdArray = [];
        foreach ($linkedUserList as $linkedUser) {
            if (!in_array($linkedUser->getSecondLinkedItemID(), $linkedUserArray)) {
                $userIdArray[] = $linkedUser->getSecondLinkedItemID();
            }
        }

        // query user manager
        $userManager = $this->legacyEnvironment->getUserManager();
        $userManager->setContextLimit($this->legacyEnvironment->getCurrentContextID());
        $userManager->setIDArrayLimit($userIdArray);
        $userManager->select();
        $userList = $userManager->get();

        foreach ($dateList as $item) {
            /** @var cs_dates_item $item */

            // create new calendar event
            $event = new Event(new UniqueIdentifier($item->getItemID()));

            // setup organizer
            $creatorItem = $item->getCreator();
            $fullName = $creatorItem->getFullName();
            $email = $creatorItem->getEmail();

            if (!empty($fullName) && !empty($email)) {
                $event->setOrganizer(new Organizer(new EmailAddress($email), $fullName));
            }

            // attendee
            $userItemIdArray = $linkedUserArray[$item->getItemID()];
            foreach ($userItemIdArray as $userId) {
                foreach ($userList as $userItem) {
                    if ($userItem->getItemID() == $userId) {
                        $email = $userItem->getEmail();

                        if (!empty($email)) {
                            $event->addAttendee(new Attendee(new EmailAddress($email)));
                        }
                    }
                }
            }

            // title
            $summary = html_entity_decode($item->getTitle());
            if ($item->issetPrivatDate()) {
                $summary .= ' [' . $this->translator->trans('date.private', [], 'date') . ']';
            }
            $event->setSummary($summary);

            // location
            if (!empty($item->getPlace())) {
                $event->setLocation(new Location($item->getPlace()));
            }

            // url
            $url = $this->generateUrl('app_date_detail', [
                'roomId' => $item->getContextID(),
                'itemId' => $item->getItemID(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $event->setUrl(new Uri($url));

            // start / end
            $dtz = new DateTimeZone($this->getParameter('commsy.dates.timezone'));
            $startTime = new DateTime($item->getDateTime_start());
            $endTime = new DateTime($item->getDateTime_end());
            $startTime->setTimezone($dtz);
            $endTime->setTimezone($dtz);

            // Ending dates ending not at the starting day, with starting time and without an exact ending time are considered to
            // span the whole day
            if ($startTime != $endTime && !empty($item->getStartingTime()) && empty($item->getEndingTime())) {
                $endTime->add(new DateInterval('P1D'));
            }

            if ($item->isWholeDay()) {
                if ($startTime == $endTime) {
                    $date = new EventDate($startTime);
                    $event->setOccurrence(new SingleDay($date));
                } else {
                    $firstDay = new EventDate($startTime);
                    $lastDay = new EventDate($endTime);
                    $event->setOccurrence(new MultiDay($firstDay, $lastDay));
                }
            } else {
                $start = new EventDateTime($startTime, false);
                $end = new EventDateTime($endTime, false);
                $event->setOccurrence(new TimeSpan($start,$end));
            }

            // description
            $event->setDescription(html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'));

            yield $event;
        }
    }

    private function getDateList(
        $currentContextItem,
        $export,
        $calendarId
    ): ?cs_list {
        $datesManager = $this->legacyEnvironment->getDatesManager();

        $datesManager->setWithoutDateModeLimit();
        $datesManager->setCalendarArrayLimit([$calendarId]);

        if (!$this->legacyEnvironment->inPrivateRoom()) {
            $datesManager->setContextLimit($currentContextItem->getItemID());
        } else {
            $dateSelStatus = $currentContextItem->getRubrikSelection('date', 'status');
            if (!empty($dateSelStatus)) {
                $datesManager->setDateModeLimit($dateSelStatus);
            }

            $dateSelAssignment = $currentContextItem->getRubrikSelection('date', 'assignment');
            if (!empty($dateSelAssignment) && $dateSelAssignment != '2') {
                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
                $userList = $currentUserItem->getRelatedUserList();

                $userIdArray = [];
                $userItem = $userList->getFirst();
                while ($userItem) {
                    $userIdArray[] = $userItem->getItemID();

                    $userItem = $userList->getNext();
                }

                $datesManager->setAssignmentLimit($userIdArray);
            }

            $myEntries = $currentContextItem->getMyCalendarDisplayConfig();
            $myRooms = [];
            foreach ($myEntries as $entry) {
                $expEntry = explode('_', $entry);

                if (sizeof($expEntry) == 2) {
                    if ($expEntry[1] == 'dates' || $expEntry[1] == 'todo') {
                        $entry = str_replace('_dates', '', $entry);
                        $entry = str_replace('_todo', '', $entry);

                        $myRooms[] = $entry;
                    }
                }
            }

            // add private room to array
            $myRooms[] = $currentContextItem->getItemID();

            $datesManager->setContextArrayLimit($myRooms);
        }

        if (!$export) {
            // for external clients subscribing to our calendar content,
            // show at least the last year of events (plus some more)
            $datesManager->setNotOlderThanMonthLimit(13);
        }

        $datesManager->select();
        $dateList = $datesManager->get();

        if ($this->legacyEnvironment->inPrivateRoom()) {
            $myEntries = $currentContextItem->getMyCalendarDisplayConfig();

            if (in_array('mycalendar_dates_assigned_to_me', $myEntries)) {
                $tempList = new cs_list();

                $currentUserItem = $this->legacyEnvironment->getCurrentUserItem();
                $currentUserList = $currentUserItem->getRelatedUserList();

                $tempElement = $dateList->getFirst();
                while ($tempElement) {
                    $tempUser = $currentUserList->getFirst();
                    while ($tempUser) {
                        if ($tempElement->isParticipant($tempUser)) {
                            $tempList->add($tempElement);
                        }

                        $tempUser = $currentUserList->getNext();
                    }

                    $tempElement = $dateList->getNext();
                }

                $dateList = $tempList;
            }
        }

        return $dateList;
    }
}