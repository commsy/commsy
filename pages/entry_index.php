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

$room_id_array = array();
$grouproom_list = $current_user_item->getRelatedGroupList();
if ( isset($grouproom_list) and $grouproom_list->isNotEmpty()) {
   $grouproom_list->reverse();
   $grouproom_item = $grouproom_list->getFirst();
   while ($grouproom_item) {
      $project_room_id = $grouproom_item->getLinkedProjectItemID();
      if ( in_array($project_room_id,$room_id_array) ) {
         $room_id_array_temp = array();
         foreach ($room_id_array as $value) {
            $room_id_array_temp[] = $value;
            if ( $value == $project_room_id) {
                $room_id_array_temp[] = $grouproom_item->getItemID();
            }
         }
         $room_id_array = $room_id_array_temp;
      }
      $grouproom_item = $grouproom_list->getNext();
   }
}
$project_list = $current_user_item->getRelatedProjectList();
if ( isset($project_list) and $project_list->isNotEmpty()) {
   $project_item = $project_list->getFirst();
   while ($project_item) {
       $room_id_array[] = $project_item->getItemID();
       $project_item = $project_list->getNext();
   }
}
$community_list = $current_user_item->getRelatedcommunityList();
if ( isset($community_list) and $community_list->isNotEmpty()) {
   $community_item = $community_list->getFirst();
   while ($community_item) {
       $room_id_array[] = $community_item->getItemID();
       $community_item = $community_list->getNext();
   }
}
$room_id_array_without_privateroom = $room_id_array;
$room_id_array[] = $current_context->getItemID();


// Find out what to do
if ( isset($_POST['option']) ) {
   $command = $_POST['option'];
}elseif ( isset($_GET['option']) ) {
   $command = $_GET['option'];
} else {
   $command = '';
}

if ( isset($_GET['selbuzzword']) and $_GET['selbuzzword'] !='-2') {
   $selbuzzword = $_GET['selbuzzword'];
} else {
   $selbuzzword = 0;
}

if ( isset($_GET['selmatrix']) and $_GET['selmatrix'] !='-2') {
   $selmatrix = $_GET['selmatrix'];
} else {
   $selmatrix = 0;
}

if ( isset($_GET['sellist']) and $_GET['sellist'] !='-2') {
   $sellist = $_GET['sellist'];
} else {
   $sellist = 0;
}

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

if ( isset($_GET['delete_list'])) {
   $deletelist = $_GET['delete_list'];
} else {
   $deletelist = 0;
}

if ( isset($_GET['search'])) {
   $searchtext = $_GET['search'];
} else {
   $searchtext = '';
}

if ( isset($_GET['delete_item'])) {
   $deleteitem = $_GET['delete_item'];
} else {
   $deleteitem = 0;
}

if ( isset($_GET['interval'])) {
   $interval = $_GET['interval'];
} else {
   $interval = 20;
}

if ( isset($_GET['pos'])) {
   $pos = $_GET['pos'];
} else {
   $pos = 0;
}

if ( isOption($command, $translator->getMessage('PRIVATEROOM_MY_LISTS_BOX_NEW_ENTRY_BUTTON')) and isset($_POST['new_list']) and !empty($_POST['new_list'])) {
   $mylist_manager = $environment->getMylistManager();
   $my_list_item = $mylist_manager->getNewItem();
   $my_list_item->setLabelType('mylist');
   $my_list_item->setName($_POST['new_list']);
   $my_list_item->setContextID($environment->getCurrentContextID());
   $user = $environment->getCurrentUserItem();
   $my_list_item->setCreatorItem($user);
   $my_list_item->setCreationDate(getCurrentDateTimeInMySQL());
   $my_list_item->save();
   $params = array();
   redirect($environment->getCurrentContextID(),CS_ENTRY_TYPE, 'index',$params);
}

