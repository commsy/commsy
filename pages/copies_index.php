<?PHP
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

include_once('classes/cs_list.php');

/*
$context_item = $environment->getCurrentContextItem();
$session = $environment->getSession();
$current_room_modules = $context_item->getHomeConf();
if ( !empty($current_room_modules) ){
   $room_modules = explode(',',$current_room_modules);
} else {
   $room_modules =  array();
}
unset($current_room_modules);
$modules = array();
foreach ( $room_modules as $module ) {
   $link_name = explode('_', $module);
   if ( $link_name[1] != 'none') {
      $modules[] = $link_name[0];
   }
}
unset($room_modules);
$html_array = array();
$rubric_copy_array = array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_MATERIAL_TYPE,CS_TODO_TYPE);
$count = 0;

$copy_list = new cs_list();

foreach ($rubric_copy_array as $rubric){
   $rubric_ids = array();
   $rubric_list = new cs_list();
   $rubric_manager = $environment->getManager($rubric);
   if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
      $rubric_manager->setContextLimit($environment->getCurrentContextID());
   }
   if ($rubric=='user') {
      $rubric_manager->setUserLimit();
      $current_user= $environment->getCurrentUser();
      if ( $current_user->isUser() ) {
         $rubric_manager->setVisibleToAllAndCommsy();
      } else {
          $rubric_manager->setVisibleToAll();
      }
   } elseif ($rubric == CS_DATE_TYPE) {
      $rubric_manager->setWithoutDateModeLimit();
   }
   if ( $rubric != CS_MYROOM_TYPE ) {
      $rubric_manager->selectDistinct();
      $rubric_list = $rubric_manager->get();
   } else {
      $rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
   }
   $copy_list->addList($rubric_list);
   $id_array = $session->getValue($rubric.'_clipboard');
   $count += count($id_array);
   unset($rubric_manager);
}
unset($rubric_copy_array);
unset($context_item);



$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$copy_view = $class_factory->getClass(COPY_INDEX_VIEW,$params);
unset($params);


$copy_view->setList($copy_list);
$copy_view->setCountAllShown($count);
$copy_view->setCountAll($count);
$copy_view->setFrom(0);
$copy_view->setInterval($interval);

// Add list view to page*/

$context_item = $environment->getCurrentContextItem();
$session = $environment->getSession();
$current_room_modules = $context_item->getHomeConf();

// Get the translator object
$translator = $environment->getTranslationObject();

if ( !empty($current_room_modules) ){
   $room_modules = explode(',',$current_room_modules);
} else {
   $room_modules =  array();
}
unset($current_room_modules);
$modules = array();
foreach ( $room_modules as $module ) {
   $link_name = explode('_', $module);
   if ( $link_name[1] != 'none') {
      $modules[] = $link_name[0];
   }
}
unset($room_modules);
$html_array = array();
$rubric_copy_array = array(CS_ANNOUNCEMENT_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_MATERIAL_TYPE,CS_TODO_TYPE);
$count = 0;

if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
if (!empty($_POST['option'])) {
   $option = $_POST['option'];
} elseif (!empty($_GET['option'])) {
   $option = $_GET['option'];
} else {
   $option = '';
}
$command ='';
$item_roomIds = array();
$item_list = new cs_list();
$tmp_id_array = array();




foreach ($rubric_copy_array as $rubric){
   $item_manager = $environment->getManager($rubric);
   $item_id_array = $session->getValue($rubric.'_clipboard');
   // list of items to be displayed


   // Sort the announcement_list By context and than like in session
   $item_list->addList($item_manager->getItemList($item_id_array));
   $item = $item_list->getFirst();
   while($item) {
      $item_roomIds[] = $item->getContextID();
      $item = $item_list->getNext();
   }
   if (is_array($item_id_array)){
      $tmp_id_array = array_merge($tmp_id_array, $item_id_array);
   }
}

$item_id_array = $tmp_id_array;

