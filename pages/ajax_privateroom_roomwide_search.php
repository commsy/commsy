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

$user_item = $environment->getCurrentUserItem();

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

// Interval abfangen
$result_list = $complete_list->getSubList(0, 20);

$result_array = array();
$item = $result_list->getFirst();

while($item){
	$room_name = $room_name_array[$item->getContextID()];
	$hover_text = 'Raum: '.$room_name.'';
	$result_array[] = array('title' => $item->getTitle(), 'type' => $item->getItemType(), 'iid' => $item->getItemId(), 'cid' => $item->getContextID(), 'hover' => $hover_text);
	$item = $result_list->getNext();
}

$info_array = array('interval' => '0', 'last' => '2', 'from' => '0', 'to' => '20', 'count' => '50');
$page->add('roomwide_search_info', $info_array);
$page->add('roomwide_search_results', $result_array);
?>