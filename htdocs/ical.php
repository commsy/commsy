<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

mb_internal_encoding('UTF-8');
if (isset($_GET['cid'])) {
    global $c_webserver;
    if (isset($c_webserver) and $c_webserver == 'lighttpd') {
        $path = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    } else {
        $path = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    }

    $path = str_replace('ical.php', '', $path);
    chdir('..');

    // require composer autoloader
    require 'vendor/autoload.php';

    include_once('etc/cs_constants.php');
    include_once('etc/cs_config.php');

    // start of execution time
    include_once('functions/misc_functions.php');
    $time_start = getmicrotime();

    include_once('classes/cs_environment.php');
    $environment = new cs_environment();
    $environment->setCurrentContextID($_GET['cid']);
    $context_item = $environment->getCurrentContextItem();
    $hash_manager = $environment->getHashManager();
    $translator = $environment->getTranslationObject();

    $validated = false;
    if ($context_item->isOpenForGuests()) {
        $validated = true;
    }

    if (!$context_item->isPortal()
        and !$context_item->isServer()
        and isset($_GET['hid'])
        and !empty($_GET['hid'])
        and !$validated
    ) {
        if (!$context_item->isLocked()
            and $hash_manager->isICalHashValid($_GET['hid'], $context_item)
        ) {
            $validated = true;
        }
    }
    if ($validated) {
        if (!empty($_GET['hid'])) {
            $l_current_user_item = $hash_manager->getUserByICalHash($_GET['hid']);
            if (!empty($l_current_user_item)) {
                $environment->setCurrentUserItem($l_current_user_item);
            }
        }

        $vCalender = new \Eluceo\iCal\Component\Calendar('www.commsy.net');

        $current_module = CS_DATE_TYPE;

        $dates_manager = $environment->getDatesManager();
        $dates_manager->setWithoutDateModeLimit();
        if (!$environment->inPrivateRoom()) {
            $dates_manager->setContextLimit($context_item->getItemID());
        } else {
            $context_item = $environment->getCurrentContextItem();
            $date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE, 'status');
            if (!empty($date_sel_status)) {
                $dates_manager->setDateModeLimit($date_sel_status);
            }
            $date_sel_assignment = $context_item->getRubrikSelection(CS_DATE_TYPE, 'assignment');
            if (!empty($date_sel_assignment)
                and $date_sel_assignment != '2'
            ) {
                $current_user_item = $environment->getCurrentUserItem();
                $user_list = $current_user_item->getRelatedUserList();
                $user_item = $user_list->getFirst();
                $user_id_array = array();
                while ($user_item) {
                    $user_id_array[] = $user_item->getItemID();
                    $user_item = $user_list->getNext();
                }
                $dates_manager->setAssignmentLimit($user_id_array);
                unset($user_id_array);
                unset($user_list);
            }

            $myentries_array = $context_item->getMyCalendarDisplayConfig();
            $myroom_array = array();
            foreach ($myentries_array as $entry) {
                $exp_entry = explode('_', $entry);
                if (sizeof($exp_entry) == 2) {
                    if ($exp_entry[1] == 'dates' || $exp_entry[1] == 'todo') {
                        $entry = str_replace('_dates', '', $entry);
                        $entry = str_replace('_todo', '', $entry);
                        $myroom_array[] = $entry;
                    }
                }
            }

            // add private room to array
            $myroom_array[] = $context_item->getItemID();

            $dates_manager->setContextArrayLimit($myroom_array);
        }
        if (!(isset($_GET['mode']) and ($_GET['mode'] == 'export'))) {
            $dates_manager->setNotOlderThanMonthLimit(3);
        }
        $dates_manager->select();
        $item_list = $dates_manager->get();

        if ($environment->inPrivateRoom()) {
            $myentries_array = $context_item->getMyCalendarDisplayConfig();
            if (in_array("mycalendar_dates_assigned_to_me", $myentries_array)) {
                $temp_list = new cs_list();
                $current_user_item = $environment->getCurrentUserItem();
                $current_user_list = $current_user_item->getRelatedUserList();
                $temp_element = $item_list->getFirst();
                while ($temp_element) {
                    $temp_user = $current_user_list->getFirst();
                    while ($temp_user) {
                        if ($temp_element->isParticipant($temp_user)) {
                            $temp_list->add($temp_element);
                        }
                        $temp_user = $current_user_list->getNext();
                    }
                    $temp_element = $item_list->getNext();
                }
                $item_list = $temp_list;
            }
        }

        $item_id_array = array();
        $item = $item_list->getFirst();
        while ($item) {
            $item_id_array[] = $item->getItemID();
            $item = $item_list->getNext();
        }

        // Alle Verlinkungen Terminen <-> User
        $link_item_manager = $environment->getLinkItemManager();
        $link_item_manager->setTypeLimit(CS_USER_TYPE);
        $link_item_manager->setIDArrayLimit($item_id_array);
        $link_item_manager->setRoomLimit($environment->getCurrentContextID());
        $link_item_manager->select2(false);
        $link_item_list = $link_item_manager->get();

        // Arrays der einzelnen Termine aufbauen
        $item_id_array_with_users = array();
        foreach ($item_id_array as $item_id) {
            $temp_array = array();
            $link_item = $link_item_list->getFirst();
            while ($link_item) {
                if ($link_item->getFirstLinkedItemID() == $item_id) {
                    $temp_array[] = $link_item->getSecondLinkedItemID();
                }
                $link_item = $link_item_list->getNext();
            }
            $item_id_array_with_users[$item_id] = $temp_array;
        }

        // Array der Benutzer-IDs aufbauen
        $user_id_array = array();
        $link_item = $link_item_list->getFirst();
        while ($link_item) {
            if (!in_array($link_item->getSecondLinkedItemID(), $user_id_array)) {
                $user_id_array[] = $link_item->getSecondLinkedItemID();
            }
            $link_item = $link_item_list->getNext();
        }

        // Benutzer-Anfrage an den User-Manager
        $user_manager = $environment->getUserManager();
        $user_manager->setContextLimit($environment->getCurrentContextID());
        $user_manager->setIDArrayLimit($user_id_array);
        $user_manager->select();
        $user_list = $user_manager->get();

        $item = $item_list->getFirst();
        while ($item) {
            $vEvent = new \Eluceo\iCal\Component\Event();

            // organizer
            $creator = $item->getCreatorItem();
            $creatorFullname = $creator->getFullName();
            $creatorEmail = $creator->getEmail();
            if (!empty($creatorFullname) && !empty($creatorEmail)) {
                $vEvent->setOrganizer(new \Eluceo\iCal\Property\Event\Organizer(
                    "MAILTO:$creatorEmail", [
                        'CN' => $creatorFullname,
                    ]
                ));
            }

            $categories = array('CommSy .' . $translator->getMessage('COMMON_DATES'));
            $temp_array = array();
            $attendee_array = array();
            $user_item_id_array = $item_id_array_with_users[$item->getItemID()];
            foreach ($user_item_id_array as $user_id) {
                $temp_user_item = $user_list->getFirst();
                while ($temp_user_item) {
                    if ($temp_user_item->getItemID() == $user_id) {

                        $email = $temp_user_item->getEmail();
                        $fullName = $temp_user_item->getFullName();

                        if (!empty($email) && !empty($fullName)) {
                            $vEvent->addAttendee("MAILTO:$email", [
                                'CN' => $fullName,
                            ]);
                        }
                    }
                    $temp_user_item = $user_list->getNext();
                }
            }

            $startTime = new \DateTime($item->getDateTime_start());
            $endTime = new \DateTime($item->getDateTime_end());

            $startTime->setTimezone(new \DateTimeZone('UTC'));
            $endTime->setTimezone(new \DateTimeZone('UTC'));

            $language = $environment->getSelectedLanguage();
            $translator = $environment->getTranslationObject();
            $title = '';
            if (!$item->issetPrivatDate()) {
                $title = $item->getTitle();
            } else {
                $title = $item->getTitle() . ' [' . $translator->getMessage('DATE_PRIVATE_ENTRY') . ']';
            }
            if (!empty($item->getPlace)) {
                $place = $item->getPlace();
            } else {
                $place = '';
            }

            if ($startTime && $endTime) {
                // Dates with equal start and end date or no start and end time are all day events
                if ($startTime == $endTime || (empty($item->getStartingTime()) && empty($item->getEndingTime()))) {
                    $vEvent->setNoTime(true);
                }

                // Ending dates ending not at the starting day, with starting time and without an exact ending time are considered to
                // span the whole day
                if ($startTime != $endTime && !empty($item->getStartingTime()) && empty($item->getEndingTime())) {
                    $endTime->add(new \DateInterval('P1D'));
                }

                $vEvent
                    ->setDtStart($startTime)
                    ->setDtEnd($endTime)
                    ->setLocation($item->getPlace())
                    ->setCategories($categories)
                    ->setDescription(html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'))
                    ->setSummary($title)
                    ->setUrl($path . $c_single_entry_point . '?cid=' . $_GET['cid'] . '&mod=date&fct=detail&iid=' . $item->getItemID())
                    ->setUniqueId($item->getItemID())
                    ->setStatus(\Eluceo\iCal\Component\Event::STATUS_CONFIRMED);

                $vCalender->addComponent($vEvent);
            }

            $item = $item_list->getNext();
        }

        $fileName = $translator->getMessage('DATES_EXPORT_FILENAME') . '_' . $_GET['cid'];

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '.ics"');

        echo $vCalender->render();

        # logging
        include_once('include/inc_log.php');

    } else {
        include_once('etc/cs_constants.php');
        include_once('etc/cs_config.php');
        include_once('classes/cs_environment.php');
        $environment = new cs_environment();
        $environment->setCurrentContextID($_GET['cid']);
        $translator = $environment->getTranslationObject();
        die($translator->getMessage('RSS_NOT_ALLOWED'));
    }
} else {
    chdir('..');
    include_once('etc/cs_constants.php');
    include_once('etc/cs_config.php');
    include_once('classes/cs_environment.php');
    $environment = new cs_environment();
    $environment->setCurrentContextID($_GET['cid']);
    $translator = $environment->getTranslationObject();
    die($translator->getMessage('RSS_NO_CONTEXT'));
}