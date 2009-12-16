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
   unset($return_to['parameter']['add_to_material_clipboard']);
}

// globals
$material_manager = $environment->getMaterialManager();

// Get the translator object
$translator = $environment->getTranslationObject();

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
if ($command == 'paste') {
   set_time_limit(500);
   $attach_array = array();
   $error_array = array();
   $error_name_array = array();

   if (!empty($_POST['attach'])) {
      foreach($_POST['attach'] as $key => $value) {
         $attach_array[] = $key;
      }
      $import_list = $material_manager->getItemList($attach_array);
      $import_material = $import_list->getFirst();
      $copy_id_array = array();
      while ($import_material) {
         if ( !in_array($import_material->getItemID(),$copy_id_array) ) { // copy only newest version
            $copy_id_array[] = $import_material->getItemID();
            $copy = $import_material->copy();

            // Fehler mitloggen
            $err = $copy->getErrorArray();
            if( !empty($err) ){
               $error_array[$copy->getItemID()] = $err;
               $error_name_array[$copy->getItemID()] = $copy->GetTitle();
            }
         }
         $import_material = $import_list->getNext();
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
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "material", "index", $return_to['parameter']);
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
      foreach($material_id_array as $material_id) {
         if(!in_array($material_id, $attach_array)) {
            $new_id_array[] = $material_id;
         }
      }
      $session->setValue('material_clipboard', $new_id_array);
      $material_id_array = $new_id_array;
   }
   if ( empty($new_id_array) ){
      $return_to['parameter']['select']='';
      redirect($return_to['context'], "material", "index", $return_to['parameter']);
   }
}

// list of material items to be displayed
$material_list = $material_manager->getItemList($material_id_array);

// Sort the material_list By context and than like in session
$material_item = $material_list->getFirst();

$mat_roomIds = array();

$material_item = $material_list->getFirst();
while($material_item) {
   $mat_roomIds[] = $material_item->getContextID();
   $material_item = $material_list->getNext();
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


// Materialien des Gemeinschaftsraumes
$checkedMaterialIds = array();
$new_material_list = new cs_list();

if (!empty($material_id_array)) {
      foreach($material_id_array as $material_id) {
         $material_item = $material_list->getFirst();
         while($material_item) {
            //include_once('functions/error_functions.php');trigger_error('n i y',E_USER_ERROR);
            if($material_item->getItemID() == $material_id and $material_item->getContextID() === 0) {
                //Get latest Version
               $latest_version = $material_manager->getLatestVersionID($material_item->getItemId());
               $material_item = $material_manager->getItemByVersion($material_item->getItemId(),$latest_version);
               $new_material_list->add($material_item);
               $current_context = $environment->getCurrentContextID();
               if ($material_item->getContextID() != $current_context){
                  $checkedMaterialIds[] = $material_item->getItemID();
               }
               break;
            } else {
               $material_item = $material_list->getNext();
            }
         }
      }
}

// Materialien anderer Räume
$room_sort = $rooms->getFirst();
while ($room_sort) {
   if (!empty($material_id_array)) {
      foreach($material_id_array as $material_id) {
         $material_item = $material_list->getFirst();
         while($material_item) {
            if($material_item->getItemID() == $material_id and $material_item->getContextID() == $room_sort->getItemId()) {
                //Get latest Version
               $latest_version = $material_manager->getLatestVersionID($material_item->getItemId());
               $material_item = $material_manager->getItemByVersion($material_item->getItemId(),$latest_version);
               $new_material_list->add($material_item);
               $current_context = $environment->getCurrentContextID();
               if ($material_item->getContextID() != $current_context){
                  $checkedMaterialIds[] = $material_item->getItemID();
               }
               break;
            } else {
               $material_item = $material_list->getNext();
            }
         }
      }
   }
   $room_sort = $rooms->getNext();
}

$material_list = $new_material_list;

// view object
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$clipboard_list_view = $class_factory->getClass(MATERIAL_INDEX_VIEW,$params);
unset($params);

// Set data for view
$clipboard_list_view->setList($material_list);

$id_array = array();
$item = $material_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $material_list->getNext();
}

$section_manager = $environment->getSectionManager();
$section_list = $section_manager->getAllSectionItemListByIDArray($id_array);

$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);


$item = $section_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $section_list->getNext();
}
$link_manager = $environment->getLinkManager();
$link_manager->getAllFileLinksForListByIDs($id_array);




if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $material_list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $checkedMaterialIds) ) {
         $checkedMaterialIds[] = $item->getItemID();
      }
      $item = $material_list->getNext();
   }
}


// Set checked Items
$clipboard_list_view->setCheckedIDs($checkedMaterialIds);
$clipboard_list_view->setClipboardMode();
$clipboard_list_view->setCountAllShown(count($material_id_array));
$clipboard_list_view->setCountAll(count($material_id_array));
$clipboard_list_view->setFrom(1);
$clipboard_list_view->setInterval(CS_LIST_INTERVAL);

//SetButtons
$context_item = $environment->getCurrentContextItem();

// Add list view to page
$page->add($clipboard_list_view);
?>