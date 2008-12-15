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


if ( isset($_GET['iid']) ) {
   $ref_iid = $_GET['iid'];
} elseif ( isset($_POST['iid']) ) {
   $ref_iid = $_POST['iid'];
}

if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

if ( isset($_POST['return_attach_item_list']) ) {
   $second_call = true;
} elseif ( isset($_GET['return_attach_item_list']) ) {
   $second_call = true;
} else {
   $second_call = false;
}

if ( isset($_GET['search']) ) {
   $search = $_GET['search'];
} elseif ( isset($_POST['search']) ) {
   $search = $_POST['search'];
} else {
   $search = '';
}
if ( isset($_POST['mode']) ) {
   $mode = $_POST['mode'];
} elseif ( isset($_GET['mode']) ) {
   $mode = $_GET['mode'];
} else {
   $mode = '';
}
$sel_activating_status = '';
if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_GET['selactivatingstatus'];
} elseif ( isset($_POST['selactivatingstatus']) and $_POST['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_POST['selactivatingstatus'];
}else {
   $sel_activating_status = 2;
}
if ( isset($_POST['selrubric']) ) {
   $selrubric = $_POST['selrubric'];
   $from = 1;
} elseif ( isset($_GET['selrubric']) ) {
   $selrubric = $_GET['selrubric'];
}  else {
   $selrubric = '';
}

if ( !empty($_POST['linked_only']) and $_POST['linked_only'] == 1 ) {
   $linked_only = true;
} else {
   $linked_only = false;
}

$params = $environment->getCurrentParameterArray();
$item_manager = $environment->getItemManager();
$tmp_item = $item_manager->getItem($ref_iid);
$manager = $environment->getManager($tmp_item->getItemType());
$item = $manager->getItem($ref_iid);

if ($environment->getCurrentModule() == CS_USER_TYPE){
   if ($environment->inCommunityRoom()){
      $selected_ids = $item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
   }else{
      $selected_ids = $item->getLinkedItemIDArray(CS_GROUP_TYPE);
   }

}else{
   $selected_ids = $item->getAllLinkedItemIDArray();
}

if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_linked_items_index_selected_ids');
}elseif ($mode == 'list_actions') {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_linked_items_index_selected_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_linked_items_index_selected_ids');
   }
}
if ( isset($_COOKIE['itemlist']) ) {
   foreach ( $_COOKIE['itemlist'] as $key => $val ) {
      setcookie ('itemlist['.$key.']', '', time()-3600);
      if ( $val == '1' ) {
         if ( !in_array($key, $selected_ids) ) {
            $selected_ids[] = $key;
         }
      } else {
         $idx = array_search($key, $selected_ids);
         if ( $idx !== false ) {
            unset($selected_ids[$idx]);
         }
      }
   }
}
$session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$selected_ids);

// Find current browsing starting point
if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}

// Find current browsing interval
// The browsing interval is applied to all rubrics!
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
}  else {
   $interval = CS_LIST_INTERVAL;
}

if ( !empty($option)
      and (isOption($option, getMessage('COMMON_ITEM_ATTACH')))
    ) {
    $entry_array = array();
    $entry_new_array = array();
    if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_linked_items_index_selected_ids')) {
       $entry_array = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_linked_items_index_selected_ids');
    }
    if (isset($_POST['itemlist'])){
       $selected_id_array = $_POST['itemlist'];
       foreach($selected_id_array as $id => $value){
          $entry_new_array[] = $id;
       }
    }
    $entry_array = array_merge($entry_array,$entry_new_array);
    $entry_array = array_unique($entry_array);
    $item->setLinkedItemsByIDArray($entry_array);
    $item->save();
    $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
    unset($params['attach_view']);
    unset($params['attach_type']);
    unset($params['from']);
    unset($params['pos']);
    unset($params['mode']);
    unset($params['return_attach_item_list']);
    redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
}