if ( isOption($command, $translator->getMessage('PRIVATEROOM_MATRIX_SAVE_BUTTON'))) {
   $matrix_manager = $environment->getMatrixManager();
   $matrix_manager->resetLimits();
   $matrix_manager->setContextLimit($environment->getCurrentContextID());
   $matrix_manager->setColumnLimit();
   $matrix_manager->select();
   $matrix_column_list = $matrix_manager->get();
   $matrix_item = $matrix_column_list->getFirst();
   while($matrix_item){
      $id = $matrix_item->getItemID();
      if (!isset($_POST['matrix_'.$id])){
         $matrix_item->delete();
      }
      $matrix_item = $matrix_column_list->getNext();
   }
   if (isset($_POST['new_matrix_column']) and !empty($_POST['new_matrix_column']) and $_POST['new_matrix_column'] != $translator->getMessage('PRIVATEROOM_MATRIX_NEW_COLUMN_ENTRY')){
      $matrix_item = $matrix_manager->getNewItem();
      $matrix_item->setLabelType('matrix');
      $matrix_item->setName($_POST['new_matrix_column']);
      $matrix_item->setIsColumn();
      $matrix_item->setContextID($environment->getCurrentContextID());
      $user = $environment->getCurrentUserItem();
      $matrix_item->setCreatorItem($user);
      $matrix_item->setCreationDate(getCurrentDateTimeInMySQL());
      $matrix_item->save();
   }
   $matrix_manager->resetLimits();
   $matrix_manager->setContextLimit($environment->getCurrentContextID());
   $matrix_manager->setRowLimit();
   $matrix_manager->select();
   $matrix_row_list = $matrix_manager->get();
   $matrix_item = $matrix_row_list->getFirst();
   while($matrix_item){
      $id = $matrix_item->getItemID();
      if (!isset($_POST['matrix_'.$id])){
         $matrix_item->delete();
      }
      $matrix_item = $matrix_row_list->getNext();
   }
   if (isset($_POST['new_matrix_row']) and !empty($_POST['new_matrix_row']) and $_POST['new_matrix_row'] != $translator->getMessage('PRIVATEROOM_MATRIX_NEW_ROW_ENTRY')){
      $matrix_item = $matrix_manager->getNewItem();
      $matrix_item->setLabelType('matrix');
      $matrix_item->setName($_POST['new_matrix_row']);
      $matrix_item->setIsRow();
      $matrix_item->setContextID($environment->getCurrentContextID());
      $user = $environment->getCurrentUserItem();
      $matrix_item->setCreatorItem($user);
      $matrix_item->setCreationDate(getCurrentDateTimeInMySQL());
      $matrix_item->save();
   }
   $params = array();
   redirect($environment->getCurrentContextID(),CS_ENTRY_TYPE, 'index',$params);
}

if ( !empty($deletelist) ) {
   $mylist_manager = $environment->getMylistManager();
   $my_list_item = $mylist_manager->getItem($deletelist);
   $my_list_item->delete();
   $params = $environment->getCurrentParameterArray();
   unset($params['delete_list']);
   redirect($environment->getCurrentContextID(),CS_ENTRY_TYPE, 'index',$params);
}

if ( !empty($deleteitem) ) {
   $from_id = '';
   if ( !empty($sellist) ) {
      $from_id = $sellist;
   }if ( !empty($selbuzzword) ) {
      $from_id = $selbuzzword;
   }
   if (!empty($from_id)){
      $link_manager = $environment->getLinkManager();
      $link_manager->deleteLink($deleteitem,$from_id);
   }
   if ( !empty($selmatrix) ) {
      $temp_matrix_array = explode('_', $selmatrix);
      $matrix_manager = $environment->getMatrixManager();
      $matrix_manager->removeItem($deleteitem, $temp_matrix_array[1], $temp_matrix_array[0]);
   }
   $params = $environment->getCurrentParameterArray();
   unset($params['delete_item']);
   redirect($environment->getCurrentContextID(),CS_ENTRY_TYPE, 'index',$params);
}

