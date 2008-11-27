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
if ( $session->issetValue('discussion_clipboard') ) {
   $clipboard_id_array = $session->getValue('discussion_clipboard');
} else {
   $clipboard_id_array = array();
}

// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = CS_DISCUSSION_TYPE;
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
   $sort = 'latest';
}

// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],getMessage('COMMON_RESET')) ) {
   $search = '';
   $selgroup = '';
   $seltopic = '';
   $selinstitution = '';
   $last_selected_tag = '';
   $seltag_array = array();
   $sel_activating_status = '';
} else {
   $sel_activating_status = '';
   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
   }




   // Find current buzzword selection
   if ( isset($_GET['selbuzzword']) and $_GET['selbuzzword'] !='-2') {
      $selbuzzword = $_GET['selbuzzword'];
   } else {
      $selbuzzword = 0;
   }
   $last_selected_tag = '';

   // Find current sel_activating_status selection
   if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
      $sel_activating_status = $_GET['selactivatingstatus'];
   } else {
      $sel_activating_status = 2;
   }

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



   // Find current group selection
   if ( isset($_GET['selgroup'])  and $_GET['selgroup'] !='-2') {
      $selgroup = $_GET['selgroup'];
   } else {
      $selgroup = 0;
   }

   // Find current topic selection
   if ( isset($_GET['seltopic'])  and $_GET['seltopic'] !='-2') {
      $seltopic = $_GET['seltopic'];
   } else {
      $seltopic = 0;
   }

   // Find current institution selection
   if ( isset($_GET['selinstitution']) and $_GET['selinstitution'] != '-2') {
      $selinstitution = $_GET['selinstitution'];
   } else {
      $selinstitution = 0;
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
if ( isOption($delete_command, getMessage('COMMON_CANCEL_BUTTON')) ) {
   $params = $environment->getCurrentParameterArray();
   redirect($environment->getCurrentContextID(), CS_DISCUSSION_TYPE, 'index', $params);
}

// Delete item
elseif ( isOption($delete_command, getMessage('COMMON_DELETE_BUTTON')) ) {
   if ($session->issetValue('cid'.$environment->getCurrentContextID().
                                  '_'.$environment->getCurrentModule().
                                 '_deleted_ids')) {
      $selected_ids = $session->getValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids');
   }
   $manager = $environment->getDiscussionManager();
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
   redirect($environment->getCurrentContextID(), CS_DISCUSSION_TYPE, 'index', $params);
}

   if ( isOption($option,getMessage('COMMON_LIST_ACTION_BUTTON_GO'))
        and !isset($_GET['show_copies'])
        and $_POST['index_view_action'] != '-1'
        and !empty($selected_ids)
      ) {
      // prepare action process
      switch ($_POST['index_view_action']) {
         case 1:
            $action = 'ENTRY_MARK_AS_READ';
            $error = false;
            $discussion_manager = $environment->getDiscussionManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $discussion_item = $discussion_manager->getItem($id);
               $version_id = $discussion_item->getVersionID();
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$discussion_item->getAnnotationList();
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
               $page->addDeleteBox(curl($environment->getCurrentContextID(),CS_DISCUSSION_TYPE,'index',$params),'index',$selected_ids);
               unset($params);
            }
            break;
         default:
            $params = $environment->getCurrentParameterArray();
            unset($params['mode']);
            redirect($environment->getCurrentContextID(), CS_DISCUSSION_TYPE, 'index', $params);
      }
      if ($_POST['index_view_action'] != '3'){
         $selected_ids = array();
         $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      }
   } // end if (perform list actions)

// Get data from database
$discussion_manager = $environment->getDiscussionManager();
$discussion_manager->setContextLimit($environment->getCurrentContextID());

$context_item = $environment->getCurrentContextItem();

$count_all = $discussion_manager->getCountAll();


