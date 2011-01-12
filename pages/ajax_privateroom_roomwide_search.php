<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

include_once('functions/development_functions.php');

if(isset($_GET['interval'])){
	$interval = $_GET['interval'];
} else {
   $interval = 20;
}

$private_room_item = $environment->getCurrentContextItem();
$user_item = $environment->getCurrentUserItem();
if(!isset($_GET['roomwide_search_room'])){
	$context_array = array();
	$room_name_array = array();
	// Projekt- und Gruppenraeume
	$project_list = $user_item->getUserRelatedProjectList();
	$project_item = $project_list->getFirst();
	while($project_item){
		$context_array[] = $project_item->getItemID();
		$room_name_array[$project_item->getItemID()] = $project_item->getTitle();
		$project_item = $project_list->getNext();
	}

	// Gemeinschaftsraeume
	$community_list = $user_item->getUserRelatedCommunityList();
	$community_item = $community_list->getFirst();
	while($community_item){
	   $context_array[] = $community_item->getItemID();
	   $room_name_array[$community_item->getItemID()] = $community_item->getTitle();
	   $community_item = $community_list->getNext();
	}

	// Privater Raum
	$context_array[] = $private_room_item->getItemID();
	$room_name_array[$private_room_item->getItemID()] = $private_room_item->getTitle();
} else {
	$context_array = $_GET['roomwide_search_room'];
	$room_manager = $environment->getRoomManager();
	$private_room_id = $private_room_item->getItemId();
	foreach($context_array as $context_temp){
		if($context_temp != $private_room_id){
		   $temp_room = $room_manager->getItem($context_temp);
		   $room_name_array[$context_temp] = $temp_room->getTitle();
		} else {
			$room_name_array[$private_room_id] = $private_room_item->getTitle();
		}
	}
}

$file_rubric_array = array();
if(isset($_GET['roomwide_search_type'])){
	if(in_array(CS_DISCUSSION_TYPE, $_GET['roomwide_search_type'])){
	   $file_rubric_array[] = CS_DISCUSSION_TYPE;
	}
	if(in_array(CS_DATE_TYPE, $_GET['roomwide_search_type'])){
	  $file_rubric_array[] = CS_DATE_TYPE;
	}
	if(in_array(CS_TODO_TYPE, $_GET['roomwide_search_type'])){
	  $file_rubric_array[] = CS_TODO_TYPE;
	}
} else {
	$file_rubric_array[] = CS_DISCUSSION_TYPE;
   $file_rubric_array[] = CS_DATE_TYPE;
   $file_rubric_array[] = CS_TODO_TYPE;
}

$complete_list = new cs_list();
foreach($file_rubric_array as $file_rubric){
	$rubric_manager = $environment->getManager($file_rubric);
   $rubric_manager->setContextArrayLimit($context_array);
   if ($file_rubric == CS_DATE_TYPE) {
      $rubric_manager->setWithoutDateModeLimit();
   }
   if(!empty($_GET['search'])){
      $rubric_manager->setSearchLimit($_GET['search']);
   }
   $rubric_manager->showNoNotActivatedEntries();
	$rubric_manager->select();
   $item_list = $rubric_manager->get();
   $complete_list->addList($item_list);
}

// Ankündigungen
if((isset($_GET['roomwide_search_type']) and in_array(CS_ANNOUNCEMENT_TYPE, $_GET['roomwide_search_type'])) or !isset($_GET['roomwide_search_type'])){
	foreach($context_array as $context){
	   $rubric_manager = $environment->getManager(CS_ANNOUNCEMENT_TYPE);
	   $rubric_manager->setContextLimit($context);
	   if(!empty($_GET['search'])){
	      $rubric_manager->setSearchLimit($_GET['search']);
	   }
      $rubric_manager->showNoNotActivatedEntries();
	   $rubric_manager->select();
	   $item_list = $rubric_manager->get();
	   $complete_list->addList($item_list);
	}
}

// Themen
if((isset($_GET['roomwide_search_type']) and in_array(CS_TOPIC_TYPE, $_GET['roomwide_search_type'])) or !isset($_GET['roomwide_search_type'])){
	foreach($context_array as $context){
	   $rubric_manager = $environment->getManager(CS_TOPIC_TYPE);
	   $rubric_manager->setContextLimit($context);
	   if(!empty($_GET['search'])){
	      $rubric_manager->setSearchLimit($_GET['search']);
	   }
      $rubric_manager->showNoNotActivatedEntries();
	   $rubric_manager->select();
	   $item_list = $rubric_manager->get();
	   $complete_list->addList($item_list);
	}
}

// Materialien
if((isset($_GET['roomwide_search_type']) and in_array(CS_MATERIAL_TYPE, $_GET['roomwide_search_type'])) or !isset($_GET['roomwide_search_type'])){
	foreach($context_array as $context){
		$rubric_manager = $environment->getManager(CS_MATERIAL_TYPE);
	   $rubric_manager->setContextLimit($context);
	   if(!empty($_GET['search'])){
	      $rubric_manager->setSearchLimit($_GET['search']);
	   }
      $rubric_manager->showNoNotActivatedEntries();
	   $rubric_manager->select();
	   $item_list = $rubric_manager->get();
	   $complete_list->addList($item_list);
	}
}

