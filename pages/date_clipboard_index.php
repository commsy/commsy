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

if (!empty($_POST['return_to_context'])) {
   $return_to = array();
   $return_to['module'] = $_POST['return_to_module'];
   $return_to['function'] = $_POST['return_to_function'];
   $return_to['context'] = $_POST['return_to_context'];
   $return_to['parameter'] = $_POST['return_to_parameter'];
} else {
   $history = $session->getValue('history');
   $return_to = $history[0];
   #$return_to['parameter'] = preg_replace('/&add_to_clipboard=[0-9]*/','',$return_to['parameter']);
   unset($return_to['parameter']['add_to_date_clipboard']);
}

// globals
$date_manager = $environment->getDateManager();

// Get the translator object
$translator = $environment->getTranslationObject();

// array of date items in clipboard
$date_id_array = $session->getValue('date_clipboard');
// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

// array of material items in clipboard
$material_id_array = $session->getValue('material_clipboard');
// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $option = $_POST['option'];
} elseif (!empty($_GET['option'])) {
   $option = $_GET['option'];
} else {
   $option = '';
}

$command ='';
if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
) {
   switch ($_POST['index_view_action']) {
      case 1:
         $command = 'paste';
         break;
      case 2:
         $command = 'delete';
         break;
   }
}

if (isset($_GET['mode']) and $_GET['mode']=='back'){
   redirect($return_to['context'],$return_to['module'],$return_to['function'],$return_to['parameter']);
}
if($command == 'paste') {
   set_time_limit(500);
   $attach_array = array();
   $error_array = array();
   $error_name_array = array();
   $count = 0;
   if (!empty($_POST['attach'])) {
   foreach($_POST['attach'] as $key => $value){
      $attach_array[] = $key;
   }
      $import_list = $date_manager->getItemList($attach_array);
      $import_date = $import_list->getFirst();
      while($import_date) {
         $copy = $import_date->copy();

         // Fehler mitloggen
         $err = $copy->getErrorArray();
         if( !empty($err) ){
            $error_array[$copy->getItemID()] = $err;
            $error_name_array[$copy->getItemID()] = $copy->GetTitle();
         }
         $count ++;
         $import_date = $import_list->getNext();
      }
   }
   // Fehlerbehandlung
   if ( !empty($error_array )){
      $err_txt='';
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $params['width'] = 500;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);

      foreach($error_array as $key=>$error){
         foreach($error as  $filename){
           $err_txt.= $translator->getMessage('COMMON_FILES_ERROR_MISSING',$error_name_array[$key],$filename).'<br>';
         }
      }
      $err_txt.='<br>'.$translator->getMessage('COMMON_FILES_ERROR_OTHERS_SUCCESSFULL');

      // Fehler-Anzeige
      $errorbox->setText($err_txt);
      $page->add($errorbox);

   } else {
      // zur Zielseite gehen
      if ( $count == 1 ){
         $import_date = $import_list->getFirst();
         $converted_day_start = convertDateFromInput($import_date->getStartingDay(),$environment->getSelectedLanguage());
         if ($converted_day_start['conforms'] == TRUE) {
            $year = mb_substr($converted_day_start['datetime'],0,4);
            $month = mb_substr($converted_day_start['datetime'],5,2);
            $day = mb_substr($converted_day_start['datetime'],8,2);
            $d_time = mktime ( 3, 0, 0, $month, $day, $year );
            $thisdate = date ( "Ymd", $d_time );
            $wday = date ( "w", $d_time );
            $parameter_week = mktime ( 3, 0, 0, $month, $day - ( $wday - 1 ), $year );
            $return_to['parameter']['week'] = $parameter_week;
            $return_to['parameter']['month'] = $year.$month.$day;
            $return_to['parameter']['year'] = $year;
         }else{
            $d_time = mktime ( 3, 0, 0, date("m"), date("d"), date("Y") );
            $thisdate = date ( "Ymd", $d_time );
            $wday = date ( "w", $d_time );
            $parameter_week = mktime ( 3, 0, 0, date("m"), date("d") - ( $wday - 1 ), date("Y") );
            $return_to['parameter']['week'] = $parameter_week;
            $return_to['parameter']['month'] = $thisdate;
            $return_to['parameter']['year'] = date ( "Y", $d_time );
         }
         $return_to['parameter']['presentation_mode'] = '1';
      }
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "date", "index", $return_to['parameter']);
   }
}