if ( isOption($option,$translator->getMessage('COMMON_COPY_LIST_ACTION_BUTTON_GO'))
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
if($command == 'paste') {   
   $attach_array = array();
   $error_array = array();
   $error_name_array = array();
   $rubric = '';
   $count = 0;
   $iid = '';
   if (!empty($_POST['attach'])) {
      foreach($_POST['attach'] as $key => $value){        
         $manager = $environment->getItemManager();
         $item = $manager->getItem($key);
         $item_manager = $environment->getManager($item->getItemType());
         $import_item = $item_manager->getItem($key);         
         $copy = $import_item->copy();
         $count++;
         $rubric = $item->getItemType();
         $iid = $copy->getItemID();
         $err = $copy->getErrorArray();
         if( !empty($err) ){
            $error_array[$copy->getItemID()] = $err;
            $error_name_array[$copy->getItemID()] = $copy->GetTitle();
         }
      }
   }
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
      $errorbox->setText($err_txt);
      $page->add($errorbox);
   } else {
      $params = $environment->getCurrentParameterArray();
      unset($params['show_copies']);
      if($count == 1 and !empty($rubric) and !empty($iid)){
         $params['iid'] = $iid;
         redirect($environment->getCurrentContextID(), $rubric, 'detail', $params);
      }elseif(!empty($rubric)){
         redirect($environment->getCurrentContextID(), $rubric, 'index', $params);
      }else{
         redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
      }
   }
}elseif($command == 'delete') {
   $tmp_array = array();
   $attach_array = array();
   if (!empty($_POST['attach'])) {
      foreach($_POST['attach'] as $key => $value){
         $manager = $environment->getItemManager();
         $item = $manager->getItem($key);
         $attach_array[$item->getItemType()][] = $key;
      }
      foreach ($attach_array as $rubric => $array){
         $new_id_array = array();
         foreach($item_id_array as $item_id) {
            if(!in_array($item_id, $array)) {
               $new_id_array[] = $item_id;
            }
         }
         $session->setValue($rubric.'_clipboard', $new_id_array);
         $tmp_array = array_merge($tmp_array, $new_id_array);
      }
   }
#  if ( empty($tmp_array) ){
      $params = $environment->getCurrentParameterArray();
      unset($params['show_copies']);
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
#  }
}





$project_manager = $environment->getProjectManager();
$projects = $project_manager->getSortedItemList($item_roomIds,'title');

$community_manager = $environment->getCommunityManager();
$communities = $community_manager->getSortedItemList($item_roomIds,'title');

$private_room_manager = $environment->getPrivateRoomManager();
$private_rooms = $private_room_manager->getSortedItemList($item_roomIds,'title');

$group_room_manager = $environment->getGroupRoomManager();
$group_rooms = $group_room_manager->getSortedItemList($item_roomIds,'title');

$rooms = new cs_list();
$rooms->addList($projects);
$rooms->addList($communities);
$rooms->addList($private_rooms);
$rooms->addList($group_rooms);


$checkedIds = array();
$new_item_list = new cs_list();
if (!empty($item_id_array)) {
      foreach($item_id_array as $item_id) {
         $item = $item_list->getFirst();
         while($item) {
            if($item->getItemID() == $item_id and $item->getContextID() === 0) {
               $item_manager = $environment->getManager($item->getItemType());
               $item = $item_manager->getItem($item->getItemId());
               $new_item_list->add($item);
               $current_context = $environment->getCurrentContextID();
               if ($item->getContextID() != $current_context){
                  $checkedIds[] = $item->getItemID();
               }
               break;
            } else {
               $item = $item_list->getNext();
            }
         }
      }
}

$room_sort = $rooms->getFirst();
while ($room_sort) {
   if (!empty($item_id_array)) {
      foreach($item_id_array as $item_id) {
         $item = $item_list->getFirst();
         while($item) {
            if($item->getItemID() == $item_id and $item->getContextID() == $room_sort->getItemId()) {
               $item_manager = $environment->getManager($item->getItemType());
               $item = $item_manager->getItem($item->getItemId());
               $new_item_list->add($item);
               $current_context = $environment->getCurrentContextID();
               if ( isset($item) and $item->getContextID() != $current_context){
                  $checkedIds[] = $item->getItemID();
               }
               break;
            } else {
               $item = $item_list->getNext();
            }
         }
      }
   }
   $room_sort = $rooms->getNext();
}

$item_list = $new_item_list;

// view object
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$copy_view = $class_factory->getClass(COPY_INDEX_VIEW,$params);
unset($params);

$id_array = array();
$item = $item_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $item_list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
$link_manager = $environment->getLinkManager();
$link_manager->getAllFileLinksForListByIDs($id_array);



// Set data for view
$copy_view->setList($item_list);


if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $item_list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $checkedIds) ) {
         $checkedIds[] = $item->getItemID();
      }
      $item = $item_list->getNext();
   }
}
if (isOption($option,$translator->getMessage('COMMON_COPY_LIST_ACTION_BUTTON_GO'))){
     $selected_ids = array();
}



// Set checked Items
$copy_view->setCheckedIDs($checkedIds);
$copy_view->setClipboardMode();
$copy_view->setCountAllShown(count($item_id_array));
$copy_view->setCountAll(count($item_id_array));
$copy_view->setFrom(1);
$copy_view->setInterval(CS_LIST_INTERVAL);

//SetButtons
$context_item = $environment->getCurrentContextItem();


?>