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

// Get the translator object
$translator = $environment->getTranslationObject();

if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sort'] = $index_search_parameter_array['sort'];
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['seltopic'] = $index_search_parameter_array['seltopic'];
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
}


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
#   unset($ref_iid);
#   unset($ref_user);
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

// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = CS_GROUP_TYPE;
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
} else {
   $sort = 'title';
}

// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   $seltopic ='';
} else {
   // Find current topic selection
   if ( isset($_GET['seltopic']) and $_GET['seltopic'] !='-2') {
      $seltopic = $_GET['seltopic'];
   } else {
      $seltopic = 0;
   }

   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
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

   // Cancel editing
   if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $params = $environment->getCurrentParameterArray();
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
   }

   // Delete item(s)
   elseif ( isOption($delete_command, $translator->getMessage('COMMON_DELETE_BUTTON')) ) {
      if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                     '_'.$environment->getCurrentModule().
                                    '_deleted_ids')) {
         $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                                  '_'.$environment->getCurrentModule().
                                                  '_deleted_ids');
      }
      $manager = $environment->getManager(module2type($environment->getCurrentModule()));
      foreach ($selected_ids as $id) {
         $item = $manager->getItem($id);
         $item->delete();
      }
      unset($manager);
      unset($item);
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                                 '_'.$environment->getCurrentModule().
                                 '_deleted_ids');
      $params = $environment->getCurrentParameterArray();
      unset($params['mode']);
      unset($params['select']);
      $selected_ids = array();
      redirect($environment->getCurrentContextID(), $environment->getCurrentModule(), $environment->getCurrentFunction(), $params);
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
            $group_manager = $environment->getGroupManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $group_item = $group_manager->getItem($id);
               if ( isset($group_item) ) {
                  $version_id = $group_item->getVersionID();
                  $noticed_manager->markNoticed($id, $version_id );
                  $annotation_list =$group_item->getAnnotationList();
                  if ( !empty($annotation_list) ){
                     $annotation_item = $annotation_list->getFirst();
                     while($annotation_item){
                        $noticed_manager->markNoticed($annotation_item->getItemID(),'0');
                        $annotation_item = $annotation_list->getNext();
                     }
                  }
               }
            }
            break;
         case 2:
            $action = 'ENTRY_COPY';
            // Copy to clipboard
            foreach ($selected_ids as $id) {
               if ( !in_array($id, $clipboard_id_array) ) {
                  $clipboard_id_array[] = $id;
               }
            }
            break;
         case 3:
            $user = $environment->getCurrentUserItem();
            if( $user->isModerator() or $environment->inPrivateRoom() ){
                $session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               $params = $environment->getCurrentParameterArray();
               $params['mode'] = 'list_actions';
               $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params),'index',$selected_ids);
               unset($params);
            }
            break;
         default:
            include_once('functions/error_functions.php');trigger_error('action ist not defined',E_USER_ERROR);
      }
      $selected_ids = array();
      $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
   } // end if (perform list actions)

// Get data from database
$group_manager = $environment->getGroupManager();
$group_manager->setContextLimit($environment->getCurrentContextID());
$group_manager->setTypeLimit('group');
$count_all = $group_manager->getCountAll();
if ( !empty($ref_iid) and $mode == 'attached' ){
   $group_manager->setRefIDLimit($ref_iid);
}
if ( !empty($sort) ) {
   $group_manager->setSortOrder($sort);
}
if ( !empty($seltopic) ) {
   $group_manager->setTopicLimit($seltopic);
}
if ( !empty($search) ) {
   $group_manager->setSearchLimit($search);
}
if ( $interval > 0 ) {
   $group_manager->setIntervalLimit($from-1,$interval);
}
$group_manager->select();
$group_list = $group_manager->get();        // returns a cs_list of group_items
$ids = $group_manager->getIDArray();   // returns an array of item ids
$count_all_shown = count($ids);

if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $group_list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $selected_ids) ) {
         $selected_ids[] = $item->getItemID();
      }
      $item = $group_list->getNext();
   }
}
if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))){
     $selected_ids = array();
}


// Prepare view object
$context_item = $environment->getCurrentContextItem();
// Prepare view object
$with_modifying_actions = false;
if ( $context_item->isProjectRoom() ) {
   if ($context_item->isOpen() AND $mode != 'detailattach' AND $mode != 'formattach')  {
      $with_modifying_actions = true;
   }
} else {
   if ($context_item->isOpen() AND $mode != 'detailattach' AND $mode != 'formattach')  {
      $with_modifying_actions = true;     // Community room
   }
}
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $with_modifying_actions;
$view = $class_factory->getClass(GROUP_INDEX_VIEW,$params);
unset($params);

// Get available topics
if ($context_item->withRubric(CS_TOPIC_TYPE)){
   $topic_manager = $environment->getTopicManager();
   $topic_manager->resetLimits();
   $topic_manager->setContextLimit($environment->getCurrentContextID());
   $topic_manager->setTypeLimit(CS_TOPIC_TYPE);
   $topic_manager->select();
   $topic_list = $topic_manager->get();
   $view->setSelectedTopic($seltopic);
   $view->setAvailableTopics($topic_list);
}


$id_array = array();
$item = $group_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $group_list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);

// Set data for view
$view->setList($group_list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);


if ( !empty($ref_iid) and $mode == 'attached'){
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
$session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);

$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['seltopic'] = $seltopic;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

?>