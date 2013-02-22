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

// Find current browsing starting point
if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
   $from = 1;
}
$mode = 'browse';
if ( isset($_GET['mode']) ) {
   $mode = $_GET['mode'];
} elseif ( isset($_POST['mode']) ) {
   $mode = $_POST['mode'];
} else {
   unset($ref_iid);
   unset($ref_user);
}


// Find current option
if ( isset($_POST['option']) ) {
   $option = $_POST['option'];
} elseif ( isset($_GET['option']) ) {
   $option = $_GET['option'];
} else {
   $option = '';
}

// Find out what to do
if ( isset($_POST['delete_option']) ) {
   $delete_command = $_POST['delete_option'];
}elseif ( isset($_GET['delete_option']) ) {
   $delete_command = $_GET['delete_option'];
} else {
   $delete_command = '';
}


$context_item = $environment->getCurrentContextItem();
$context_type = $context_item->getType();

// Get the translator object
$translator = $environment->getTranslationObject();

if ( isset($_GET['delete_room_id']) and !empty($_GET['delete_room_id']) ){
   $manager = $environment->getMyRoomManager();
   $room_item =  $manager->getItem($_GET['delete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setNotShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   $params = $environment->getCurrentParameterArray();
   unset($params['delete_room_id']);
   redirect($environment->getCurrentContextID(),'myroom','index',$params);
   unset($params);
} elseif ( isset($_GET['undelete_room_id']) and !empty($_GET['undelete_room_id']) ){
   $manager = $environment->getMyRoomManager();
   $room_item =  $manager->getItem($_GET['undelete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   $params = $environment->getCurrentParameterArray();
   unset($params['undelete_room_id']);
   redirect($environment->getCurrentContextID(),'myroom','index',$params);
   unset($params);
}


if ( isset($_GET['ref_iid']) ) {
   $ref_iid = $_GET['ref_iid'];
} elseif ( isset($_POST['ref_iid']) ) {
   $ref_iid = $_POST['ref_iid'];
}

if ( isset($_GET['ref_user']) ) {
   $ref_user = $_GET['ref_user'];
} elseif ( isset($_POST['ref_user']) ) {
   $ref_user = $_POST['ref_user'];
} else{
   $ref_user ='';
}

// Find current browsing interval
// The browsing interval is applied to all rubrics
$context_item = $environment->getCurrentContextItem();
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
} elseif ( $session->issetValue('interval') ) {
   $interval = $session->getValue('interval');
} else{
   $interval = $context_item->getListLength();
}

// Find current sort key
if ( isset($_GET['sort']) ) {
   $sort = $_GET['sort'];
}  else {
   $sort = 'activity_rev';
}


$mode = 'browse';
if ( !empty($ref_iid) or !empty($ref_user)) {
   if ( isset($_GET['mode']) ) {
      $mode = $_GET['mode'];
   } elseif ( isset($_POST['mode']) ) {
      $mode = $_POST['mode'];
   } else {
      unset($ref_iid);
      unset($ref_user);
   }
}


// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = $environment->getCurrentModule();
   include('pages/index_attach_inc.php');
}


// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   $selstatus = 6;
} else {

   // Find current search text
   if ( isset($_POST['search']) ) {
      $search = $_POST['search'];
      $from = 1;
   } elseif ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
   }
   if (!empty($_POST['selroom'])) {
      $selroom = $_POST['selroom'];
   } else {
      $selroom = 1;
   }


   // time (clock pulses)
   if (!empty($_POST['seltime'])) {
      $seltime = $_POST['seltime'];
     if ($seltime == -2 or $seltime == -3) {
        $seltime = '';
     }
   } elseif (!empty($_GET['seltime'])) {
      $seltime = $_GET['seltime'];
     if ($seltime == -2 or $seltime == -3) {
        $seltime = '';
     }
   } else {
#	  $current_context = $environment->getCurrentContextItem();
#	  $portal_item = $current_context->getContextItem();
#	  if ($current_context->showTime() and $portal_item->showTime()) {
#	     $current_time_item = $portal_item->getCurrentTimeItem();
#	     if (isset($current_time_item)) {
#            $seltime = $current_time_item->getItemID();
#	     } else {
#		    $seltime = '';
#	     }
#	  } else {
       $seltime = '';
#	  }
   }

   $context_item = $environment->getCurrentContextItem();
   $current_room_modules = $context_item->getHomeConf();
   if ( !empty($current_room_modules) ){
      $room_modules = explode(',',$current_room_modules);
   }

   $sel_array = array();
   foreach ( $room_modules as $module ) {
      $link_name = explode('_', $module);
      if ( $link_name[1] != 'none' ) {
         if ($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) {
            // Find current institution selection
            $string = 'sel'.$link_name[0];
            if ( isset($_GET[$string]) and $_GET[$string] !='-2') {
               $sel_array[$link_name[0]] = $_GET[$string];
            } else {
               $sel_array[$link_name[0]] = 0;
            }
         }
      }
   }
   // Find current status selection
   if ( isset($_POST['selcommunityroom']) ) {
      $selcommunityroom = $_POST['selcommunityroom'];
      $from = 1;
   } elseif ( isset($_GET['selcommunityroom']) ) {
      $selcommunityroom = $_GET['selcommunityroom'];
   } elseif ( !empty($ref_iid) ) {
      $selcommunityroom = $ref_iid;
   } else {
      $selcommunityroom = 0;
   }
}



