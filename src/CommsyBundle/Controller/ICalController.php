<?php

namespace CommsyBundle\Controller;

use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Description\Location;
use Jsvrcek\ICS\Model\Relationship\Attendee;
use Jsvrcek\ICS\Model\Relationship\Organizer;
use Jsvrcek\ICS\Utility\Formatter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $calendar = $this->createCalendar($currentContextItem, $export);

        // setup exporter
        $calendarExport = new CalendarExport(new CalendarStream(), new Formatter());
        $calendarExport->addCalendar($calendar);

        $response = new Response($calendarExport->getStream());
        $response->headers->set('Content-Type', 'text/calendar');

        return $response;
    }

    private function createCalendar($currentContextItem, $export)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $translator = $legacyEnvironment->getTranslationObject();

        // setup calendar
        $calendar = new Calendar();

        $dateList = $this->getDateList($currentContextItem, $export);
        // $todoList = ....

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

        // quere user manager
        $userManager = $legacyEnvironment->getUserManager();
        $userManager->setContextLimit($legacyEnvironment->getCurrentContextID());
        $userManager->setIDArrayLimit($userIdArray);
        $userManager->select();
        $userList = $userManager->get();

        $item = $dateList->getFirst();
        while ($item) {
            // create new calendar event
            $event = new CalendarEvent();

            // setup organizer
            $organizer = new Organizer(new Formatter());

            $creatorItem = $item->getCreator();
            $fullName = $creatorItem->getFullName();
            $email = $creatorItem->getEmail();

            if (!empty($fullName) && !empty($email)) {
                $organizer->setValue($creatorItem->getEmail());
                $organizer->setName($creatorItem->getFullName());
            }
            $organizer->setLanguage($creatorItem->getLanguage());
            $event->setOrganizer($organizer);

            // attendee
            $userItemIdArray = $linkedUserArray[$item->getItemID()];
            foreach ($userItemIdArray as $userId) {
                $userItem = $userList->getFirst();
                while ($userItem) {
                    if ($userItem->getItemID() == $userId) {
                        $attendee = new Attendee(new Formatter());

                        $attendee->setValue($userItem->getEmail());
                        $attendee->setName($userItem->getFullName());

                        $event->addAttendee($attendee);
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
                $location = new Location();
                $location->setName($item->getPlace());
                $event->addLocation($location);
            }

            // start / end
            $startTime = new \DateTime($item->getDateTime_start());
            $endTime = new \DateTime($item->getDateTime_end());
            if ($startTime >= $endTime) {
                // add one hour
                $endTime = $startTime;
                $endTime->add(new \DateInterval('PT1H'));
            }

            $allDay = false;
            if ($startTime == $endTime) {
                // start at midnight?
                if (    $startTime->format('H') == 0 &&
                        $startTime->format('i') == 0 &&
                        $startTime->format('s') == 0) {
                    $startTime->add(new \DateInterval('P1D'));
                }

                $allDay = true;
            } else if(  $startTime->format('H') == 0 &&
                        $startTime->format('i') == 0 &&
                        $startTime->format('s') == 0 &&
                        $endTime->format('H') == 0 &&
                        $endTime->format('i') == 0 &&
                        $endTime->format('s') == 0) {
                $allDay = true;
            }

            //$categories = ['CommSy .' . $translator->getMessage('COMMON_DATES')];

            // url
            $url = $this->generateUrl('commsy_date_detail', [
                'roomId' => $item->getContextID(),
                'itemId' => $item->getItemID(),
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $event->setUrl($url);

            $event->setStart($startTime);
            $event->setEnd($endTime);
            $event->setUid($item->getItemID());
            $event->setDescription(html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES));
            $event->setAllDay($allDay);

            $calendar->addEvent($event);


//            if ($current_module == CS_TODO_TYPE) {
//                $categories = array('CommSy .' . $translator->getMessage('COMMON_TODOS'));
//                $temp_array = array();
//                $attendee_array = array();
//                $temp_array = array();
//                $attendee_array = array();
//                $user_item_id_array = $item_id_array_with_users[$item->getItemID()];
//                foreach ($user_item_id_array as $user_id) {
//                    $temp_user_item = $user_list->getFirst();
//                    while ($temp_user_item) {
//                        if ($temp_user_item->getItemID() == $user_id) {
//                            $temp_array['name'] = $temp_user_item->getFullName();
//                            $temp_array['email'] = $temp_user_item->getEmail();
//                            $temp_array['role'] = '0';
//                            $attendee_array[] = $temp_array;
//                        }
//                        $temp_user_item = $user_list->getNext();
//                    }
//                }
//
//                $alarm = array();
//                //         $alarm = (array) array(
//                //                          0, // Action: 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
//                //                          30,  // Trigger: alarm before the event in minutes
//                //                          'Wake Up!', // Title
//                //                          '...and go shopping', // Description
//                //                          $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
//                //                          5, // Duration between the alarms in minutes
//                //                          3  // How often should the alarm be repeated
//                //                          );
//                $enddate = '';
//                $recurrency_end = strtotime($item->getDate());
//                $language = $environment->getSelectedLanguage();
//                $translator = $environment->getTranslationObject();
//                $title = $item->getTitle();
//                $status = 1;
//                $item_status = $item->getStatus();
//                if ($item_status == $translator->getMessage('TODO_IN_POGRESS')) {
//                    $status = 2;
//                    $percent = 0;
//                } elseif ($item_status == $translator->getMessage('TODO_DONE')) {
//                    $status = 1;
//                    $percent = 100;
//                    $enddate = strtotime($item->getModificationDate());
//                } else {
//                    $status = 0;
//                    $percent = 0;
//                }
//
//                $due = '';
//                if ($item->getDate() != '9999-00-00 00:00:00') {
//                    $due = strtotime($item->getDate());
//                }
//
//                if ($enddate != '-1') {
//                    $iCal->addToDo($title, //Title for the event
//                        html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'), //Description
//                        '', // location
//                        strtotime($item->getCreationDate()), //Start time for the event (timestamp)
//
//                        '', //Duration of the todo in minutes
//                        $enddate, // End time for the event (timestamp)
//                        $percent, //The percent completion of the ToDo
//
//                        5, //priority = 09
//                        $status, //Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
//                        1, //(0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
//
//                        array($item->getCreatorItem()->getFullname(), $item->getCreatorItem()->getEmail()),//The organizer  use array('Name', 'name@domain.com')
//                        $attendee_array, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
//                        $categories, //Array with Strings (example: array('Freetime','Party'))                                                1, //$weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)
//
//                        strtotime($item->getModificationDate()), // Last modification of the to-to (timestamp)
//                        '', //Array with all the alarm information, '' for no alarm
//                        0, //frequency: 0 = once, secoundly  yearly = 17
//                        $recurrency_end, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
//                        1, // Interval for frequency (every 2,3,4 weeks)
//                        array(), //Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
//                        1, // Startday of the Week ( 0 = Sunday  6 = Saturday)
//                        '', //exeption dates: Array with timestamps of dates that should not be includes in the recurring event
//                        $path . $c_single_entry_point . '?cid=' . $_GET['cid'] . '&mod=todo&fct=detail&iid=' . $item->getItemID(), // optional URL for that event
//                        $language, // Language of the Strings
//                        $item->getItemID(), // Optional UID for this event
//                        $due // strtotime($item->getDate())
//                    );
//                }
//
//            } else {
//            }

            $item = $dateList->getNext();
        }

        return $calendar;
    }

    private function getDateList($currentContextItem, $export)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();
        $datesManager = $legacyEnvironment->getDatesManager();

        $datesManager->setWithoutDateModeLimit();

        if (!$legacyEnvironment->inPrivateRoom()) {
            $datesManager->setContextLimit($currentContextItem->getItemID());
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

    private function getTodoList($currentContextItem) {
//        $todo_manager = $environment->getToDoManager();
//        $context_item = $environment->getCurrentContextItem();
//        if (isset($context_item) and $context_item->isPrivateRoom()) {
//            $todo_sel_status = $context_item->getRubrikSelection(CS_TODO_TYPE, 'status');
//            if (isset($todo_sel_status)) {
//                if ($todo_sel_status > 9) {
//                    $todo_sel_status = $todo_sel_status - 10;
//                }
//                if (!empty($todo_sel_status)) {
//                    $todo_manager->setStatusLimit($todo_sel_status);
//                }
//            } else {
//                $todo_manager->setStatusLimit(4);
//            }
//        } else {
//            $todo_manager->setStatusLimit(4);
//        }
//        $todo_sel_room = '';
//        if (isset($context_item) and $context_item->isPrivateRoom()) {
//            $todo_sel_room = $context_item->getRubrikSelection(CS_TODO_TYPE, 'room');
//        }
//        if (!empty($todo_sel_room)) {
//            if ($todo_sel_room > 99) {
//                $room_id_array = array();
//                $room_id_array[] = $todo_sel_room;
//                $todo_manager->setContextArrayLimit($room_id_array);
//            } elseif ($todo_sel_room == 2) {
//                $current_user_item = $environment->getCurrentUser();
//                $room_id_array = array();
//                $room_id_array[] = $context_item->getItemID();
//                $grouproom_list = $current_user_item->getRelatedGroupList();
//                if (isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
//                    $grouproom_list->reverse();
//                    $grouproom_item = $grouproom_list->getFirst();
//                    while ($grouproom_item) {
//                        $project_room_id = $grouproom_item->getLinkedProjectItemID();
//                        if (in_array($project_room_id, $room_id_array)) {
//                            $room_id_array_temp = array();
//                            foreach ($room_id_array as $value) {
//                                $room_id_array_temp[] = $value;
//                                if ($value == $project_room_id) {
//                                    $room_id_array_temp[] = $grouproom_item->getItemID();
//                                }
//                            }
//                            $room_id_array = $room_id_array_temp;
//                        }
//                        $grouproom_item = $grouproom_list->getNext();
//                    }
//                }
//                $project_list = $current_user_item->getRelatedProjectList();
//                if (isset($project_list) and $project_list->isNotEmpty()) {
//                    $project_item = $project_list->getFirst();
//                    while ($project_item) {
//                        $room_id_array[] = $project_item->getItemID();
//                        $project_item = $project_list->getNext();
//                    }
//                }
//                $community_list = $current_user_item->getRelatedcommunityList();
//                if (isset($community_list) and $community_list->isNotEmpty()) {
//                    $community_item = $community_list->getFirst();
//                    while ($community_item) {
//                        $room_id_array[] = $community_item->getItemID();
//                        $community_item = $community_list->getNext();
//                    }
//                }
//                $todo_manager->setContextArrayLimit($room_id_array);
//            } else {
//                $room_id_array = array();
//                $room_id_array[] = $context_item->getItemID();
//                $todo_manager->setContextArrayLimit($room_id_array);
//            }
//        } else {
//            $room_id_array = array();
//            $room_id_array[] = $context_item->getItemID();
//            $todo_manager->setContextArrayLimit($room_id_array);
//        }
//        $todo_sel_assignment = '';
//        if (isset($context_item) and $context_item->isPrivateRoom()) {
//            $todo_sel_assignment = $context_item->getRubrikSelection(CS_TODO_TYPE, 'assignment');
//        }
//        if (!empty($todo_sel_assignment)) {
//            if ($todo_sel_assignment == '3') {
//                $current_user = $environment->getCurrentUserItem();
//                $user_list = $current_user->getRelatedUserList();
//                $user_item = $user_list->getFirst();
//                $user_id_array = array();
//                while ($user_item) {
//                    $user_id_array[] = $user_item->getItemID();
//                    $user_item = $user_list->getNext();
//                }
//                if (!empty($user_id_array)) {
//                    $todo_manager->setAssignmentLimit($user_id_array);
//                }
//                unset($user_id_array);
//                unset($user_list);
//            }
//        }
//        $todo_manager->select();
//        $item_list = $todo_manager->get();
    }
}