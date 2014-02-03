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

if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sort'] = $index_search_parameter_array['sort'];
   $params['selbuzzword'] = $index_search_parameter_array['selbuzzword'];
   $params['seltag_array'] = $index_search_parameter_array['seltag_array'];
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sel_activating_status'] = $index_search_parameter_array['sel_activating_status'];
   $sel_array = $index_search_parameter_array['sel_array'];
   foreach($sel_array as $key => $value){
      $params['sel'.$key] = $value;
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
}

// @segment-begin 4803 read:ref_iid-and/ref_user-from-GET/POST
if ( isset($_GET['ref_iid']) ) {
   $ref_iid = $_GET['ref_iid'];
} elseif ( isset($_POST['ref_iid']) ) {
   $ref_iid = $_POST['ref_iid'];
}

if ( isset($_GET['ref_user']) ) {
   $ref_user = $_GET['ref_user'];
} elseif ( isset($_POST['ref_user']) ) {
   $ref_user = $_POST['ref_user'];
}else{
   $ref_user ='';
}
// @segment-end 4803

// @segment-begin 43488 read:mode-from-GET/POST,_default=browse
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

// Find clipboard id array
if ( $session->issetValue('announcement_clipboard') ) {
   $clipboard_id_array = $session->getValue('announcement_clipboard');
} else {
   $clipboard_id_array = array();
}

// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = CS_ANNOUNCEMENT_TYPE;
   include('pages/index_attach_inc.php');
}

// Find current browsing starting point
if ( isset($_GET['from']) ) {
   $from = $_GET['from'];
}  else {
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

if ( isset($_GET['sort']) ) {
   $sort = $_GET['sort'];
}  else {
   $sort = 'modified';
}

if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   $selinstitution = '';
   $seltopic = '';
   $last_selected_tag = '';
   $seltag_array = array();
   $sel_activating_status = '';
} else {
   $sel_activating_status = '';

   // Find current search text
   if ( isset($_GET['search']) and ($_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_ROOM') || $_GET['search'] != $translator->getMessage('COMMON_SEARCH_IN_RUBRIC'))) {
      $search = $_GET['search'];
   }  else {
      $search = '';
   }

   // Find current sel_activating_status selection
   if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
      $sel_activating_status = $_GET['selactivatingstatus'];
   } else {
      $sel_activating_status = 2;
   }


   // Find current buzzword selection
   if ( isset($_GET['selbuzzword']) and $_GET['selbuzzword'] !='-2') {
      $selbuzzword = $_GET['selbuzzword'];
   } else {
      $selbuzzword = 0;
   }
   $last_selected_tag = '';

   // Find current topic selection
   if ( isset($_GET['seltag']) and $_GET['seltag'] =='yes') {
      $i = 0;
      while ( !isset($_GET['seltag_'.$i]) ){
         $i++;
      }
      $seltag_array[] = $_GET['seltag_'.$i];
      $j = 0;
      while(isset($_GET['seltag_'.$i]) and $_GET['seltag_'.$i] !='-2'){
         if (!empty($_GET['seltag_'.$i])){
            $seltag_array[$i] = $_GET['seltag_'.$i];
            $j++;
         }
         $i++;
      }
      $last_selected_tag = $seltag_array[$j-1];
   }else{
      $last_selected_tag = '';
      $seltag_array = array();
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


}//End Search / Select Area

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
   redirect($environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'index', $params);
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
   $manager = $environment->getAnnouncementManager();
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
   redirect($environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'index', $params);
}

   if ( isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and !isset($_GET['show_copies'])
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $announcement_manager = $environment->getAnnouncementManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $announcement_item = $announcement_manager->getItem($id);
               $version_id = $announcement_item->getVersionID();
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$announcement_item->getAnnotationList();
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
            if( $user->isModerator() ){
                $session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               $params = $environment->getCurrentParameterArray();
               $params['mode'] = 'list_actions';
               $page->addDeleteBox(curl($environment->getCurrentContextID(),CS_ANNOUNCEMENT_TYPE,'index',$params),'index',$selected_ids);
               unset($params);
            }
            break;
         case 'download':
            include_once('include/inc_rubric_download.php');
            break;
         default:
            if ( !empty($_POST['index_view_action'])
                 and ( $environment->isPlugin($_POST['index_view_action'])
                       or $environment->isPlugin(substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_')))
                     )
               ) {
               $plugin = '';
               if ( $environment->isPlugin($_POST['index_view_action']) ) {
                  $plugin = $_POST['index_view_action'];
               } else {
                  $plugin = substr($_POST['index_view_action'],0,strpos($_POST['index_view_action'],'_'));
               }
               plugin_hook_plugin($plugin,'performListAction',$_POST);
            } else {
               $params = $environment->getCurrentParameterArray();
               unset($params['mode']);
               redirect($environment->getCurrentContextID(), CS_ANNOUNCEMENT_TYPE, 'index', $params);
            }
      }
      if ($_POST['index_view_action'] != '3'){
         $selected_ids = array();
         $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      }
   } // end if (perform list actions)

