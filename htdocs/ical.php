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
if ( isset($_GET['cid']) ) {
   global $c_webserver;
   if(isset($c_webserver) and $c_webserver == 'lighttpd'){
      $path = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
   } else {
      $path = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
   }

   $path = str_replace('ical.php','',$path);
   chdir('..');
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
   if ( $context_item->isOpenForGuests() ) {
      $validated = true;
   }

   if ( !$context_item->isPortal()
        and !$context_item->isServer()
        and isset($_GET['hid'])
        and !empty($_GET['hid'])
        and !$validated
      ) {
      if ( !$context_item->isLocked()
           and $hash_manager->isICalHashValid($_GET['hid'],$context_item)
         ) {
         $validated = true;
      }
   }
   if($validated) {
      if ( !empty($_GET['hid']) ) {
         $l_current_user_item = $hash_manager->getUserByICalHash($_GET['hid']);
         if ( !empty($l_current_user_item) ) {
            $environment->setCurrentUserItem($l_current_user_item);
         }
      }
      include_once('classes/external_classes/ical/iCal.php');
      $iCal = new iCal('', 1); // (ProgrammID, Method [1 = Publish | 0 = Request])
      if ( isset($_GET['mod'])){
         $current_module = $_GET['mod'];
      }else{
         $current_module = CS_DATE_TYPE;
      }
      if ($current_module==CS_DATE_TYPE){
         $dates_manager = $environment->getDatesManager();
         $dates_manager->setWithoutDateModeLimit();
         if ( !$environment->inPrivateRoom() ) {
            $dates_manager->setContextLimit($context_item->getItemID());
         } else {
            $context_item = $environment->getCurrentContextItem();
            $date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE,'status');
            if ( !empty($date_sel_status) ) {
               $dates_manager->setDateModeLimit($date_sel_status);
            }
            $date_sel_assignment = $context_item->getRubrikSelection(CS_DATE_TYPE,'assignment');
            if ( !empty($date_sel_assignment)
                 and $date_sel_assignment != '2'
               ) {
               $current_user_item = $environment->getCurrentUserItem();
               $user_list = $current_user_item->getRelatedUserList();
               $user_item = $user_list->getFirst();
               $user_id_array = array();
               while ($user_item){
                  $user_id_array[] = $user_item->getItemID();
                  $user_item = $user_list->getNext();
               }
               $dates_manager->setAssignmentLimit($user_id_array);
               unset($user_id_array);
               unset($user_list);
            }
            /*$date_sel_room = $context_item->getRubrikSelection(CS_DATE_TYPE,'room');
            if ( !empty($date_sel_room) ) {
               if ( $date_sel_room != "2" ) {
                  $room_id = array();
                  $room_id[] = $date_sel_room;
                  $dates_manager->setContextArrayLimit($room_id);
                  unset($room_id);
               } else {
                  $room_id_array = array();
                  $room_id_array[] = $context_item->getItemID();
                  $current_user_item = $environment->getCurrentUserItem();
                  $grouproom_list = $current_user_item->getRelatedGroupList();
                  if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
                     $grouproom_list->reverse();
                     $grouproom_item = $grouproom_list->getFirst();
                     while ($grouproom_item) {
                        $project_room_id = $grouproom_item->getLinkedProjectItemID();
                        if ( in_array($project_room_id,$room_id_array) ) {
                           $room_id_array_temp = array();
                           foreach ($room_id_array as $value) {
                              $room_id_array_temp[] = $value;
                              if ( $value == $project_room_id) {
                                  $room_id_array_temp[] = $grouproom_item->getItemID();
                              }
                           }
                           $room_id_array = $room_id_array_temp;
                        }
                        $grouproom_item = $grouproom_list->getNext();
                     }
                  }
                  $project_list = $current_user_item->getRelatedProjectList();
                  if ( isset($project_list) and $project_list->isNotEmpty()) {
                     $project_item = $project_list->getFirst();
                     while ($project_item) {
                         $room_id_array[] = $project_item->getItemID();
                         $project_item = $project_list->getNext();
                     }
                  }
                  $community_list = $current_user_item->getRelatedcommunityList();
                  if ( isset($community_list) and $community_list->isNotEmpty()) {
                     $community_item = $community_list->getFirst();
                     while ($community_item) {
                         $room_id_array[] = $community_item->getItemID();
                         $community_item = $community_list->getNext();
                     }
                  }
                  $dates_manager->setContextArrayLimit($room_id_array);
               }
            }*/
            $myentries_array = $context_item->getMyCalendarDisplayConfig();
            $myroom_array = array();
            foreach($myentries_array as $entry) {
               $exp_entry = explode('_', $entry);
               if(sizeof($exp_entry) == 2) {
                  if($exp_entry[1] == 'dates' || $exp_entry[1] == 'todo') {
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
         if (!(isset($_GET['mode']) and ($_GET['mode'] == 'export'))){
         	$dates_manager->setNotOlderThanMonthLimit(3);
         }
         $dates_manager->select();
         $item_list = $dates_manager->get();

         if ( $environment->inPrivateRoom() ) {
            $myentries_array = $context_item->getMyCalendarDisplayConfig();
            if(in_array("mycalendar_dates_assigned_to_me", $myentries_array)){
               $temp_list = new cs_list();
               $current_user_item = $environment->getCurrentUserItem();
               $current_user_list = $current_user_item->getRelatedUserList();
               $temp_element = $item_list->getFirst();
               while($temp_element){
                  $temp_user = $current_user_list->getFirst();
                  while($temp_user){
                     if($temp_element->isParticipant($temp_user)){
                        $temp_list->add($temp_element);
                     }
                     $temp_user = $current_user_list->getNext();
                  }
                  $temp_element = $item_list->getNext();
               }
               $item_list = $temp_list;
            }
         }

      }else{
         $todo_manager = $environment->getToDoManager();
         $context_item = $environment->getCurrentContextItem();
         if ( isset($context_item) and $context_item->isPrivateRoom() ) {
            $todo_sel_status = $context_item->getRubrikSelection(CS_TODO_TYPE,'status');
            if ( isset($todo_sel_status) ) {
               if ( $todo_sel_status > 9 ) {
                  $todo_sel_status = $todo_sel_status - 10;
               }
               if ( !empty($todo_sel_status) ) {
                  $todo_manager->setStatusLimit($todo_sel_status);
               }
            } else {
               $todo_manager->setStatusLimit(4);
            }
         } else {
            $todo_manager->setStatusLimit(4);
         }
         $todo_sel_room = '';
         if ( isset($context_item) and $context_item->isPrivateRoom() ) {
            $todo_sel_room = $context_item->getRubrikSelection(CS_TODO_TYPE,'room');
         }
         if ( !empty($todo_sel_room) ) {
            if ( $todo_sel_room > 99 ) {
               $room_id_array = array();
               $room_id_array[] = $todo_sel_room;
               $todo_manager->setContextArrayLimit($room_id_array);
            } elseif ( $todo_sel_room == 2 ) {
               $current_user_item = $environment->getCurrentUser();
               $room_id_array = array();
               $room_id_array[] = $context_item->getItemID();
               $grouproom_list = $current_user_item->getRelatedGroupList();
               if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
                  $grouproom_list->reverse();
                  $grouproom_item = $grouproom_list->getFirst();
                  while ($grouproom_item) {
                     $project_room_id = $grouproom_item->getLinkedProjectItemID();
                     if ( in_array($project_room_id,$room_id_array) ) {
                        $room_id_array_temp = array();
                        foreach ($room_id_array as $value) {
                           $room_id_array_temp[] = $value;
                           if ( $value == $project_room_id) {
                               $room_id_array_temp[] = $grouproom_item->getItemID();
                           }
                        }
                        $room_id_array = $room_id_array_temp;
                     }
                     $grouproom_item = $grouproom_list->getNext();
                  }
               }
               $project_list = $current_user_item->getRelatedProjectList();
               if ( isset($project_list) and $project_list->isNotEmpty()) {
                  $project_item = $project_list->getFirst();
                  while ($project_item) {
                      $room_id_array[] = $project_item->getItemID();
                      $project_item = $project_list->getNext();
                  }
               }
               $community_list = $current_user_item->getRelatedcommunityList();
               if ( isset($community_list) and $community_list->isNotEmpty()) {
                  $community_item = $community_list->getFirst();
                  while ($community_item) {
                      $room_id_array[] = $community_item->getItemID();
                      $community_item = $community_list->getNext();
                  }
               }
               $todo_manager->setContextArrayLimit($room_id_array);
            } else {
               $room_id_array = array();
               $room_id_array[] = $context_item->getItemID();
               $todo_manager->setContextArrayLimit($room_id_array);
            }
         } else {
            $room_id_array = array();
            $room_id_array[] = $context_item->getItemID();
            $todo_manager->setContextArrayLimit($room_id_array);
         }
         $todo_sel_assignment = '';
         if ( isset($context_item) and $context_item->isPrivateRoom() ) {
            $todo_sel_assignment = $context_item->getRubrikSelection(CS_TODO_TYPE,'assignment');
         }
         if ( !empty($todo_sel_assignment) ) {
            if ($todo_sel_assignment == '3'){
               $current_user = $environment->getCurrentUserItem();
               $user_list = $current_user->getRelatedUserList();
               $user_item = $user_list->getFirst();
               $user_id_array = array();
               while ($user_item){
                  $user_id_array[] = $user_item->getItemID();
                  $user_item = $user_list->getNext();
               }
               if ( !empty($user_id_array) ) {
                  $todo_manager->setAssignmentLimit($user_id_array);
               }
               unset($user_id_array);
               unset($user_list);
            }
         }
         $todo_manager->select();
         $item_list = $todo_manager->get();
      }

      $item_id_array = array();
      $item = $item_list->getFirst();
      while($item){
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
      foreach($item_id_array as $item_id){
         $temp_array = array();
         $link_item = $link_item_list->getFirst();
         while($link_item){
            if($link_item->getFirstLinkedItemID() == $item_id){
               $temp_array[] = $link_item->getSecondLinkedItemID();
            }
            $link_item = $link_item_list->getNext();
         }
         $item_id_array_with_users[$item_id] = $temp_array;
      }

      // Array der Benutzer-IDs aufbauen
      $user_id_array = array();
      $link_item = $link_item_list->getFirst();
      while($link_item){
         if(!in_array($link_item->getSecondLinkedItemID(), $user_id_array)){
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

      #$user_item = $user_list->getFirst();
      #while($user_item){
      #   $user_item = $user_list->getNext();
      #}

      $item = $item_list->getFirst();
      while($item)
      {
         $fullname = $item->getCreatorItem()->getFullName();
         $email = $item->getCreatorItem()->getEmail();
         if(empty($fullname) or empty($email))
         {
            $organizer = array();
         } else {
            $organizer = (array) array($item->getCreatorItem()->getFullName(), $item->getCreatorItem()->getEmail());
         }
         if ($current_module==CS_TODO_TYPE){
            $categories = array('CommSy .'.$translator->getMessage('COMMON_TODOS'));
###            $attendees = $item->getProcessorItemList();
###            $attendee = $attendees->getFirst();
            $temp_array = array();
            $attendee_array = array();
            $temp_array = array();
            $attendee_array = array();
            $user_item_id_array = $item_id_array_with_users[$item->getItemID()];
            foreach($user_item_id_array as $user_id){
               $temp_user_item = $user_list->getFirst();
               while($temp_user_item){
                  if($temp_user_item->getItemID() == $user_id){
                     $temp_array['name'] = $temp_user_item->getFullName();
                     $temp_array['email'] = $temp_user_item->getEmail();
                     $temp_array['role'] = '0';
                     $attendee_array[] = $temp_array;
                  }
                  $temp_user_item = $user_list->getNext();
               }
            }
###            while($attendee)
###            {
###               $temp_array['name'] = $attendee->getFullName();
###               $temp_array['email'] = $attendee->getEmail();
###               $temp_array['role'] = '0';
###               $attendee_array[] = $temp_array;
###               $attendee = $attendees->getNext();
###            }

            $alarm = array();
            //         $alarm = (array) array(
            //                          0, // Action: 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
            //                          30,  // Trigger: alarm before the event in minutes
            //                          'Wake Up!', // Title
            //                          '...and go shopping', // Description
            //                          $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
            //                          5, // Duration between the alarms in minutes
            //                          3  // How often should the alarm be repeated
            //                          );
            $enddate = '';
            $recurrency_end = strtotime($item->getDate());
            $language = $environment->getSelectedLanguage();
            $translator = $environment->getTranslationObject();
            $title = $item->getTitle();
            $status = 1;
            $item_status = $item->getStatus();
            if($item_status == $translator->getMessage('TODO_IN_POGRESS')){
               $status = 2;
               $percent = 0;
            }elseif($item_status == $translator->getMessage('TODO_DONE')){
               $status = 1;
               $percent = 100;
               $enddate = strtotime($item->getModificationDate());
            }else{
               $status = 0;
               $percent = 0;
            }

            $due = '';
            if($item->getDate() != '9999-00-00 00:00:00'){
               $due = strtotime($item->getDate());
            }

            if($enddate != '-1')
            {
               $iCal->addToDo($title, //Title for the event
               html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'), //Description
                                        '', // location
               strtotime($item->getCreationDate()), //Start time for the event (timestamp)

                                        '', //Duration of the todo in minutes
               $enddate, // End time for the event (timestamp)
               $percent, //The percent completion of the ToDo

               5, //priority = 09
               $status, //Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
               1, //(0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)

               array($item->getCreatorItem()->getFullname(),$item->getCreatorItem()->getEmail()),//The organizer  use array('Name', 'name@domain.com')
               $attendee_array, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
               $categories, //Array with Strings (example: array('Freetime','Party'))                                                1, //$weekstart  Startday of the Week ( 0 = Sunday  6 = Saturday)

               strtotime($item->getModificationDate()), // Last modification of the to-to (timestamp)
                                        '', //Array with all the alarm information, '' for no alarm
               0, //frequency: 0 = once, secoundly  yearly = 17
               $recurrency_end, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
               1, // Interval for frequency (every 2,3,4 weeks)
               array(), //Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
               1, // Startday of the Week ( 0 = Sunday  6 = Saturday)
                                        '', //exeption dates: Array with timestamps of dates that should not be includes in the recurring event
               $path.$c_single_entry_point.'?cid='.$_GET['cid'].'&mod=todo&fct=detail&iid='.$item->getItemID(), // optional URL for that event
               $language, // Language of the Strings
               $item->getItemID(), // Optional UID for this event
               $due // strtotime($item->getDate())
               );
            }

         }else{
            $categories = array('CommSy .'.$translator->getMessage('COMMON_DATES'));
###            $attendees = $item->getParticipantsItemList();
###            $attendee = $attendees->getFirst();
            $temp_array = array();
            $attendee_array = array();
            $user_item_id_array = $item_id_array_with_users[$item->getItemID()];
            foreach($user_item_id_array as $user_id){
               $temp_user_item = $user_list->getFirst();
               while($temp_user_item){
                  if($temp_user_item->getItemID() == $user_id){
                     $temp_array['name'] = $temp_user_item->getFullName();
                     $temp_array['email'] = $temp_user_item->getEmail();
                     $temp_array['role'] = '0';
                     $attendee_array[] = $temp_array;
                  }
                  $temp_user_item = $user_list->getNext();
               }
            }
###            while($attendee)
###            {
###               $temp_array['name'] = $attendee->getFullName();
###               $temp_array['email'] = $attendee->getEmail();
###               $temp_array['role'] = '0';
###               $attendee_array[] = $temp_array;
###               $attendee = $attendees->getNext();
###            }
            $alarm = array();
            //         $alarm = (array) array(
            //                          0, // Action: 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
            //                          30,  // Trigger: alarm before the event in minutes
            //                          'Wake Up!', // Title
            //                          '...and go shopping', // Description
            //                          $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
            //                          5, // Duration between the alarms in minutes
            //                          3  // How often should the alarm be repeated
            //                          );

            $starttime = strtotime($item->getDateTime_start());
            $endtime = strtotime($item->getDateTime_end());

            if($starttime >= $endtime)
            {
               $endtime = $starttime+3600;
            }
            $language = $environment->getSelectedLanguage();
            $translator = $environment->getTranslationObject();
            $title = '';
            if(!$item->issetPrivatDate())
            {
               $title = $item->getTitle();
            } else {
               $title = $item->getTitle().' ['.$translator->getMessage('DATE_PRIVATE_ENTRY').']';
            }
            if(!empty($item->getPlace)) {
               $place = $item->getPlace();
            } else {
               $place = '';
            }
            if ( $item->getDateTime_start() == $item->getDateTime_end() ) {
               if ( strstr($item->getDateTime_start(),"00:00:00") ) {
                  $starttime = $starttime + (24*3600);
               }
               $endtime = 'allday';
            } elseif ( strstr($item->getDateTime_start(),"00:00:00")
                       and strstr($item->getDateTime_end(),"00:00:00")
                     ) {
               $starttime += (24*3600);
               $endtime += (48*3600);
            }

            ### for thunderbird 3 and lightning 1.0b1 ###
            elseif ( strstr($item->getDateTime_start(),"00:00:00") ) {
               $starttime = $starttime + (24*3600);
               $endtime = $endtime + (24*3600);
            }
            ### for thunderbird 3 and lightning 1.0b1 ###

            if($starttime != '-1' and $endtime != '-1')
            {
               $iCal->addEvent(
               $organizer, // Organizer
               $starttime, // Start Time (timestamp; for an allday event the startdate has to start at YYYY-mm-dd 00:00:00)
               $endtime, // End Time (write 'allday' for an allday event instead of a timestamp)
               $item->getPlace(), // Location
               1, // Transparancy (0 = OPAQUE | 1 = TRANSPARENT)
               $categories, // Array with Strings
               html_entity_decode(strip_tags($item->getDescription()), ENT_NOQUOTES, 'UTF-8'), // Description
               $title, // Title
               1, // Class (0 = PRIVATE | 1 = PUBLIC | 2 = CONFIDENTIAL)
               $attendee_array, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
               5, // Priority = 0-9
               0, // frequency: 0 = once, secoundly - yearly = 1-7
               0, // recurrency end: ('' = forever | integer = number of times | timestring = explicit date)
               0, // Interval for frequency (every 2,3,4 weeks...)
               array(), // Array with the number of the days the event accures (example: array(0,1,5) = Sunday, Monday, Friday
               1, // Startday of the Week ( 0 = Sunday - 6 = Saturday)
                        '', // exeption dates: Array with timestamps of dates that should not be includes in the recurring event
               $alarm,  // Sets the time in minutes an alarm appears before the event in the programm. no alarm if empty string or 0
               1, // Status of the event (0 = TENTATIVE, 1 = CONFIRMED, 2 = CANCELLED)
               $path.$c_single_entry_point.'?cid='.$_GET['cid'].'&mod=date&fct=detail&iid='.$item->getItemID(), // optional URL for that event
               $language, // Language of the Strings
               $item->getItemID() // Optional UID for this event
               );
            }
         }
         $item = $item_list->getNext();
      }
      if($current_module==CS_DATE_TYPE){
         $dateiname = $translator->getMessage('DATES_EXPORT_FILENAME').'_'.$_GET['cid'];
      } elseif ($current_module==CS_TODO_TYPE){
         $dateiname = $translator->getMessage('TODO_EXPORT_FILENAME').'_'.$_GET['cid'];
      }
      #echo $iCal->getOutput();
      $iCal->outputFile($dateiname);

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
?>