/*
if (isset($_GET['back_to_search']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array')){
   $campus_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
   $params['search'] = $campus_search_parameter_array['search'];
   $params['selrestriction'] = $campus_search_parameter_array['selrestriction'];
   $params['selrubric'] = $campus_search_parameter_array['selrubric'];
   $params['selbuzzword'] = $campus_search_parameter_array['selbuzzword'];
   $params['seltag_array'] = $campus_search_parameter_array['seltag_array'];
   $params['selfiles'] = $campus_search_parameter_array['selfiles'];
   $params['interval'] = $campus_search_parameter_array['interval'];
   $params['sel_activating_status'] = $campus_search_parameter_array['sel_activating_status'];
   $sel_array = $campus_search_parameter_array['sel_array'];
   foreach($sel_array as $key => $value){
      $params['sel'.$key] = $value;
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids');
   redirect($environment->getCurrentContextID(),'campus_search', 'index', $params);
}

$file_rubric_array = array();
$file_rubric_array[] = CS_DISCUSSION_TYPE;
$file_rubric_array[] = CS_MATERIAL_TYPE;
$file_rubric_array[] = CS_DATE_TYPE;
$file_rubric_array[] = CS_ANNOUNCEMENT_TYPE;
$file_rubric_array[] = CS_TODO_TYPE;

include_once('classes/cs_list.php');


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

// Search / Select Area
if ( isset($_GET['option']) and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $search = '';
   $selrubric = 'all';
   $selrestriction = 'all';
   $seltopic = '';
   $last_selected_tag = '';
   $seltag_array = array();
} else {


   // Find current search text
   if ( isset($_POST['search']) ) {
      $search = $_POST['search'];
      $from = 1;
   } elseif ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   }  else {
      $search = '';
   }

   // Find current search text
   if ( isset($_POST['selrubric']) ) {
      $selrubric = $_POST['selrubric'];
      $from = 1;
   } elseif ( isset($_GET['selrubric']) ) {
      $selrubric = $_GET['selrubric'];
   }  else {
      $selrubric = '';
   }
   if ($selrubric == 'campus_search'){
      $selrubric ='all';
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

   // Find current restriction selection
   if ( isset($_POST['selrestriction']) ) {
      if ($_POST['selrestriction'] == 1){
         $selrestriction = 'title';
      }elseif($_POST['selrestriction'] == 2){
        $selrestriction = 'author';
      }else{
        $selrestriction = 'all';
      }
      $from = 1;
   } elseif ( isset($_GET['selrestriction']) ) {
      if ($_GET['selrestriction'] == 1){
        $selrestriction = 'title';
      }elseif($_GET['selrestriction'] == 2){
        $selrestriction = 'author';
      }else{
        $selrestriction = 'all';
      }
   }  else {
      $selrestriction = 'all';
   }
   // Find current only files selection
   if ( isset($_POST['only_files']) ) {
      $selfiles = $_POST['only_files'];
      $from = 1;
   } elseif ( isset($_GET['only_files']) ) {
      $selfiles = $_GET['only_files'];
   }  else {
      $selfiles = '';
   }
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

*/
$search_list = new cs_list();
$campus_search_ids = array();
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$view = $class_factory->getClass(ENTRY_INDEX_VIEW,$params);
unset($params);
/*
$context_item = $environment->getCurrentContextItem();
$current_room_modules = $context_item->getHomeConf();
if ( !empty($current_room_modules) ){
   $room_modules = explode(',',$current_room_modules);
}
$first = '';
$i=1;
$rubric_array = array();
foreach ( $room_modules as $module ) {
   $link_name = explode('_', $module);
   if ( $link_name[1] != 'none' ) {
      if ( !($environment->inPrivateRoom() and $link_name =='user')
           and ( empty($selfiles)
                 or in_array($link_name[0],$file_rubric_array)
               )
         ) {
         if ( (empty($selbuzzword)
                 and empty($selfiles)
                 and empty($last_selected_tag)
              )
             or ($link_name[0] != CS_USER_TYPE
                 and $link_name[0] != CS_TOPIC_TYPE
                 and $link_name[0] != CS_GROUP_TYPE
                 and $link_name[0] != CS_INSTITUTION_TYPE)
         ){
            $rubric_array[] = $link_name[0];
         }

      }
   }
}
if ( !empty($selrubric) and $selrubric != 'all' and $selrubric != 'campus_search') {
   $rubric_array = array();
   $rubric_array[] = $selrubric;
}

// Find current sel_activating_status selection
if ( isset($_GET['selactivatingstatus']) and $_GET['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_GET['selactivatingstatus'];
} else {
   $sel_activating_status = 2;
}

// Find current search text
if ( isset($_GET['attribute_limit']) ) {
   $attribute_limit = $_GET['attribute_limit'];
   switch( $attribute_limit  ){
     case 1 :
         $attribute_limit = 'title';
         break;
     case 2 :
         $attribute_limit = 'author';
         break;
     case 3 :
         $attribute_limit = 'file';
         break;
   }
} else {
   $attribute_limit = '';
}

// Get available buzzwords
$buzzword_manager = $environment->getLabelManager();
$buzzword_manager->resetLimits();
$buzzword_manager->setContextLimit($environment->getCurrentContextID());
$buzzword_manager->setTypeLimit('buzzword');
$buzzword_manager->setGetCountLinks();
$buzzword_manager->select();
$buzzword_list = $buzzword_manager->get();
$count_all = 0;
*/
/*Durchführung möglicher Einschränkungen*/
/*foreach($sel_array as $rubric => $value){
   $label_manager = $environment->getManager($rubric);
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $temp_rubric_list = clone $rubric_list;
   $view->setAvailableRubric($rubric,$temp_rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}*/

// Get data from database

$user_id_array = array();
$user_id_array[]= $current_user->getItemID();
$privatroom_id_array = array();
$privatroom_id_array[]= $current_context->getItemID();