else if($command == 'delete') {
   set_time_limit(500);
   $attach_array = array();
   if (!empty($_POST['attach'])) {
   foreach($_POST['attach'] as $key => $value){
      $attach_array[] = $key;
   }
      $new_id_array = array();
      foreach($date_id_array as $date_id) {
         if(!in_array($date_id, $attach_array)) {
            $new_id_array[] = $date_id;
         }
      }
      $session->setValue('date_clipboard', $new_id_array);
      $date_id_array = $new_id_array;
   }
   if ( empty($new_id_array) ){
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "date", "index", $return_to['parameter']);
   }
}
// list of date items to be displayed
$date_list = $date_manager->getItemList($date_id_array);

// Sort the date_list By context and than like in session
$date_item = $date_list->getFirst();
#$date_array = array();

$mat_roomIds = array();

$date_item = $date_list->getFirst();
while($date_item) {
   $mat_roomIds[] = $date_item->getContextID();
   $date_item = $date_list->getNext();
}

$project_manager = $environment->getProjectManager();
$projects = $project_manager->getSortedItemList($mat_roomIds,'title');

$community_manager = $environment->getCommunityManager();
$communities = $community_manager->getSortedItemList($mat_roomIds,'title');

$private_room_manager = $environment->getPrivateRoomManager();
$private_rooms = $private_room_manager->getSortedItemList($mat_roomIds,'title');

$group_room_manager = $environment->getGroupRoomManager();
$group_rooms = $group_room_manager->getSortedItemList($mat_roomIds,'title');

$rooms = new cs_list();
$rooms->addList($projects);
$rooms->addList($communities);
$rooms->addList($private_rooms);
$rooms->addList($group_rooms);


// Termine des Gemeinschaftsraumes
$checkedDateIds = array();
$new_date_list = new cs_list();

if (!empty($date_id_array)) {
      foreach($date_id_array as $date_id) {
         $date_item = $date_list->getFirst();
         while($date_item) {
            //include_once('functions/error_functions.php');trigger_error('n i y',E_USER_ERROR);
            if($date_item->getItemID() == $date_id and $date_item->getContextID() === 0) {
               $date_item = $date_manager->getItem($date_item->getItemId());
               $new_date_list->add($date_item);
               $current_context = $environment->getCurrentContextID();
               if ($date_item->getContextID() != $current_context){
                  $checkedDateIds[] = $date_item->getItemID();
               }
               break;
            } else {
               $date_item = $date_list->getNext();
            }
         }
      }
}

// Termine anderer Räume
$room_sort = $rooms->getFirst();
while ($room_sort) {
   if (!empty($date_id_array)) {
      foreach($date_id_array as $date_id) {
         $date_item = $date_list->getFirst();
         while($date_item) {
            if($date_item->getItemID() == $date_id and $date_item->getContextID() == $room_sort->getItemId()) {
               $date_item = $date_manager->getItem($date_item->getItemId());
               $new_date_list->add($date_item);
               $current_context = $environment->getCurrentContextID();
               if ($date_item->getContextID() != $current_context){
                  $checkedDateIds[] = $date_item->getItemID();
               }
               break;
            } else {
               $date_item = $date_list->getNext();
            }
         }
      }
   }
   $room_sort = $rooms->getNext();
}

$date_list = $new_date_list;

// view object
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$clipboard_list_view = $class_factory->getClass(DATE_INDEX_VIEW,$params);
unset($params);

$id_array = array();
$item = $date_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $date_list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
$link_manager = $environment->getLinkManager();
$link_manager->getAllFileLinksForListByIDs($id_array);


// Set data for view
$clipboard_list_view->setList($date_list);


if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $date_list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $checkedDateIds) ) {
         $checkedDateIds[] = $item->getItemID();
      }
      $item = $date_list->getNext();
   }
}


// Set checked Items
$clipboard_list_view->setCheckedIDs($checkedDateIds);
$clipboard_list_view->setClipboardMode();
$clipboard_list_view->setCountAllShown(count($date_id_array));
$clipboard_list_view->setCountAll(count($date_id_array));
$clipboard_list_view->setFrom(1);
$clipboard_list_view->setInterval(CS_LIST_INTERVAL);

//SetButtons
$context_item = $environment->getCurrentContextItem();

// Add list view to page
$page->add($clipboard_list_view);
?>