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
	$project_list = $user_item->getRelatedProjectList();
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

$result_page = $_GET['page'];
if($result_page > $number_of_pages){
   $result_page = $number_of_pages;
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

while($item){
   $room_name = $room_name_array[$item->getContextID()];
   $hover_text = $translator->getMessage('COMMON_ROOM').': &quot;'.$room_name.'&quot;';
   $result_array[] = array('title' => $item->getTitle(), 'type' => $item->getItemType(), 'iid' => $item->getItemId(), 'cid' => $item->getContextID(), 'hover' => $hover_text);
   $item = $result_list->getNext();
}

$page->add('roomwide_search_info', array('page' => $result_page, 'last' => $number_of_pages, 'from' => $from_display, 'to' => $to_display, 'count' => $count));
$page->add('roomwide_search_results', $result_array);
?>