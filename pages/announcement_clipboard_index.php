<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

include_once('classes/cs_announcement_index_view.php');
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
   unset($return_to['parameter']['add_to_announcement_clipboard']);
}

// globals
$announcement_manager = $environment->getAnnouncementManager();

// array of announcement items in clipboard
$announcement_id_array = $session->getValue('announcement_clipboard');
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
if ( isOption($option,getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
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
   $attach_array = array();
   $error_array = array();
   $error_name_array = array();

   if (!empty($_POST['attach'])) {
      foreach($_POST['attach'] as $key => $value){
         $attach_array[] = $key;
      }
      $import_list = $announcement_manager->getItemList($attach_array);
      $import_announcement = $import_list->getFirst();
      while($import_announcement) {
         $copy = $import_announcement->copy();

       // Fehler mitloggen
		 $err = $copy->getErrorArray();
		 if( !empty($err) ){
             $error_array[$copy->getItemID()] = $err;
             $error_name_array[$copy->getItemID()] = $copy->GetTitle();
		 }
         $import_announcement = $import_list->getNext();
      }
   }

   // Fehlerbehandlung
   if ( !empty($error_array )){
      $err_txt='';
      include_once('classes/cs_errorbox_view.php');
      $errorbox = new cs_errorbox_view($environment, true, 500);

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
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "announcement", "index", $return_to['parameter']);
   }


}
else if($command == 'delete') {
   $attach_array = array();
   if (!empty($_POST['attach'])) {
   foreach($_POST['attach'] as $key => $value){
      $attach_array[] = $key;
   }
      $new_id_array = array();
      foreach($announcement_id_array as $announcement_id) {
         if(!in_array($announcement_id, $attach_array)) {
            $new_id_array[] = $announcement_id;
         }
      }
      $session->setValue('announcement_clipboard', $new_id_array);
      $announcement_id_array = $new_id_array;
   }
   if ( empty($new_id_array) ){
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "announcement", "index", $return_to['parameter']);
   }
}
// list of announcement items to be displayed
$announcement_list = $announcement_manager->getItemList($announcement_id_array);

// Sort the announcement_list By context and than like in session
$announcement_item = $announcement_list->getFirst();
#$announcement_array = array();

$mat_roomIds = array();

$announcement_item = $announcement_list->getFirst();
while($announcement_item) {
   $mat_roomIds[] = $announcement_item->getContextID();
   $announcement_item = $announcement_list->getNext();
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
$checkedAnnouncementIds = array();
$new_announcement_list = new cs_list();

if (!empty($announcement_id_array)) {
      foreach($announcement_id_array as $announcement_id) {
         $announcement_item = $announcement_list->getFirst();
         while($announcement_item) {
            //include_once('functions/error_functions.php');trigger_error('n i y',E_USER_ERROR);
            if($announcement_item->getItemID() == $announcement_id and $announcement_item->getContextID() === 0) {
               $announcement_item = $announcement_manager->getItem($announcement_item->getItemId());
               $new_announcement_list->add($announcement_item);
               $current_context = $environment->getCurrentContextID();
               if ($announcement_item->getContextID() != $current_context){
                  $checkedAnnouncementIds[] = $announcement_item->getItemID();
               }
               break;
            } else {
               $announcement_item = $announcement_list->getNext();
            }
         }
      }
}

// Termine anderer R�ume
$room_sort = $rooms->getFirst();
while ($room_sort) {
   if (!empty($announcement_id_array)) {
      foreach($announcement_id_array as $announcement_id) {
         $announcement_item = $announcement_list->getFirst();
         while($announcement_item) {
            if($announcement_item->getItemID() == $announcement_id and $announcement_item->getContextID() == $room_sort->getItemId()) {
               $announcement_item = $announcement_manager->getItem($announcement_item->getItemId());
               $new_announcement_list->add($announcement_item);
               $current_context = $environment->getCurrentContextID();
               if ($announcement_item->getContextID() != $current_context){
                  $checkedAnnouncementIds[] = $announcement_item->getItemID();
               }
               break;
            } else {
               $announcement_item = $announcement_list->getNext();
            }
         }
      }
   }
   $room_sort = $rooms->getNext();
}

$announcement_list = $new_announcement_list;

// view object
$clipboard_list_view = new cs_announcement_index_view($environment,true);

$id_array = array();
$item = $announcement_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $announcement_list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
$link_manager = $environment->getLinkManager();
$link_manager->getAllFileLinksForListByIDs($id_array);



// Set data for view
$clipboard_list_view->setList($announcement_list);


if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $announcement_list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $checkedAnnouncementIds) ) {
         $checkedAnnouncementIds[] = $item->getItemID();
      }
      $item = $announcement_list->getNext();
   }
}
if (isOption($option,getMessage('COMMON_LIST_ACTION_BUTTON_GO'))){
     $selected_ids = array();
}



// Set checked Items
$clipboard_list_view->setCheckedIDs($checkedAnnouncementIds);
$clipboard_list_view->setClipboardMode();
$clipboard_list_view->setCountAllShown(count($announcement_id_array));
$clipboard_list_view->setCountAll(count($announcement_id_array));
$clipboard_list_view->setFrom(1);
$clipboard_list_view->setInterval(CS_LIST_INTERVAL);

//SetButtons
$context_item = $environment->getCurrentContextItem();

// Add list view to page
$page->add($clipboard_list_view);
?>