// Get data from database
$announcement_manager = $environment->getAnnouncementManager();
$announcement_manager->setContextLimit($environment->getCurrentContextID());
$all_ids = $announcement_manager->getIds();
$count_all = count($all_ids);
if (isset($all_ids[0])){
   $newest_id = $all_ids[0];
   $item = $announcement_manager->getItem($newest_id);
   $date = $item->getModificationDate();
   $now = getCurrentDateTimeInMySQL();
   if ($date <= $now){
      $sel_activating_status = 1;
   }
}elseif($count_all == 0){
   $sel_activating_status = 1;
}
$announcement_manager->resetData();
if ( !empty($ref_iid) and $mode == 'attached' ){
   $announcement_manager->setRefIDLimit($ref_iid);
}
if ( !empty($ref_user) and $mode == 'attached' ){
   $announcement_manager->setRefUserLimit($ref_user);
}
if ( !empty($sort) ) {
   $announcement_manager->setSortOrder($sort);
}
if ( $sel_activating_status == 2 ) {
   $announcement_manager->showNoNotActivatedEntries();
}
if ( !empty($search) ) {
   $announcement_manager->setSearchLimit($search);
}
if ( !empty($selgroup) ) {
   $announcement_manager->setGroupLimit($selgroup);
}
if ( !empty($selbuzzword) ) {
   $announcement_manager->setBuzzwordLimit($selbuzzword);
}
if ( !empty($last_selected_tag) ){
   $announcement_manager->setTagLimit($last_selected_tag);
}

// Get available buzzwords
$buzzword_manager = $environment->getLabelManager();
$buzzword_manager->resetLimits();
$buzzword_manager->setContextLimit($environment->getCurrentContextID());
$buzzword_manager->setTypeLimit('buzzword');
$buzzword_manager->setGetCountLinks();
$buzzword_manager->select();
$buzzword_list = $buzzword_manager->get();


//*******************************
// Prepare view object

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
$view = $class_factory->getClass(ANNOUNCEMENT_INDEX_VIEW,$params);
unset($params);

foreach($sel_array as $rubric => $value){
   if (!empty($value)){
      $announcement_manager->setRubricLimit($rubric,$value);
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
//********************************

if ( $interval > 0 ) {
   $announcement_manager->setIntervalLimit($from-1,$interval);
}
if ( !empty($only_show_array) ) {
   $announcement_manager->resetLimits();
   $announcement_manager->setIDArrayLimit($only_show_array);
}
$announcement_manager->select();
$list = $announcement_manager->get();        // returns a cs_list of announcement_items
$ids = $announcement_manager->getIDArray();
$count_all_shown = count($ids);

$id_array = array();
$item = $list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
$noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
$link_manager = $environment->getLinkManager();
$file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array);
$file_manager = $environment->getFileManager();
$file_manager->setIDArrayLimit($file_id_array);
$file_manager->select();

if (isset($_GET['select']) and $_GET['select']=='all'){
   $item = $list->getFirst();
   while($item){
      if ( !in_array($item->getItemID(), $selected_ids) ) {
         $selected_ids[] = $item->getItemID();
      }
      $item = $list->getNext();
   }
}
if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
     $selected_ids = array();
}




// Set data for view
$view->setList($list);
$view->setCountAllShown($count_all_shown);
$view->setCountAll($count_all);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setClipboardIDArray($clipboard_id_array);

$view->setAvailableBuzzwords($buzzword_list);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setActivationLimit($sel_activating_status);

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

// @segment-end 50396

// @segment-begin 40245 add-view-object-to-page-object

// Add list view to page
$page->add($view);

// @segment-end 40245


$session->setValue('announcement_clipboard', $clipboard_id_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids', $ids);
$session->setValue('interval', $interval);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);

$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['sel_array'] = $sel_array;
$index_search_parameter_array['selbuzzword'] = $selbuzzword;
$index_search_parameter_array['seltag_array'] = $seltag_array;
$index_search_parameter_array['sel_activating_status'] = $sel_activating_status;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');
?>