// Get data from database
if ( !isset($room_type) ) {
   include_once('functions/error_functions.php');trigger_error('room_type not set',E_USER_ERROR);
} elseif ( $room_type == CS_PROJECT_TYPE) {
   $manager = $environment->getProjectManager();
} elseif ( $room_type == CS_COMMUNITY_TYPE) {
   $manager = $environment->getCommunityManager();
} elseif ( $room_type == CS_MYROOM_TYPE) {
   $manager = $environment->getMyRoomManager();
}

$context_item = $environment->getCurrentContextItem();


// LIST ACTIONS
// initiate selected array of IDs
$selected_ids = array();
if ($mode == '') {
   $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
}elseif ($mode == 'list_actions') {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_selected_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_selected_ids');
   }
}
      // Update attached items from cookie (requires JavaScript in browser)
      if ( isset($_COOKIE['attach']) ) {
         foreach ( $_COOKIE['attach'] as $key => $val ) {
            setcookie ('attach['.$key.']', '', time()-3600);
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

      // Update attached items from form post (works always)
      if ( isset($_POST['attach']) ) {
         foreach ( $_POST['shown'] as $shown_key => $shown_val ) {
            if ( array_key_exists($shown_key, $_POST['attach']) ) {
               if ( !in_array($shown_key, $selected_ids) ) {
                  $selected_ids[] = $shown_key;
               }
            } else {
               $idx = array_search($shown_key, $selected_ids);
               if ( $idx !== false ) {
                  unset($selected_ids[$idx]);
               }
            }
         }
      }


   ///////////////////////////////////////
   // perform list actions              //
   ///////////////////////////////////////

// Cancel editing
if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   $params = $environment->getCurrentParameterArray();
   redirect($environment->getCurrentContextID(), CS_PROJECT_TYPE, 'index', $params);
}

// Delete item
elseif ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_deleted_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids');
   }
   $manager = $environment->getProjectManager();
   foreach ($selected_ids as $id) {
      $item = $manager->getItem($id);
      $item->delete();
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_deleted_ids');
   $params = $environment->getCurrentParameterArray();
   unset($params['mode']);
   unset($params['select']);
   $selected_ids = array();
   redirect($environment->getCurrentContextID(), CS_PROJECT_TYPE, 'index', $params);
}

   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $room_manager = $environment->getRoomManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $room_item = $room_manager->getItem($id);
               $version_id = $room_item->getVersionID();
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$room_item->getAnnotationList();
               if ( !empty($annotation_list) ){
                  $annotation_item = $annotation_list->getFirst();
                  while($annotation_item){
                     $noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                     $annotation_item = $annotation_list->getNext();
                  }
               }
            }
            break;
         case 3:
            $user = $environment->getCurrentUserItem();
            if( $user->isModerator() ){
                $session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               $params = $environment->getCurrentParameterArray();
               $params['mode'] = 'list_actions';
               $page->addDeleteBox(curl($environment->getCurrentContextID(),CS_PROJECT_TYPE,'index',$params),'index',$selected_ids);
               unset($params);
            }
            break;
         default:
            $params = $environment->getCurrentParameterArray();
            unset($params['mode']);
            redirect($environment->getCurrentContextID(), CS_PROJECT_TYPE, 'index', $params);
      }
      if ($_POST['index_view_action'] != '3'){
         $selected_ids = array();
         $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      }
   } // end if (perform list actions)


$manager->reset();
if ($environment->inCommunityRoom()) {
   $manager->setContextLimit($environment->getCurrentPortalID());
} else {
   $manager->setContextLimit($environment->getCurrentContextID());
}
if ($context_type == CS_COMMUNITY_TYPE) {
   if ( !isset($c_cache_cr_pr) or $c_cache_cr_pr ) {
      $manager->setCommunityroomLimit($environment->getCurrentContextID());
   } else {
      /**
       * use redundant infos in community room
       */
      $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
   }
} elseif ($context_type == CS_MYROOM_TYPE) {
   $current_user_item = $environment->getCurrentUserItem();
   $manager->setUserIDLimit($current_user_item->getUserID());
   $manager->setAuthSourceLimit($current_user_item->getAuthSource());
}
$count_all = $manager->getCountAll();

if ( $environment->inPrivateRoom() ) {
   $user = $environment->getCurrentUserItem();
   $list = $manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
   $count_all = $list->getCount();
}
//*******************************

