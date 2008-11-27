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

// Find current page   mode. Modes are:
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


$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();
$seldisplay_mod = $context_item->getDatesPresentationStatus();
if ( isset($_GET['seldisplay_mode']) ) {
   $seldisplay_mode = $_GET['seldisplay_mode'];
   $session->setValue($environment->getCurrentContextID().'_dates_seldisplay_mode',$_GET['seldisplay_mode']);
} elseif ( !empty($_POST['seldisplay_mode']) ) {
   $session->setValue($environment->getCurrentContextID().'_dates_seldisplay_mode',$_POST['seldisplay_mode']);
   $seldisplay_mode = $_POST['seldisplay_mode'];
} elseif ( $session->issetValue($environment->getCurrentContextID().'_dates_seldisplay_mode') ) {
   $seldisplay_mode = $session->getValue($environment->getCurrentContextID().'_dates_seldisplay_mode');
} else {
   $seldisplay_mode = $context_item->getDatesPresentationStatus();
}


$month = '';
$year = '';

if ($seldisplay_mode == 'calendar'){

   // Initialisierung der benötigten Werte
   $day = date("d");
   $year = date("Y");
   $month = date("Ymd");
   $d_time = mktime(3,0,0,date("m"),date("d"),date("Y") );
   $wday = date("w",$d_time );
   $week = mktime (3,0,0,date("m"),date("d") - ($wday - 1),date("Y"));
   $presentation_mode = '1';
   $old_month ='';
   $old_year ='';
   $old_week ='';

   //Belegung der Variablen mit aktuellen Werten
   if (isset($_GET['year'])) {
      $year = $_GET['year'];
   } elseif (isset($_POST['year'])) {
      $year = $_POST['year'];
   }
   if (isset($_GET['month'])) {
      $month = $_GET['month'];
   } elseif (isset($_POST['month'])) {
      $month = $_POST['month'];
   }
   if (isset($_GET['week']) and !empty($_GET['week'])){
      $week = $_GET['week'];
   }elseif (isset($_POST['week'])) {
      $week = $_POST['week'];
   }
   if(isset($_GET['presentation_mode']) and !empty($_GET['presentation_mode'])){
      $presentation_mode = $_GET['presentation_mode'];
   }else{
      $presentation_mode = '1';
   }
   if ($session->issetValue($environment->getCurrentContextID().'_month')){
      $old_month = $session->getValue($environment->getCurrentContextID().'_month');
   }else{
      $old_month = $month;
   }
   if ($session->issetValue($environment->getCurrentContextID().'_year')){
      $old_year = $session->getValue($environment->getCurrentContextID().'_year');
   }else{
      $old_year = $year;
   }
   if ($session->issetValue($environment->getCurrentContextID().'_week')){
      $old_week = $session->getValue($environment->getCurrentContextID().'_week');
   }else{
      $old_week = $week;
   }
   if ($session->issetValue($environment->getCurrentContextID().'_presentation_mode')){
      $old_presentation_mode = $session->getValue($environment->getCurrentContextID().'_presentation_mode');
   }else{
      $old_presentation_mode = $presentation_mode;
   }
   //Berechnung der neuen Werte
   //Beim Blättern der Einträge
   if (!isset($_GET['year']) or !isset($_GET['month']) or !isset($_GET['week'])){
      if(isset($_GET['week']) and $old_week != $week){
         $month = date("Ymd", $week);
         $year = date("Y", $week);
         $presentation_mode = '1';
      }
      if(isset($_GET['month']) and $old_month != $month){
         $year = substr($month,0,4);
         $real_month = substr($month,4,2);
         $d_time = mktime(3,0,0,$real_month,'1',$year);
         $wday = date("w",$d_time);
         $week = mktime(3,0,0,$real_month,1 - ($wday - 1),$year);
         $presentation_mode = '2';
      }
      if (isset($_GET['year']) and $old_year != $year){
         $real_month = substr($old_month,4,2);
         $real_day = substr($old_month,6,2);
         $d_time = mktime(3,0,0,$real_month,$real_day,$year);
         $month = date("Ymd",$d_time);
         $wday = date("w",$d_time);
         $week = mktime(3,0,0,$real_month,$real_day - ($wday - 1),$year);
      }
   // Beim Editieren oder der Auswahl der Selectboxen
   }elseif (isset($_GET['year']) and isset($_GET['month']) and isset($_GET['week'])){
      $history = $session->getValue('history');
      // Beim Editieren
      if (isset($history['0']['function']) and $history['0']['function'] =='edit'){
         $month = $_GET['month'];
         $year = $_GET['year'];
         $real_month = substr($month,4,2);
         $day = substr($month,6,2);
         $d_time = mktime(3,0,0,$real_month,$day,$year);
         $wday = date("w",$d_time);
         if (empty($wday)){
            $wday = 7;
         }
         $week = mktime(3,0,0,$real_month,$day - ($wday - 1),$year);
         if (isset($_GET['presentation_mode'])){
            $presentation_mode = $_GET['presentation_mode'];
         }
      // Bei der Auswahl aus Selectboxen
      }else{
         if (isset($_GET['presentation_mode'])){
            $presentation_mode = $_GET['presentation_mode'];
         }else{
            $presentation_mode = '1';
         }
         $temp_year = $year;
         $temp_month = $month;
         $temp_week = $week;
         if(isset($_GET['week']) and $old_week != $week){
            $temp_month = date("Ymd", $week);
            $temp_year = date("Y", $week);
            $presentation_mode = '1';
         }elseif(isset($_GET['month']) and $old_month != $month){
            $temp_year = substr($month,0,4);
            $real_month = substr($month,4,2);
            $d_time = mktime(3,0,0,$real_month,'1',$temp_year);
            $wday = date("w",$d_time);
            $temp_week = mktime(3,0,0,$real_month,1 - ($wday - 1),$temp_year);
            $presentation_mode = '2';
         }elseif (isset($_GET['year']) and $old_year != $year){
            $real_month = substr($old_month,4,2);
            $real_day = substr($old_month,6,2);
            $d_time = mktime(3,0,0,$real_month,$real_day,$year);
            $temp_month = date("Ymd",$d_time);
            $wday = date("w",$d_time);
            $temp_week = mktime(3,0,0,$real_month,$real_day - ($wday - 1),$year);
         }
         $month = $temp_month;
         $year = $temp_year;
         $week = $temp_week;
      }
   }
   if ($old_presentation_mode != $presentation_mode){
   }
   $session->setValue($environment->getCurrentContextID().'_month', $month);
   $session->setValue($environment->getCurrentContextID().'_year', $year);
   $session->setValue($environment->getCurrentContextID().'_week', $week);
   $session->setValue($environment->getCurrentContextID().'_presentation_mode', $presentation_mode);
} else{
   #$session->unsetValue($environment->getCurrentContextID().'_dates_seldisplay_mode');
}

