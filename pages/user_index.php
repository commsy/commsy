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

// Get the translator object
$translator = $environment->getTranslationObject();

if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sort'] = $index_search_parameter_array['sort'];
   $params['selstatus'] = $index_search_parameter_array['selstatus'];
   $params['interval'] = $index_search_parameter_array['interval'];
   $sel_array = $index_search_parameter_array['sel_array'];
   foreach($sel_array as $key => $value){
      $params['sel'.$key] = $value;
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
}

$current_user = $environment->getCurrentUser();
$current_context = $environment->getCurrentContextItem();

if ($environment->inPrivateRoom()){
   $params['iid'] = $environment->getCurrentUserID();
   redirect($environment->getCurrentContextID(), $current_module, 'detail', $params);
} else {

   $current_user = $environment->getCurrentUser();
   $current_context = $environment->getCurrentContextItem();
   if ( !($current_user->isUser() or ($environment->inCommunityRoom() and $current_context->isOpenForGuests() ) )
        or ($environment->inPortal())
      ) {
      if ( $environment->inPortal() ) {
         redirect($environment->getCurrentContextID(), 'home', 'index', '');
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
         $page->add($errorbox);
      }

   } elseif(!$current_context->withRubric(CS_USER_TYPE)) {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_RUBRIC_DOES_NOT_EXIST'));
         $page->add($errorbox);
   }else{

   include_once('classes/cs_list.php');

   // Find current page mode. Modes are:
   //   browse       = standard, simply show items
   //   detailattach = attach_iid is set, show checkboxes
   //                  attach from a detail view
   //                  save changes to links
   //   formattach   = formattach_iid is set, show checkboxes
   //                  attach from a form view
   //                  do not changes, but leave in session
   //   attached     = ref_iid is set, show backlink
   //                  show all items attached to the ref item

   if ( isset($_GET['ref_iid']) ) {
      $ref_iid = $_GET['ref_iid'];
   } elseif ( isset($_POST['ref_iid']) ) {
      $ref_iid = $_POST['ref_iid'];
   }

   $mode = 'browse';
   if ( isset($_GET['mode']) ) {
      $mode = $_GET['mode'];
   } elseif ( isset($_POST['mode']) ) {
      $mode = $_POST['mode'];
   } else {
      unset($ref_iid);
   }


   // Find current option
   if ( isset($_POST['option']) ) {
      $option = $_POST['option'];
   } elseif ( isset($_GET['option']) ) {
      $option = $_GET['option'];
   } else {
      $option = '';
   }

   // Handle attaching
   if ( $mode == 'formattach' or $mode == 'detailattach' ) {
      $attach_type = CS_USER_TYPE;
      include('pages/index_attach_inc.php');
   }


   // Find current browsing starting point
   if ( isset($_GET['from']) ) {
      $from = $_GET['from'];
   } else {
      $from = 1;
   }

// Find current browsing interval
// The browsing interval is applied to all rubrics
if ( isset($_GET['interval']) ) {
   $interval = $_GET['interval'];
} elseif ( $session->issetValue('interval') ) {
   $interval = $session->getValue('interval');
} else{
   $interval = $current_context->getListLength();
}

   // Find current sort key
   if ( isset($_GET['sort']) ) {
      $sort = $_GET['sort'];
   } else {
      $sort = 'name';
   }

   // Search / Select Area
   if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
      $search = '';
      $selgroup = '';
      $seltopic = '';
      $selinstitution = '';
   } else {

      // Find current search text
      if ( isset($_GET['search']) ) {
         $search = $_GET['search'];
      } else {
         $search = '';
      }
   $context_item = $environment->getCurrentContextItem();
   $current_room_modules = $context_item->getHomeConf();
   if ( !empty($current_room_modules) ){
      $room_modules = explode(',',$current_room_modules);
   } else {
      $room_modules =  $default_room_modules;
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
      if ( isset($_GET['selstatus']) and $_GET['selstatus']!=2 and $_GET['selstatus']!='-2' ) {
         $selstatus = $_GET['selstatus'];
      } else {
         $selstatus = '';
      }
   }

   $context = $environment->getCurrentContextItem();


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

   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $user_manager = $environment->getUserManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $user_item = $user_manager->getItem($id);
               $version_id = $user_item->getVersionID();
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$user_item->getAnnotationList();
               if ( !empty($annotation_list) ){
                  $annotation_item = $annotation_list->getFirst();
                  while($annotation_item){
                     $noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                     $annotation_item = $annotation_list->getNext();
                  }
               }
            }
            break;
         case 2:
            $action = 'USER_EMAIL_SEND';

         $current_user = $environment->getCurrentUser();
         $user_item_id = $current_user->getItemID();
         $action_array = array();
         $action_array['user_item_id'] = $user_item_id;
         $action_array['action'] = $action;
         $action_array['backlink']['cid'] = $environment->getCurrentContextID();
         $action_array['backlink']['mod'] = $environment->getCurrentModule();
         $action_array['backlink']['fct'] = $environment->getCurrentFunction();
         $action_array['backlink']['par'] = '';
         $action_array['selected_ids'] = $selected_ids;
         $params = array();
         $params['step'] = 1;
         $session->setValue('index_action',$action_array);
         redirect( $environment->getCurrentContextID(),
                   $environment->getCurrentModule(),
                   'action',
                   $params);
            break;
         default:
            include_once('functions/error_functions.php');
            trigger_error('action ist not defined',E_USER_ERROR);
      }
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      $selected_ids = array();
   } // end if (perform list actions)



   // Get data from database
   $user_manager = $environment->getUserManager();
   $user_manager->reset();
   $user_manager->setContextLimit($environment->getCurrentContextID());
   $user_manager->setUserLimit();
   $count_all = $user_manager->getCountAll();
   if ( !empty($ref_iid) and $mode == 'attached' ){
      $user_manager->setRefIDLimit($ref_iid);
   }
   if ( !empty($sort) ) {
      $user_manager->setSortOrder($sort);
   }
   if ( !empty($search) ) {
      $user_manager->setSearchLimit($search);
   }
   if ( !empty($selgroup) ) {
      $user_manager->setGroupLimit($selgroup);
   }
   if ( !empty($seltopic) ) {
      $user_manager->setTopicLimit($seltopic);
   }
   if ( !empty($selstatus) ) {
      if ($selstatus == 11) {
         $user_manager->setUserInProjectLimit();
      } elseif ($selstatus == 12) {
         $user_manager->setContactModeratorInProjectLimit();
      } else {
         $user_manager->setStatusLimit($selstatus);
      }
   }
   if ( $environment->inCommunityRoom() ){
      $current_user =$environment->getCurrentUser();
      if ( $current_user->isUser() ) {
         $user_manager->setVisibleToAllAndCommsy();
      } else {
         $user_manager->setVisibleToAll();
      }
   }
   // Prepare view object
   $context_item = $environment->getCurrentContextItem();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $context_item->isOpen();
   $view = $class_factory->getClass(USER_INDEX_VIEW,$params);
   unset($params);