// Prepare view object
$current_context_item = $environment->getCurrentContextItem();
unset($view);
$with_modifying_actions = false;
if ( $mode != 'detailattach' and $mode != 'formattach' and $current_context_item->isOpen() ) {
   $with_modifying_actions = true;
}
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
if ($room_type == CS_PROJECT_TYPE) {
   $view = $class_factory->getClass(PROJECT_INDEX_VIEW,$params);
} elseif ($room_type == CS_MYROOM_TYPE) {
   $view = $class_factory->getClass(MYROOM_INDEX_VIEW,$params);
} elseif ($room_type == CS_COMMUNITY_TYPE) {
   $view = $class_factory->getClass(COMMUNITY_INDEX_VIEW,$params);
}
unset($params);

foreach ($sel_array as $rubric => $value) {
   if (!empty($value)){
      $manager->setRubricLimit($rubric,$value);
   }
   $label_manager = $environment->getManager($rubric);
   $label_manager->resetLimits();
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $view->setAvailableRubric($rubric,$rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}
//********************************

if ($context_type == CS_PORTAL_TYPE) {
   if ( $room_type == CS_PROJECT_TYPE and !empty($selcommunityroom) ) {
      if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
         $manager->setCommunityroomLimit($selcommunityroom);
      } else {
         /**
          * use redundant infos in community room
          */
         $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
      }
   }
} elseif ($room_type == CS_PROJECT_TYPE and $context_type == CS_COMMUNITY_TYPE) {
   if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
      $manager->setCommunityroomLimit($environment->getCurrentContextID());
   } else {
      /**
       * use redundant infos in community room
       */
      $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
   }
}
if ( !empty($sort) ) {
   $manager->setSortOrder($sort);
}
if ( !empty($search) ) {
   $manager->setSearchLimit($search);
}

// time (clock pulses)
if (!empty($seltime)) {
   $manager->setTimeLimit($seltime);
}

if( $environment->inPrivateRoom() ){
   $user = $environment->getCurrentUserItem();
   $ids = $manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID(),'id_array');
   $count_all_shown = count($ids);
}else{
   $ids = $manager->getIDArray();       // returns an array of item ids
   $count_all_shown = count($ids);
}
if ( $interval > 0 ) {
   $manager->setIntervalLimit($from-1,$interval);
}
$manager->setQueryWithoutExtra();
$manager->select();
unset($list);
if( $environment->inPrivateRoom() ){
   $user = $environment->getCurrentUserItem();
   $list = $manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$environment->getCurrentPortalID());
} else {
   $list = $manager->get();        // returns a cs_list items
}
// Set data for view
if ($room_type == CS_PROJECT_TYPE and $context_type == CS_COMMUNITY_TYPE) {
   $view->setTitle($translator->getMessage('PROJECT_HEADER_IN_COMMUNITY_ROOM',$current_context_item->getTitle()));
} elseif ($room_type == CS_PROJECT_TYPE) {
   $view->setTitle($translator->getMessage('PROJECT_INDEX'));
} elseif ($room_type == CS_COMMUNITY_TYPE) {
   $view->setTitle($translator->getMessage('COMMUNITY_INDEX'));
}
$view->setList($list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);

if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $selected_ids) ) {
         $selected_ids[] = $item->getItemID();
      }
      $item = $list->getNext();
   }
}



$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
if ( $room_type == CS_PROJECT_TYPE and !empty($selcommunityroom) ) {
   $view->setSelectedCommunityRoom($selcommunityroom);
}

$seltime_value = $seltime;
if ($environment->inPrivateRoom()){
   if (!empty($_POST['seltime'])) {
      $seltime_value = $_POST['seltime'];
   } elseif (!empty($_GET['seltime'])) {
      $seltime_value = $_GET['seltime'];
   }
}
if (!empty($seltime_value)) {
   $view->setSelectedTime($seltime_value);
}


if ( !empty($ref_iid) and $mode =='attached' ) {
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_iid);
   $ref_item_manager = $environment->getManager($ref_item_type);
   $ref_item = $ref_item_manager->getItem($ref_iid);
   $view->setRefItem($ref_item);
   $view->setRefIid($ref_iid);
   $view->setIsAttachedList();
} elseif ( !empty($ref_user) and $mode =='attached' ) {
   $item_manager = $environment->getItemManager();
   $ref_item_type = $item_manager->getItemType($ref_user);
   $ref_item_manager = $environment->getManager(CS_USER_TYPE);
   $ref_item = $ref_item_manager->getItem($ref_user);
   $view->setRefItem($ref_item);
   $view->setRefUser($ref_user);
   $view->setIsAttachedList();
}


if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $view->setRefIID($ref_iid);
   if (isset($ref_user)) {
     $view->setRefUser($ref_user);
   }
   $view->setHasCheckboxes($mode);
   $view->setCheckedIDs($new_attach_ids);
   $view->setDontEditIDs($dontedit_attach_ids);
}elseif ($mode == 'attach'){
   $view->setHasCheckboxes('list_actions');
}else{
   $view->setCheckedIDs($selected_ids);
   $view->setHasCheckboxes('list_actions');
}

// Add list view to page
$page->add($view);
unset($list);
unset($view);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$room_type.'_index_ids', $ids);
$session->setValue('interval', $interval);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
?>