// Find clipboard id array
if ( $session->issetValue('date_clipboard') ) {
   $clipboard_id_array = $session->getValue('date_clipboard');
} else {
   $clipboard_id_array = array();
}

// Copy to clipboard
if ( isset($_GET['add_to_date_clipboard']) ) {
   if ( !in_array($_GET['add_to_date_clipboard'], $clipboard_id_array) ) {
      $clipboard_id_array[] = $_GET['add_to_date_clipboard'];
   }
}



// Handle attaching
if ( $mode == 'formattach' or $mode == 'detailattach' ) {
   $attach_type = CS_DATE_TYPE;
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
   $sort = 'time_rev';
}

// Search / Select Area
if ( isset($_GET['option'])
     and isOption($_GET['option'],getMessage('COMMON_RESET')) ) {
   $from = 1;
   $search = '';
   $selgroup = '';
   $seltopic = '';
   $selinstitution = '';
   $last_selected_tag = '';
   $seltag_array = array();
   $sel_activating_status = '';
} else {

   // Find current search text
   if ( isset($_GET['search']) ) {
      $search = $_GET['search'];
   } else {
      $search = '';
   }
   $sel_activating_status = '';

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



   // Find current status selection
   if ( isset($_GET['selstatus'])  and $_GET['selstatus'] !='-2') {
      $selstatus = $_GET['selstatus'];
   } else {
      if ($seldisplay_mode=='calendar'  or $mode == 'formattach' or $mode == 'detailattach' or $environment->inPrivateRoom()){
         $selstatus = 2;
      }else{
         $selstatus = 3;
      }
   }
}

//***********************************

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
//************************************

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
if ( isOption($delete_command, getMessage('COMMON_CANCEL_BUTTON')) ) {
   $params = $environment->getCurrentParameterArray();
   redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
   $manager = $environment->getDatesManager();
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
   redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
            $dates_manager = $environment->getDateManager();
            $noticed_manager = $environment->getNoticedManager();
            foreach ($selected_ids as $id) {
               $dates_item = $dates_manager->getItem($id);
               $version_id = $dates_item->getVersionID();
               $noticed_manager->markNoticed($id, $version_id );
               $annotation_list =$dates_item->getAnnotationList();
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
            if( $user->isModerator() or $environment->inPrivateRoom() ){
                $session->setValue('cid'.$environment->getCurrentContextID().
                                               '_'.$environment->getCurrentModule().
                                               '_deleted_ids', $selected_ids);
               $params = $environment->getCurrentParameterArray();
               $params['mode'] = 'list_actions';
               $page->addDeleteBox(curl($environment->getCurrentContextID(),CS_DATE_TYPE,'index',$params),'index',$selected_ids);
               unset($params);
            }
            break;
         default:
            $params = $environment->getCurrentParameterArray();
            unset($params['mode']);
            redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
      }
      if ($_POST['index_view_action'] != '3'){
         $selected_ids = array();
         $session->unsetValue('cid'.$environment->getCurrentContextID().
                              '_'.$environment->getCurrentModule().
                              '_selected_ids');
      }
   } // end if (perform list actions)