$item_list = new cs_list();
$item_ids = array();
$count_all = 0;
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$item_attach_index_view = $class_factory->getClass(ITEM_ATTACH_INDEX_VIEW,$params);
unset($params);

$context_item = $environment->getCurrentContextItem();
$current_room_modules = $context_item->getHomeConf();
if ( !empty($current_room_modules) ){
   $room_modules = explode(',',$current_room_modules);
} else {
   $room_modules =  $default_room_modules;
}

$rubric_array = array();
foreach ( $room_modules as $module ) {
   $link_name = explode('_', $module);
   if ( $link_name[1] != 'none' ) {
      if ( !($environment->inPrivateRoom() and $link_name =='user') ){
         $rubric_array[] = $link_name[0];
      }
   }
}
if ( !empty($selrubric)
     and $selrubric != 'all'
     and $selrubric != 'campus_search'
     and $selrubric != -1
   ) {
   $rubric_array = array();
   $rubric_array[] = $selrubric;
}
if ($environment->getCurrentModule() == CS_USER_TYPE){
   $rubric_array = array();
   if ($context_item->withRubric(CS_GROUP_TYPE)){
      $rubric_array[] = CS_GROUP_TYPE;
   }
   if ($context_item->withRubric(CS_INSTITUTION_TYPE)){
      $rubric_array[] = CS_INSTITUTION_TYPE;
   }
   $interval = 100;
}

foreach ($rubric_array as $rubric) {
   $rubric_ids = array();
   $rubric_list = new cs_list();
   $rubric_manager = $environment->getManager($rubric);
   if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
      $rubric_manager->setContextLimit($environment->getCurrentContextID());
   }
   if ($rubric == CS_DATE_TYPE) {
      $rubric_manager->setWithoutDateModeLimit();
   }
   if ($rubric==CS_USER_TYPE) {
      $rubric_manager->setUserLimit();
      $current_user= $environment->getCurrentUser();
      if ( $current_user->isUser() ) {
          $rubric_manager->setVisibleToAllAndCommsy();
      } else {
          $rubric_manager->setVisibleToAll();
      }
   }
   $count_all = $count_all + $rubric_manager->getCountAll();

   if ( !empty($search) ) {
      $rubric_manager->setSearchLimit($search);
   }

   if ( $linked_only ) {
      $rubric_manager->setIDArrayLimit($selected_ids);
   }

   if ( $sel_activating_status == 2 ) {
      $rubric_manager->showNoNotActivatedEntries();
   }

   if ( $rubric != CS_MYROOM_TYPE ) {
      $rubric_manager->selectDistinct();
      $rubric_list = $rubric_manager->get();
   } else {
      $rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
   }

   $item_list->addList($rubric_list);
   if ($rubric!=CS_MYROOM_TYPE) {
      $temp_rubric_ids = $rubric_manager->getIDArray();
   } else {
      $current_user= $environment->getCurrentUser();
      $temp_rubric_ids = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID(),'id_array');;
   }
   if (!empty($temp_rubric_ids)){
      $rubric_ids = $temp_rubric_ids;
   }
   $session->setValue('cid'.$environment->getCurrentContextID().'_item_attach_index_ids', $rubric_ids);
   $item_ids = array_merge($item_ids, $rubric_ids);
}


$sublist = $item_list->getSubList($from-1,$interval);
$item_attach_index_view->setList($sublist);
$item_attach_index_view->setLinkedItemIDArray($selected_ids);
$item_attach_index_view->setRefItemID($_GET['iid']);
$item_attach_index_view->setCountAllShown(count($item_ids));
$item_attach_index_view->setCountAll($count_all);
$item_attach_index_view->setFrom($from);
$item_attach_index_view->setInterval($interval);
$item_attach_index_view->setSearchText($search);
$item_attach_index_view->setChoosenRubric($selrubric);
$item_attach_index_view->setActivationLimit($sel_activating_status);


?>