$item_manager = $environment->getItemManager();
$item_manager->setOrderLimit(true);
if (!empty($sellist) and $sellist != 'new'){
   $item_manager->setListLimit($sellist);
}
if (!empty($selbuzzword)){
   $item_manager->setBuzzwordLimit($selbuzzword);
}
if (!empty($selmatrix)){
   $item_manager->setMatrixLimit($selmatrix);
}
if (!empty($searchtext)){
   $item_manager->setSearchLimit($searchtext);
}
if (!empty($last_selected_tag)){
   $item_manager->setTagLimit($last_selected_tag);
}
$new_entry_list = $item_manager->getAllPrivateRoomEntriesOfUserList($privatroom_id_array,$user_id_array);
$search_list->addList($new_entry_list);

// ToDo: Nur Einträge in der Liste belassen, die auch angezeigt werden -> sonst gibt es leere Seiten über die geblättert wird!
$rubric_array = array();
$rubric_array[] = CS_ANNOUNCEMENT_TYPE;
$rubric_array[] = CS_DISCUSSION_TYPE;
$rubric_array[] = CS_DATE_TYPE;
$rubric_array[] = CS_MATERIAL_TYPE;
$rubric_array[] = CS_TODO_TYPE;

$sort_list = new cs_list();
$sort_item = $new_entry_list->getFirst();
while($sort_item){
   if(in_array($sort_item->getItemType(), $rubric_array)){
      $sort_list->add($sort_item);
   }
   $sort_item = $new_entry_list->getNext();
}
$new_entry_list = $sort_list;

$browse_prev = true;
$browse_next = true;
$temp_list = new cs_list();
if($interval != 'all'){
   $temp_start = $pos * $interval;
   $temp_index = 0;
   $temp_item = $new_entry_list->getFirst();
   while($temp_item){
      if(($temp_index >= $temp_start) and ($temp_index < ($temp_start + $interval))){
         $temp_list->add($temp_item);
      }
      $temp_index++;
      $temp_item = $new_entry_list->getNext();
   }
   if($pos == 0){
      $browse_prev = false;
   }
   if(($temp_start + $interval) >= $temp_index){
      $browse_next = false;
   }
   if($temp_index % $interval == 0){
      $max_pos = ($temp_index / $interval) - 1;
   } else {
      $max_pos = (($temp_index - ($temp_index % $interval)) / $interval);
   }
} else {
   $temp_list = $new_entry_list;
   $browse_prev = false;
   $browse_next = false;
   $pos = 0;
   $max_pos = 0;
}

$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = $current_context->isOpen();
$my_entries_view = $class_factory->getClass(PRIVATEROOM_HOME_NEW_ENTRIES_VIEW,$params);
$my_entries_view->setList($new_entry_list);
$my_entries_view->setList($temp_list);
unset($params);

// Set data for view
#$sublist = $search_list->getSubList($from-1,$interval);
#$view->setList($new_entry_list);
$view->setList($temp_list);
$view->setSelectedMyList($sellist);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedMatrix($selmatrix);
$view->setSelectedTagArray($seltag_array);
$view->setSelectedTag($last_selected_tag);
$view->setSearchText($searchtext);
$view->setInterval($interval);
$view->setPos($pos);
$view->setMaxPos($max_pos);
$view->setBrowsePrev($browse_prev);
$view->setBrowseNext($browse_next);
/*$view->setCountAllShown(count($campus_search_ids));
$view->setCountAll($count_all);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSearchText($search);
$view->setSelectedRestriction($selrestriction);
$view->setSelectedFile($selfiles);
$view->setAvailableBuzzwords($buzzword_list);
$view->setChoosenRubric($selrubric);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setActivationLimit($sel_activating_status);*/

// Add list view to page
$page->add($view);
/*
// Safe information in session for later use
$campus_search_parameter_array = array();
$campus_search_parameter_array['search'] = $search;
$campus_search_parameter_array['selrestriction'] = $selrestriction;
$campus_search_parameter_array['selrubric'] = $selrubric;
$campus_search_parameter_array['selbuzzword'] = $selbuzzword;
$campus_search_parameter_array['seltag_array'] = $seltag_array;
$campus_search_parameter_array['selfiles'] = $selfiles;
$campus_search_parameter_array['sel_array'] = $sel_array;
$campus_search_parameter_array['interval'] = $interval;
$campus_search_parameter_array['sel_activating_status'] = $sel_activating_status;

$ftsearch_manager = $environment->getFTSearchManager();
if ($ftsearch_manager->getSearchStatus()) {
   // get fids from cs_ftsearch_manager
   $ft_file_ids = $ftsearch_manager->getFileIDs();
   if ( !empty($ft_file_ids) ) {
      $campus_search_parameter_array['file_id_array'] = $ft_file_ids;
   }
}
unset($ftsearch_manager);

$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array', $campus_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids', $campus_search_ids);
*/
?>