// Get data from database
$dates_manager = $environment->getDatesManager();
$dates_manager->setContextLimit($environment->getCurrentContextID());
if ($seldisplay_mode == 'calendar'  and !($mode == 'formattach' or $mode == 'detailattach') ){
   $dates_manager->setDateModeLimit(2);
   $dates_manager->setYearLimit($year);
   if (!empty($presentation_mode) and $presentation_mode =='2'){
      $real_month = substr($month,4,2);
      $first_char = substr($real_month,0,1);
      if ($first_char == '0'){
         $real_month = substr($real_month,1,2);
      }
      $dates_manager->setMonthLimit($real_month);
   }else{
      $real_month = substr($month,4,2);
      $first_char = substr($real_month,0,1);
      if ($first_char == '0'){
         $real_month = substr($real_month,1,2);
      }
      $dates_manager->setMonthLimit2($real_month);
   }
   $count_all = $dates_manager->getCountAll();
   $dates_manager->resetLimits();
   $dates_manager->setSortOrder('time');
} else {
   $dates_manager->setDateModeLimit(2);
   $count_all = $dates_manager->getCountAll();
}
if ( $sel_activating_status == 2 ) {
   $dates_manager->showNoNotActivatedEntries();
}

if ( !empty($ref_iid) and $mode == 'attached' ){
   $dates_manager->setRefIDLimit($ref_iid);
}
if ( !empty($ref_user) and $mode == 'attached' ){
   $dates_manager->setRefUserLimit($ref_user);
}
if ( !empty($sort) and ($seldisplay_mode!='calendar' or $mode == 'formattach' or $mode == 'detailattach') ) {
   $dates_manager->setSortOrder($sort);
}
if ( !empty($search) ) {
   $dates_manager->setSearchLimit($search);
}
if ( !empty($selgroup) ) {
   $dates_manager->setGroupLimit($selgroup);
}
if ( !empty($seltopic) ) {
   $dates_manager->setTopicLimit($seltopic);
}
if ( !empty($selstatus) ) {
   $dates_manager->setDateModeLimit($selstatus);
}
if ( !empty($selbuzzword) ) {
   $dates_manager->setBuzzwordLimit($selbuzzword);
}
if ( !empty($last_selected_tag) ){
   $dates_manager->setTagLimit($last_selected_tag);
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
// Prepare view object
$context_item = $environment->getCurrentContextItem();
if ($seldisplay_mode == 'calendar' and !($mode == 'formattach' or $mode == 'detailattach') ){
   $with_modifying_actions = false;
   if ( $mode != 'detailattach' and $context_item->isOpen() ) {
      $with_modifying_actions = true;
   }
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $view = $class_factory->getClass(DATE_CALENDAR_INDEX_VIEW,$params);
   unset($params);
   $view->setMonth($month);
   $view->setYear($year);
   $view->setWeek($week);
   $view->setPresentationMode($presentation_mode);
} else {
   $with_modifying_actions = false;
   if ( $mode != 'detailattach' and $context_item->isOpen() ) {
      $with_modifying_actions = true;
   }
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $with_modifying_actions;
   $view = $class_factory->getClass(DATE_INDEX_VIEW,$params);
   unset($params);
}
foreach($sel_array as $rubric => $value){
   if (!empty($value)){
      $dates_manager->setRubricLimit($rubric,$value);
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


$ids = $dates_manager->getIDArray();       // returns an array of item ids
$count_all_shown = count($ids);



if ($seldisplay_mode=='calendar' and !($mode == 'formattach' or $mode == 'detailattach') ){
   if (!empty($year)) {
      $dates_manager->setYearLimit($year);
   }
   if (!empty($month)) {
      if (!empty($presentation_mode) and $presentation_mode =='2'){
         $real_month = substr($month,4,2);
         $first_char = substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit($real_month);
      }else{
         $real_month = substr($month,4,2);
         $first_char = substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit2($real_month);
      }
   }
   $dates_manager->setDateModeLimit($selstatus);
}


if ( $interval > 0 ) {
   $dates_manager->setIntervalLimit($from-1,$interval);
}
if ($seldisplay_mode=='calendar' and !($mode == 'formattach' or $mode == 'detailattach') ){
   $dates_manager->selectDistinct();
} else {
   $dates_manager->select();
}
$list = $dates_manager->get();        // returns a cs_list of dates_items
if ($seldisplay_mode=='calendar' and !($mode == 'formattach' or $mode == 'detailattach') ){
   $count_all_shown = $list->getCount();
}

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


// Set data for view
$view->setList($list);
$view->setCountAll($count_all);
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setSelectedStatus($selstatus);
$view->setDisplayMode($seldisplay_mode);
$view->setClipboardIDArray($clipboard_id_array);
$view->setAvailableBuzzwords($buzzword_list);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setAdditionalSelect();
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


// Add list view to page
$page->add($view);

// Safe information in session for later use
$session->setValue('date_clipboard', $clipboard_id_array);
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_dates_index_ids', $ids);
?>