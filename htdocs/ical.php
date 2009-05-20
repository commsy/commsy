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


if ( isset($_GET['cid']) ) {
   $path = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
   $path = str_replace('ical.php','',$path);
   chdir('..');
   include_once('etc/cs_constants.php');
   include_once('etc/cs_config.php');
   include_once('classes/cs_environment.php');
   $environment = new cs_environment();
   $environment->setCurrentContextID($_GET['cid']);
   $context_item = $environment->getCurrentContextItem();
   $hash_manager = $environment->getHashManager();

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
      include_once('classes/external_classes/ical/iCal.php');
      $iCal = new iCal('', 0); // (ProgrammID, Method [1 = Publish | 0 = Request])
                 if ( isset($_GET['mod'])){
                    $current_module = $_GET['mod'];
                 }else{
                    $current_module = CS_DATE_TYPE;
                 }
                 if ($current_module==CS_DATE_TYPE){
                    $dates_manager = $environment->getDatesManager();
         $dates_manager->setContextLimit($context_item->getItemID());
         //$dates_manager->setFutureLimit();
         $dates_manager->setWithoutDateModeLimit();
         $dates_manager->select();
         $item_list = $dates_manager->get();
           }else{
              $todo_manager = $environment->getToDoManager();
            $todo_manager->setContextLimit($context_item->getItemID());
            $todo_manager->select();
            $item_list = $todo_manager->get();
           }
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
         $categories = array('CommSy .'.getMessage('COMMON_TODOS'));
        $attendees = $item->getProcessorItemList();
        $attendee = $attendees->getFirst();
        $temp_array = array();
        $attendee_array = array();
        while($attendee)
        {
           $temp_array['name'] = $attendee->getFullName();
           $temp_array['email'] = $attendee->getEmail();
           $temp_array['role'] = '0';
           $attendee_array[] = $temp_array;
           $attendee = $attendees->getNext();
        }

        $alarm = array();
//			$alarm = (array) array(
//								  0, // Action: 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
//								  30,  // Trigger: alarm before the event in minutes
//								  'Wake Up!', // Title
//								  '...and go shopping', // Description
//								  $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
//								  5, // Duration between the alarms in minutes
//								  3  // How often should the alarm be repeated
//								  );
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
                   strtotime($item->getDate())
         );
                    }

                 }else{
         $categories = array('CommSy .'.getMessage('COMMON_DATES'));
           $attendees = $item->getParticipantsItemList();
           $attendee = $attendees->getFirst();
           $temp_array = array();
           $attendee_array = array();
           while($attendee)
           {
             $temp_array['name'] = $attendee->getFullName();
             $temp_array['email'] = $attendee->getEmail();
             $temp_array['role'] = '0';
             $attendee_array[] = $temp_array;
             $attendee = $attendees->getNext();
           }
         $alarm = array();
//			$alarm = (array) array(
//								  0, // Action: 0 = DISPLAY, 1 = EMAIL, (not supported: 2 = AUDIO, 3 = PROCEDURE)
//								  30,  // Trigger: alarm before the event in minutes
//								  'Wake Up!', // Title
//								  '...and go shopping', // Description
//								  $attendees, // Array (key = attendee name, value = e-mail, second value = role of the attendee [0 = CHAIR | 1 = REQ | 2 = OPT | 3 =NON])
//								  5, // Duration between the alarms in minutes
//								  3  // How often should the alarm be repeated
//								  );

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
         if($item->getDateTime_start() == $item->getDateTime_end())
         {
            $starttime = $starttime + (24*3600);
            $endtime = 'allday';
         } elseif(strstr($item->getDateTime_start(),"00:00:00") and strstr($item->getDateTime_end(),"00:00:00"))
         {
            $starttime = $starttime + (24*3600);
            $endtime = $endtime + (24*3600);
         }
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
    $dateiname = 'dates_'.$_GET['cid'];
   #echo $iCal->getOutput();
   $iCal->outputFile($dateiname);
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