if ( !empty($ref_iid) and $mode == 'attached' ){
   $discussion_manager->setRefIDLimit($ref_iid);
}
if ( !empty($ref_user) and $mode == 'attached' ){
   $discussion_manager->setRefUserLimit($ref_user);
}

if ( !empty($sort) ) {
   $discussion_manager->setSortOrder($sort);
}
if ( $sel_activating_status == 2 ) {
   $discussion_manager->showNoNotActivatedEntries();
}
if ( !empty($search) ) {
   $discussion_manager->setSearchLimit($search);
}
if ( !empty($selgroup) ) {
   $discussion_manager->setGroupLimit($selgroup);
}
if ( !empty($seltopic) ) {
   $discussion_manager->setTopicLimit($seltopic);
}
if ( !empty($selinstitution) ) {
   $discussion_manager->setInstitutionLimit($selinstitution);
}





if ( !empty($selbuzzword) ) {
   $discussion_manager->setBuzzwordLimit($selbuzzword);
}
if ( !empty($last_selected_tag) ){
   $discussion_manager->setTagLimit($last_selected_tag);
}






$ids = $discussion_manager->getIDArray();       // returns an array of item ids
$count_all_shown = count($ids);
if ( $interval > 0 ) {
   $discussion_manager->setIntervalLimit($from-1,$interval);
}
$discussion_manager->select();
$list = $discussion_manager->get();        // returns a cs_list of discussion_items



$id_array = array();
$item = $list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $list->getNext();
}
$discarticle_manager = $environment->getDiscussionArticleManager();
$discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($id_array);


$item = $discarticle_list->getFirst();
while ($item){
   $id_array[] = $item->getItemID();
   $item = $discarticle_list->getNext();
}
$noticed_manager = $environment->getNoticedManager();
$noticed_manager->getLatestNoticedByIDArray($id_array);
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

if (isOption($option,getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
     $selected_ids = array();
}
$context_item = $environment->getCurrentContextItem();




// Get available buzzwords
$buzzword_manager = $environment->getLabelManager();
$buzzword_manager->resetLimits();
$buzzword_manager->setContextLimit($environment->getCurrentContextID());
$buzzword_manager->setTypeLimit('buzzword');
$buzzword_manager->setGetCountLinks();
$buzzword_manager->select();
$buzzword_list = $buzzword_manager->get();







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
$view = $class_factory->getClass(DISCUSSION_INDEX_VIEW,$params);
unset($params);

if($context_item->withRubric(CS_GROUP_TYPE)){
   // Get available groups
   $group_manager = $environment->getGroupManager();
   $group_manager->resetLimits();
   $group_manager->setContextLimit($environment->getCurrentContextID());
   $group_manager->select();
   $group_list = $group_manager->get();
   $view->setSelectedGroup($selgroup);
   $view->setAvailableGroups($group_list);
}
if($context_item->withRubric(CS_TOPIC_TYPE)){
   // Get available topics
   $topic_manager = $environment->getTopicManager();
   $topic_manager->resetLimits();
   $topic_manager->setContextLimit($environment->getCurrentContextID());
   $topic_manager->select();
   $topic_list = $topic_manager->get();
   $view->setSelectedTopic($seltopic);
   $view->setAvailableTopics($topic_list);
}

if($context_item->withRubric(CS_INSTITUTION_TYPE)){
   // Get available institutions
   $institution_manager = $environment->getInstitutionManager();
   $institution_manager->resetLimits();
   $institution_manager->setContextLimit($environment->getCurrentContextID());
   $institution_manager->select();
   $institution_list = $institution_manager->get();
   $view->setSelectedInstitution($selinstitution);
   $view->setAvailableInstitutions($institution_list);
}


// Set data for view
$view->setList($list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setClipboardIDArray($clipboard_id_array);
$view->setActivationLimit($sel_activating_status);



$view->setAvailableBuzzwords($buzzword_list);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);






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
$session->setValue('discussion_clipboard', $clipboard_id_array);
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids', $ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
?>