$complete_list->sortby('modification_date');
$complete_list->reverse();

$count = $complete_list->getCount();

$number_of_pages = 0;
if($count % $interval == 0){
   $number_of_pages = ($count / $interval)-1;
} else {
   $number_of_pages = (($count - ($count % $interval)) / $interval);
}
if($number_of_pages == -1){
	$number_of_pages++;
}

$result_page = $_GET['page'];
if($result_page > $number_of_pages){
   $result_page = $number_of_pages;
} else if($result_page == -1){
	$result_page = 1;
}

if($count > $interval){
	$from = $interval * $result_page;
	if(($from + ($interval-1)) <= $count){
		$to = $from + ($interval-1);
		$to_display = $to+1;
	} else {
	   $to = $count;
	   $to_display = $to;
	}
} else {
	$from = 0;
	$to = $count;
	$to_display = $count;
}

if($to > 0){
   $from_display = $from+1;
} else {
	$from_display = 0;
}

$result_list = $complete_list->getSubList($from, $interval);

$result_array = array();
$item = $result_list->getFirst();

$params = array();
$params['environment'] = $environment;
$view = $class_factory->getClass(INDEX_VIEW,$params);


while($item){
   $room_name = $room_name_array[$item->getContextID()];
   $hover_text = '';
   if($item->getItemType() == CS_DATE_TYPE){
   	$hover_date_array = getTooltipDate($item);
      $hover_text .= $hover_date_array[0].' '.$hover_date_array[1];
   } else {
   	$type = 'COMMON_'.strtoupper($item->getItemType());
   	$hover_text .= $translator->getMessage($type);
   }
   $status_change = getItemChangeStatus($item, $item->getContextID());
   $annotation_change = getItemAnnotationChangeStatus($item, $item->getContextID());

   $result_array[] = array('title' => $view->_text_as_html_short(_compareWithSearchText($item->getTitle())), 'status' => $status_change.''.$annotation_change, 'type' => $item->getItemType(), 'iid' => $item->getItemId(), 'cid' => $item->getContextID(), 'hover' => $hover_text, 'room_name' => $room_name);
   $item = $result_list->getNext();
}

$page->add('roomwide_search_info', array('page' => $result_page, 'last' => $number_of_pages, 'from' => $from_display, 'to' => $to_display, 'count' => $count));
$page->add('roomwide_search_results', $result_array);

// Functions

function _compareWithSearchText($value){
   if(!empty($_GET['search'])){
      if ( mb_stristr($value,$_GET['search']) ) {
         // $replace = '(:mainsearch_text:)$0(:mainsearch_text_end:)';
         include_once('functions/misc_functions.php');
         if ( getMarkerColor() == 'green') {
            $replace = '(:mainsearch_text_green:)$0(:mainsearch_text_green_end:)';
         }
         else if (getMarkerColor() == 'yellow') {
            $replace = '(:mainsearch_text_yellow:)$0(:mainsearch_text_yellow_end:)';
         }
         // $replace = '(:searchedtext:)$0(:searchedtext_end:)';
         $value = preg_replace('~'.preg_quote($_GET['search'],'/').'~iu',$replace,$value);
         // $value = preg_replace('~'.preg_quote($search_text,'/').'~iu','*$0*',$value);
      }
   }
   return $value;
}



