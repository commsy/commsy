<?php

namespace CommsyBundle\Controller;

use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ICalController extends Controller
{
    /**
     * @Route("/ical/{contextId}")
     */
    public function getContentAction($contextId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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

        $userItem = null;

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

        // prepare response
        $response = new Response($calendar->render());
        $response->headers->set('Content-Type', 'text/calendar');
        $response->setCharset('utf-8');
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $contextId . '.ics'
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function createCalendar($currentContextItem, $export, $calendarId)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $translator = $legacyEnvironment->getTranslationObject();

        // setup calendar
        $calendar = new Calendar('www.commsy.net');

        $dateList = $this->getDateList($currentContextItem, $export, $calendarId);

        // get ids auf all items
        $itemIdArray = [];
        $item = $dateList->getFirst();
        while ($item) {
            $itemIdArray[] = $item->getItemID();

            $item = $dateList->getNext();
        }

        // get a list of all related users
        $linkItemManager = $legacyEnvironment->getLinkItemManager();
        $linkItemManager->setTypeLimit('user');
        $linkItemManager->setIDArrayLimit($itemIdArray);
        $linkItemManager->setRoomLimit($currentContextItem->getItemID());
        $linkItemManager->select2(false);
        $linkedUserList = $linkItemManager->get();

        // build linked user array
        $linkedUserArray = [];
        foreach ($itemIdArray as $itemId) {
            $tempArray = [];
            $linkedUser = $linkedUserList->getFirst();
            while ($linkedUser) {
                if ($linkedUser->getFirstLinkedItemID() == $itemId) {
                    $tempArray[] = $linkedUser->getSecondLinkedItemID();
                }

                $linkedUser = $linkedUserList->getNext();
            }

            $linkedUserArray[$itemId] = $tempArray;
        }

        // array with user ids
        $userIdArray = [];
        $linkedUser = $linkedUserList->getFirst();
        while ($linkedUser) {
            if (!in_array($linkedUser->getSecondLinkedItemID(), $linkedUserArray)) {
                $userIdArray[] = $linkedUser->getSecondLinkedItemID();
            }

            $linkedUser = $linkedUserList->getNext();
        }

        // query user manager
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setIDArrayLimit($userIdArray);
        $userManager->select();
        $userList = $userManager->get();

        $item = $dateList->getFirst();
        while ($item) {
            // create new calendar event
            $event = new Event();

            // setup organizer
            $creatorItem = $item->getCreator();
            $fullName = $creatorItem->getFullName();
            $email = $creatorItem->getEmail();

            if (!empty($fullName) && !empty($email)) {
                $event->setOrganizer(new Organizer("MAILTO:$email", [
                    'CN' => $fullName,
                ]));
            }

            // attendee
            $userItemIdArray = $linkedUserArray[$item->getItemID()];
            foreach ($userItemIdArray as $userId) {
                $userItem = $userList->getFirst();
                while ($userItem) {
                    if ($userItem->getItemID() == $userId) {
                        $email = $userItem->getEmail();
                        $fullName = $userItem->getFullName();

                        if (!empty($email) && !empty($fullName)) {
                            $event->addAttendee("MAILTO:$email", [
                                'CN' => $fullName,
                            ]);
                        }
                    }

                    $userItem = $userList->getNext();
                }
            }

            // title
            $summary = $item->getTitle();
            if ($item->issetPrivatDate()) {
                $summary .= ' [' . $translator->getMessage('DATE_PRIVATE_ENTRY') . ']';
            }
            $event->setSummary($summary);

            // location
            if (!empty($item->getPlace())) {
                $event->setLocation($item->getPlace());
            }

            // url
            $url = $this->generateUrl('commsy_date_detail', [
                'roomId' => $item->getContextID(),
                'itemId' => $item->getItemID(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $event->setUrl($url);

            // unique id
            $event->setUniqueId($item->getItemID());

            // start / end
            $startTime = new \DateTime($item->getDateTime_start());
            $endTime = new \DateTime($item->getDateTime_end());

            $startTime->setTimezone(new \DateTimeZone('UTC'));
            $endTime->setTimezone(new \DateTimeZone('UTC'));

            if ($startTime && $endTime) {
                // Dates with equal start and end date or no start and end time are all day events
                if ($startTime == $endTime || (empty($item->getStartingTime()) && empty($item->getEndingTime()))) {
                    $event->setNoTime(true);
                }

                // Ending dates ending not at the starting day, with starting time and without an exact ending time are considered to
                // span the whole day
                if ($startTime != $endTime && !empty($item->getStartingTime()) && empty($item->getEndingTime())) {
                    $endTime->add(new \DateInterval('P1D'));
                }

                $event
                    ->setDtStart($startTime)
                    ->setDtEnd($endTime)
                    ->setDescription(html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'))
                    ->setStatus(Event::STATUS_CONFIRMED);
            }

            $calendar->addComponent($event);

            $item = $dateList->getNext();
        }

        return $calendar;
    }

    private function getDateList($currentContextItem, $export, $calendarId)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $datesManager = $legacyEnvironment->getDatesManager();

        $datesManager->setWithoutDateModeLimit();

        if (!$legacyEnvironment->inPrivateRoom()) {
            $datesManager->setContextLimit($currentContextItem->getItemID());

            $datesManager->setCalendarArrayLimit([$calendarId]);
        } else {
            $dateSelStatus = $currentContextItem->getRubrikSelection('date', 'status');
            if (!empty($dateSelStatus)) {
                $datesManager->setDateModeLimit($dateSelStatus);
            }

            $dateSelAssignment = $currentContextItem->getRubrikSelection('date', 'assignment');
            if (!empty($dateSelAssignment) && $dateSelAssignment != '2') {
                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
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

            $datesManager->setCalendarArrayLimit([$calendarId]);
        }

        if (!$export) {
            $datesManager->setNotOlderThanMonthLimit(3);
        }

        $datesManager->select();
        $dateList = $datesManager->get();

        if ($legacyEnvironment->inPrivateRoom()) {
            $myEntries = $currentContextItem->getMyCalendarDisplayConfig();

            if (in_array('mycalendar_dates_assigned_to_me', $myEntries)) {
                $tempList = new \cs_list();

                $currentUserItem = $legacyEnvironment->getCurrentUserItem();
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