foreach($sel_array as $rubric => $value){
   if (!empty($value)){
      $user_manager->setRubricLimit($rubric,$value);
   }
   $label_manager = $environment->getManager($rubric);
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $temp_rubric_list = clone $rubric_list;
   $view->setAvailableRubric($rubric,$temp_rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}
   $ids = $user_manager->getIDArray();       // returns an array of item ids
   $count_all_shown = count($ids);
   if ( $interval > 0 ) {
      $user_manager->setIntervalLimit($from-1,$interval);
   }
   $user_manager->select();
   $list = $user_manager->get();        // returns a cs_list of user_items

   if (isset($_GET['select']) and $_GET['select']=='all'){
      $item = $list->getFirst();
      while($item){
         if ( !in_array($item->getItemID(), $selected_ids) ) {
            $selected_ids[] = $item->getItemID();
         }
         $item = $list->getNext();
      }
   }



$id_array = array();
$item = $list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);

   // Set data for view
   $view->setList($list);
   $view->setCountAll($count_all);
   $view->setCountAllShown($count_all_shown);
   $view->setFrom($from);
   $view->setInterval($interval);
   $view->setSortKey($sort);
   $view->setSearchText($search);
   $view->setSelectedStatus($selstatus);

   if ( !empty($ref_iid) and $mode =='attached'){
      $item_manager = $environment->getItemManager();
      $ref_item_type = $item_manager->getItemType($ref_iid);
      $ref_item_manager = $environment->getManager($ref_item_type);
      $ref_item = $ref_item_manager->getItem($ref_iid);
      $view->setRefItem($ref_item);
      $view->setRefIid($ref_iid);
      $view->setIsAttachedList();
   }

   if ( $mode == 'formattach' or $mode == 'detailattach' ) {
      $view->setRefIID($ref_iid);
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

   // Safe information in session for later use
   $session->setValue('interval', $interval); // interval is applied to all rubrics
   $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $ids);
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);

   $index_search_parameter_array = array();
   $index_search_parameter_array['interval'] = $interval;
   $index_search_parameter_array['sort'] = $sort;
   $index_search_parameter_array['search'] = $search;
   $index_search_parameter_array['sel_array'] = $sel_array;
   $index_search_parameter_array['selstatus'] = $selstatus;
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

}

}
?>