function getTooltipDate($date){
	global $environment;
	$text_converter = $environment->getTextConverter();
	$translator = $environment->getTranslationObject();

      $parse_time_start = convertTimeFromInput($date->getStartingTime());
      $conforms = $parse_time_start['conforms'];
      if ($conforms == TRUE) {
         $start_time_print = getTimeLanguage($parse_time_start['datetime']);
      } else {
         $start_time_print = $text_converter->text_as_html_short($date->getStartingTime());
      }

      $parse_time_end = convertTimeFromInput($date->getEndingTime());
      $conforms = $parse_time_end['conforms'];
      if ($conforms == TRUE) {
         $end_time_print = getTimeLanguage($parse_time_end['datetime']);
      } else {
         $end_time_print = $text_converter->text_as_html_short($date->getEndingTime());
      }

      $parse_day_start = convertDateFromInput($date->getStartingDay(),$environment->getSelectedLanguage());
      $conforms = $parse_day_start['conforms'];
      if ($conforms == TRUE) {
        $start_day_print = $date->getStartingDayName().', '.$translator->getDateInLang($parse_day_start['datetime']);
      } else {
         $start_day_print = $text_converter->text_as_html_short($date->getStartingDay());
      }

      $parse_day_end = convertDateFromInput($date->getEndingDay(),$environment->getSelectedLanguage());
      $conforms = $parse_day_end['conforms'];
      if ($conforms == TRUE) {
         $end_day_print =$date->getEndingDayName().', '.$translator->getDateInLang($parse_day_end['datetime']);
      } else {
         $end_day_print = $text_converter->text_as_html_short($date->getEndingDay());
      }
      //formating dates and times for displaying
      $date_print ="";
      $time_print ="";

      if ($end_day_print != "") { //with ending day
         $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.' '.$translator->getMessage('DATES_TILL').' '.$end_day_print;
         if ($parse_day_start['conforms']
             and $parse_day_end['conforms']) { //start and end are dates, not strings
           $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
         }

         if ($start_time_print != "" and $end_time_print =="") { //starting time given
            $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
             if ($parse_time_start['conforms'] == true) {
               $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
            if ($parse_time_end['conforms'] == true) {
               $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            if ($parse_time_end['conforms'] == true) {
               $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
            if ($parse_time_start['conforms'] == true) {
               $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
            $date_print = $translator->getMessage('DATES_AS_OF').' '.$start_day_print.', '.$start_time_print.$translator->getMessage('DATES_TILL').' '.$end_day_print.', '.$end_time_print;
            if ($parse_day_start['conforms']
                and $parse_day_end['conforms']) {
               $date_print .= ' ('.getDifference($parse_day_start['timestamp'], $parse_day_end['timestamp']).' '.$translator->getMessage('DATES_DAYS').')';
            }
         }

      } else { //without ending day
         $date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
         if ($start_time_print != "" and $end_time_print =="") { //starting time given
             $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
             if ($parse_time_start['conforms'] == true) {
               $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
            if ($parse_time_end['conforms'] == true) {
               $time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            if ($parse_time_end['conforms'] == true) {
               $end_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
            if ($parse_time_start['conforms'] == true) {
               $start_time_print .= ' '.$translator->getMessage('DATES_OCLOCK');
            }
            $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
         }
      }

      if ($parse_day_start['timestamp'] == $parse_day_end['timestamp'] and $parse_day_start['conforms'] and $parse_day_end['conforms']) {
         $date_print = $translator->getMessage('DATES_ON_DAY').' '.$start_day_print;
         if ($start_time_print != "" and $end_time_print =="") { //starting time given
             $time_print = $translator->getMessage('DATES_AS_OF_LOWER').' '.$start_time_print;
         } elseif ($start_time_print == "" and $end_time_print !="") { //endtime given
            $time_print = $translator->getMessage('DATES_TILL').' '.$end_time_print;
         } elseif ($start_time_print != "" and $end_time_print !="") { //all times given
            $time_print = $translator->getMessage('DATES_FROM_TIME_LOWER').' '.$start_time_print.' '.$translator->getMessage('DATES_TILL').' '.$end_time_print;
         }
      }

      // Date and time
      $temp_array = array();
      $temp_array[] = $translator->getMessage('DATES_DATETIME');
      if ($time_print != '') {
         $temp_array[] = $date_print.' '.$time_print;
      } else {
         $temp_array[] = $date_print;
      }
      $tooltip_date = $temp_array;
      return $tooltip_date;
}

function getItemAnnotationChangeStatus($item,$context_id) {
	   global $environment;
	   $translator = $environment->getTranslationObject();
      $current_user = $environment->getCurrentUserItem();
      $related_user = $current_user->getRelatedUserItemInContext($context_id);
      if ($related_user->isUser()) {
         $noticed_manager = $environment->getNoticedManager();
         $noticed_manager->_current_user_id = $related_user->getItemID();
         $annotation_list = $item->getItemAnnotationList();
         $anno_item = $annotation_list->getFirst();
         $new = false;
         $changed = false;
         $date = "0000-00-00 00:00:00";
         while ( $anno_item ) {
            $noticed = $noticed_manager->getLatestNoticed($anno_item->getItemID());
            if ( empty($noticed) ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = true;
                   $changed = false;
                   $date = $anno_item->getModificationDate();
               }
            } elseif ( $noticed['read_date'] < $anno_item->getModificationDate() ) {
               if ($date < $anno_item->getModificationDate() ) {
                   $new = false;
                   $changed = true;
                   $date = $anno_item->getModificationDate();
               }
            }
            $anno_item = $annotation_list->getNext();
         }
         if ( $new ) {
            $info_text =' <span class="changed">['.$translator->getMessage('COMMON_NEW_ANNOTATION').']</span>';
         } elseif ( $changed ) {
            $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_CHANGED_ANNOTATION').']</span>';
         } else {
            $info_text = '';
         }
      } else {
         $info_text = '';
      }
      return $info_text;
}


function getItemChangeStatus($item,$context_id) {
	   global $environment;
	   $translator = $environment->getTranslationObject();
      $current_user = $environment->getCurrentUserItem();
      $related_user = $current_user->getRelatedUserItemInContext($context_id);
      if ($related_user->isUser()) {
         $noticed_manager = $environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestnoticedByUser($item->getItemID(),$related_user->getItemID());
         if ( empty($noticed) ) {
            $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_NEW').']</span>';
         } elseif ( $noticed['read_date'] < $item->getModificationDate() ) {
            $info_text = ' <span class="changed">['.$translator->getMessage('COMMON_CHANGED').']</span>';
         } else {
            $info_text = '';
         }
         // Add change info for annotations (TBD)
      } else {
         $info_text = '';
      }
      return $info_text;
}
?>