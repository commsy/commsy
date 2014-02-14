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


$new_private_room = $environment->inConfigArray('c_use_new_private_room',$environment->getCurrentContextID());
if ($new_private_room){

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

// Get the translator object
$translator = $environment->getTranslationObject();

if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sort'] = $index_search_parameter_array['sort'];
   $params['selbuzzword'] = $index_search_parameter_array['selbuzzword'];
   $params['seltag_array'] = $index_search_parameter_array['seltag_array'];
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['selcolor'] = $index_search_parameter_array['selcolor'];
   $params['sel_activating_status'] = $index_search_parameter_array['sel_activating_status'];
   $sel_array = $index_search_parameter_array['sel_array'];
   foreach($sel_array as $key => $value){
      $params['sel'.$key] = $value;
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
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

if ( $seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') {

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
      if ( $environment->inPrivateRoom() ) {
         $current_context_item = $environment->getCurrentContextItem();
         $saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
         if ( $presentation_mode == 1 ) {
            $current_date_display_mode = 'calendar';
         } else {
            $current_date_display_mode = 'calendar_month';
         }
         if ( $saved_date_display_mode != $current_date_display_mode ) {
            $current_context_item->setDatesPresentationStatus($current_date_display_mode);
            $current_context_item->save();
         }
         unset($current_context_item);
      }
   }elseif($seldisplay_mode == 'calendar_month'){
      $presentation_mode = '2';
      if ( $environment->inPrivateRoom() ) {
         $current_context_item = $environment->getCurrentContextItem();
         $saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
         if ( $saved_date_display_mode != 'calendar_month' ) {
            $current_context_item->setDatesPresentationStatus('calendar_month');
            $current_context_item->save();
         }
         unset($current_context_item);
      }
   }else{
      $presentation_mode = '1';
      if ( $environment->inPrivateRoom() ) {
         $current_context_item = $environment->getCurrentContextItem();
         $saved_date_display_mode = $current_context_item->getDatesPresentationStatus();
         if ( !empty($saved_date_display_mode)
              and $saved_date_display_mode == 'calendar'
            ) {
            $presentation_mode = '1';
         } else {
            $presentation_mode = '2';
         }
         unset($current_context_item);
      }
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
         $year = mb_substr($month,0,4);
         $real_month = mb_substr($month,4,2);
         $d_time = mktime(3,0,0,$real_month,'1',$year);
         $wday = date("w",$d_time);
         $week = mktime(3,0,0,$real_month,1 - ($wday - 1),$year);
         $presentation_mode = '2';
      }
      if (isset($_GET['year']) and $old_year != $year){
         $real_month = mb_substr($old_month,4,2);
         $real_day = mb_substr($old_month,6,2);
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
         $real_month = mb_substr($month,4,2);
         $day = mb_substr($month,6,2);
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
            $temp_year = mb_substr($month,0,4);
            $real_month = mb_substr($month,4,2);
            $d_time = mktime(3,0,0,$real_month,'1',$temp_year);
            $wday = date("w",$d_time);
            $temp_week = mktime(3,0,0,$real_month,1 - ($wday - 1),$temp_year);
            $presentation_mode = '2';
         }elseif (isset($_GET['year']) and $old_year != $year){
            $real_month = mb_substr($old_month,4,2);
            $real_day = mb_substr($old_month,6,2);
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

// Find current sort key
if ( isset($_GET['selcolor']) and $_GET['selcolor'] !='-2') {
   $sel_color = $_GET['selcolor'];
} else {
   $sel_color = '';
}

$sel_room_default = false;
$room_save_selection = false;
if ( isset($_GET['selroom'])
     and $_GET['selroom'] != '-2'
     and $_GET['selroom'] != '2'
   ) {
   $sel_room = $_GET['selroom'];
   // save selection
   if ( $context_item->isPrivateRoom() ) {
      $date_sel_room = $context_item->getRubrikSelection(CS_DATE_TYPE,'room');
      if ( $date_sel_room != $sel_room ) {
         $context_item->setRubrikSelection(CS_DATE_TYPE,'room',$sel_room);
         $room_save_selection = true;
      }
   }
} elseif ( !empty($_GET['selroom'])
           and $_GET['selroom'] == '2'
         ) {
   $sel_room = '2';
   if ( $context_item->isPrivateRoom() ) {
      $date_sel_room = $context_item->getRubrikSelection(CS_DATE_TYPE,'room');
      if ( $date_sel_room != $sel_room ) {
         $context_item->setRubrikSelection(CS_DATE_TYPE,'room',$sel_room);
         $room_save_selection = true;
      }
   }
} else {
   $sel_room = '2';
   if ( $environment->inPrivateRoom() ) {
      $date_sel_room = $context_item->getRubrikSelection(CS_DATE_TYPE,'room');
      if ( !empty($date_sel_room) ) {
         $sel_room = $date_sel_room;
      } else {
         $sel_room = $environment->getCurrentContextID();
      }
   }
}

if ( isset($_GET['selassignment'])
     and $_GET['selassignment'] != '-2'
     and $_GET['selassignment'] != '3'
   ) {
   $sel_assignment = $_GET['selassignment'];
   // save selection
   if ( $context_item->isPrivateRoom() ) {
      $date_sel_assignment = $context_item->getRubrikSelection(CS_DATE_TYPE,'assignment');
      if ( $date_sel_assignment != $sel_assignment ) {
         $context_item->setRubrikSelection(CS_DATE_TYPE,'assignment',$sel_assignment);
         $room_save_selection = true;
      }
   }
} elseif ( !empty($_GET['selassignment'])
           and $_GET['selassignment'] == '3'
         ) {
   $sel_assignment = '3';
   if ( $context_item->isPrivateRoom() ) {
      $date_sel_assignment = $context_item->getRubrikSelection(CS_DATE_TYPE,'assignment');
      if ( $date_sel_room != $sel_assignment ) {
         $context_item->setRubrikSelection(CS_DATE_TYPE,'assignment',$sel_assignment);
         $room_save_selection = true;
      }
   }
} else {
   $sel_assignment = '3';
   if ( $environment->inPrivateRoom() ) {
      $date_sel_assignment = $context_item->getRubrikSelection(CS_DATE_TYPE,'assignment');
      if ( !empty($date_sel_assignment) ) {
         $sel_assignment = $date_sel_assignment;
      } else {
         $sel_assignment = 2;
      }
   }
}

// Search / Select Area
if ( isset($_GET['option'])
     and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $from = 1;
   $search = '';
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
   if ( isset($_GET['selstatus'])
        and $_GET['selstatus'] != '-2'
      ) {
      $selstatus = $_GET['selstatus'];
      // save selection
      if ( $context_item->isPrivateRoom() ) {
         $date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE,'status');
         if ( $date_sel_status != $selstatus ) {
            $context_item->setRubrikSelection(CS_DATE_TYPE,'status',$selstatus);
            $room_save_selection = true;
         }
      }
   } else {
      if ( $seldisplay_mode=='calendar'
           or $seldisplay_mode == 'calendar_month'
           or $mode == 'formattach'
           or $mode == 'detailattach'
           or $environment->inPrivateRoom()
         ) {
         $selstatus = 2;
         if ( $environment->inPrivateRoom() ) {
            $date_sel_status = $context_item->getRubrikSelection(CS_DATE_TYPE,'status');
            if ( !empty($date_sel_status) ) {
               $selstatus = $date_sel_status;
            } else {
               $selstatus = 2;
            }
         }
      }else{
         $selstatus = 3;
      }
   }
}

if ( isset($room_save_selection)
     and $room_save_selection
   ) {
   $context_item->save();
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
         if (($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) or ($link_name[0] == 'user')) {
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
if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   $params = $environment->getCurrentParameterArray();
   redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
               redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
$dates_manager = $environment->getDatesManager();

if ( empty($only_show_array) ) {
   $color_array = $dates_manager->getColorArray();
   $current_context = $environment->getCurrentContextItem();
   if ($current_context->isPrivateRoom()){
      $id_array = array();
      $id_array[] = $environment->getCurrentContextID();
      $dates_manager->setContextArrayLimit($id_array);
      $dates_manager->setDateModeLimit(2);
      $dates_manager->setYearLimit($year);
      if (!empty($presentation_mode) and $presentation_mode =='2'){
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit($real_month);
      }else{
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit2($real_month);
      }
      $count_all = $dates_manager->getCountAll();
      $dates_manager->resetLimits();
      $dates_manager->setSortOrder('time');
   }elseif (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
      $dates_manager->setContextLimit($environment->getCurrentContextID());
      $dates_manager->setDateModeLimit(2);
      $dates_manager->setYearLimit($year);
      if (!empty($presentation_mode) and $presentation_mode =='2'){
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit($real_month);
      }else{
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit2($real_month);
      }
      $count_all = $dates_manager->getCountAll();
      $dates_manager->resetLimits();
      $dates_manager->setSortOrder('time');
   } else {
      $dates_manager->setContextLimit($environment->getCurrentContextID());
      $dates_manager->setDateModeLimit(2);
      $count_all = $dates_manager->getCountAll();
   }
   if ( $sel_activating_status == 2 ) {
      $dates_manager->showNoNotActivatedEntries();
   }

   if ( !empty($sel_color) and $sel_color != 2 ) {
      $dates_manager->setColorLimit('#'.$sel_color);
   }

   if ( !empty($ref_iid) and $mode == 'attached' ){
      $dates_manager->setRefIDLimit($ref_iid);
   }
   if ( !empty($ref_user) and $mode == 'attached' ){
      $dates_manager->setRefUserLimit($ref_user);
   }
   if ( !empty($sort) and ($seldisplay_mode!='calendar' or $seldisplay_mode == 'calendar_month' or $mode == 'formattach' or $mode == 'detailattach') ) {
      $dates_manager->setSortOrder($sort);
   }
   if ( !empty($search) ) {
      $dates_manager->setSearchLimit($search);
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
}

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
if (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
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


if ( $current_context->isPrivateRoom()
     and $environment->getConfiguration('c_use_new_private_room')
   ) {
   $current_user_item = $environment->getCurrentUser();
   $room_id_array = array();

   // get my calendar display configuration
   $configuration = $current_context->getMyCalendarDisplayConfig();
   $configuration_room_dates_limit = array();
   $configuration_room_todo_limit = array();
   foreach($configuration as $entry) {
      $exp_entry = explode('_', $entry);
      if(sizeof($exp_entry) == 2) {
         if($exp_entry[1] == 'dates') {
            $configuration_room_dates_limit[] = $exp_entry[0];
         } else if($exp_entry[1] == 'todo') {
            $configuration_room_todo_limit[] = $exp_entry[0];
         }
      }
   }

   // add privateroom itself
   $room_id_array[] = $current_context->getItemID();

   // add related group rooms
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

   // add related project rooms
   $project_list = $current_user_item->getRelatedProjectList();
   if ( isset($project_list) and $project_list->isNotEmpty()) {
      $project_item = $project_list->getFirst();
      while ($project_item) {
          $room_id_array[] = $project_item->getItemID();
          $project_item = $project_list->getNext();
      }
   }

   // add related community rooms
   $community_list = $current_user_item->getRelatedcommunityList();
   if ( isset($community_list) and $community_list->isNotEmpty()) {
      $community_item = $community_list->getFirst();
      while ($community_item) {
          $room_id_array[] = $community_item->getItemID();
          $community_item = $community_list->getNext();
      }
   }

   // filter room id array
   $temp = array();
   foreach($configuration_room_dates_limit as $limit) {
      if(in_array($limit, $room_id_array)) {
         $temp[] = $limit;
      }
   }
   $temp[] = $current_context->getItemID();
   $dates_room_id_array = $temp;

   if ($sel_room != "2"){
      $room_id = array();
      $room_id[] = $sel_room;
      $dates_manager->setContextArrayLimit($room_id);
   }else{
      $dates_manager->setContextArrayLimit($dates_room_id_array);
   }
   $view->setRoomIDArray($room_id_array);
   $view->setSelectedRoom($sel_room);
   $view->setSelectedAssignment($sel_assignment);

   // todo
   $todo_sel_room = '';
   if ( !empty($_GET[CS_TODO_TYPE.'_selroom'])
        and $_GET[CS_TODO_TYPE.'_selroom'] != '-2'
        and $_GET[CS_TODO_TYPE.'_selroom'] != '2'
      ) {
      $todo_sel_room = $_GET[CS_TODO_TYPE.'_selroom'];
      $room_id_array = array();
      $room_id_array[] = $todo_sel_room;
      $view->setSelectedRoom($todo_sel_room,CS_TODO_TYPE);
      $context_item->setRubrikSelection(CS_TODO_TYPE,'room',$todo_sel_room);
      $context_item->save();
   } elseif ( !empty($_GET[CS_TODO_TYPE.'_selroom'])
              and $_GET[CS_TODO_TYPE.'_selroom'] == '2'
            ) {
      $todo_sel_room = $_GET[CS_TODO_TYPE.'_selroom'];
      $view->setSelectedRoom($todo_sel_room,CS_TODO_TYPE);
      $context_item->setRubrikSelection(CS_TODO_TYPE,'room',$todo_sel_room);
      $context_item->save();
   } elseif ( empty($_GET[CS_TODO_TYPE.'_selroom']) ) {
      $todo_sel_room = $context_item->getRubrikSelection(CS_TODO_TYPE,'room');
      if ( !empty($todo_sel_room)
           and $todo_sel_room != '-2'
           and $todo_sel_room != '2'
         ) {
         $room_id_array = array();
         $room_id_array[] = $todo_sel_room;
         $view->setSelectedRoom($todo_sel_room,CS_TODO_TYPE);
      }
   }

   $todo_sel_status_for_manager = 4;
   if ( isset($_GET[CS_TODO_TYPE.'_selstatus'])
        and $_GET[CS_TODO_TYPE.'_selstatus'] != '-2'
      ) {
      $todo_sel_status = $_GET[CS_TODO_TYPE.'_selstatus'];
      $view->setSelectedStatus($todo_sel_status,CS_TODO_TYPE);
      $context_item->setRubrikSelection(CS_TODO_TYPE,'status',$todo_sel_status);
      $context_item->save();
      if ( $todo_sel_status > 9 ) {
         $todo_sel_status_for_manager = $todo_sel_status - 10;
      } else {
         $todo_sel_status_for_manager = $todo_sel_status;
      }
   } elseif ( empty($_GET[CS_TODO_TYPE.'_selstatus']) ) {
      $todo_sel_status = $context_item->getRubrikSelection(CS_TODO_TYPE,'status');
      if ( !empty($todo_sel_status) ) {
         $view->setSelectedStatus($todo_sel_status,CS_TODO_TYPE);
         if ( $todo_sel_status > 9 ) {
            $todo_sel_status_for_manager = $todo_sel_status - 10;
         } else {
            $todo_sel_status_for_manager = $todo_sel_status;
         }
      }
   }

   $todo_sel_assignment = 3;
   if ( !empty($_GET[CS_TODO_TYPE.'_selassignment'])
        and $_GET[CS_TODO_TYPE.'_selassignment'] != '-2'
      ) {
      $todo_sel_assignment = $_GET[CS_TODO_TYPE.'_selassignment'];
      $view->setSelectedAssignment($todo_sel_assignment,CS_TODO_TYPE);
      $context_item->setRubrikSelection(CS_TODO_TYPE,'assignment',$todo_sel_assignment);
      $context_item->save();
   } elseif ( empty($_GET[CS_TODO_TYPE.'_selassignment']) ) {
      $todo_sel_assignment = $context_item->getRubrikSelection(CS_TODO_TYPE,'assignment');
      if ( !empty($todo_sel_assignment) ) {
         $view->setSelectedAssignment($todo_sel_assignment,CS_TODO_TYPE);
      }
   }

   // filter room id array
   $temp = array();
   foreach($configuration_room_todo_limit as $limit) {
      if(in_array($limit, $room_id_array)) {
         $temp[] = $limit;
      }
   }
   $temp[] = $current_context->getItemID();
   $todo_room_id_array = $temp;

   $todo_manager = $environment->getToDoManager();
   $todo_manager->setContextArrayLimit($todo_room_id_array);
   $todo_ids = $todo_manager->getIDArray();
   $count_all_todos = count($todo_ids);
   $todo_manager->showNoNotActivatedEntries();
   $todo_manager->setSortOrder('date');
   if ( !empty($todo_sel_status_for_manager) ) {
      $todo_manager->setStatusLimit($todo_sel_status_for_manager);
   }
   if ($todo_sel_assignment == '3'){
      $current_user = $environment->getCurrentUserItem();
      $user_list = $current_user->getRelatedUserList();
      $user_item = $user_list->getFirst();
      $user_id_array = array();
      while ($user_item){
         $user_id_array[] = $user_item->getItemID();
         $user_item = $user_list->getNext();
      }
      $todo_manager->setAssignmentLimit($user_id_array);
      unset($user_id_array);
      unset($user_list);
   }
   if ( !empty($search) ) {
      $todo_manager->setSearchLimit($search);
   }
   $todo_manager->select();
   $todo_list = $todo_manager->get();
   $view->setTodoList($todo_list);
   if ( isset($count_all_todos) ) {
      $view->setCountAllTodos($count_all_todos);
   }
   // todo

}

if ( !empty($only_show_array) ) {
   $dates_manager->resetLimits();
   $dates_manager->setWithoutDateModeLimit();
   $dates_manager->setIDArrayLimit($only_show_array);
}

$ids = $dates_manager->getIDArray();       // returns an array of item ids
$count_all_shown = count($ids);

if ( empty($only_show_array) ) {
   if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
      if (!empty($year)) {
         $dates_manager->setYearLimit($year);
      }
      if (!empty($month)) {
         if (!empty($presentation_mode) and $presentation_mode =='2'){
            $real_month = mb_substr($month,4,2);
            $first_char = mb_substr($real_month,0,1);
            if ($first_char == '0'){
               $real_month = mb_substr($real_month,1,2);
            }
            $dates_manager->setMonthLimit($real_month);
         }else{
            $real_month = mb_substr($month,4,2);
            $first_char = mb_substr($real_month,0,1);
            if ($first_char == '0'){
               $real_month = mb_substr($real_month,1,2);
            }
            $dates_manager->setMonthLimit2($real_month);
         }
      }
      $dates_manager->setDateModeLimit($selstatus);
   }
   if ( $sel_assignment != '2'
        and $environment->inPrivateRoom()
        and $environment->inConfigArray('c_use_new_private_room',$environment->getCurrentContextID())
      ) {
      $current_user = $environment->getCurrentUserItem();
      $user_list = $current_user->getRelatedUserList();
      $user_item = $user_list->getFirst();
      $user_id_array = array();
      while ($user_item){
         $user_id_array[] = $user_item->getItemID();
         $user_item = $user_list->getNext();
      }
      $dates_manager->setAssignmentLimit($user_id_array);
   }

   if ( $interval > 0 ) {
      $dates_manager->setIntervalLimit($from-1,$interval);
   }
}
if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
   $dates_manager->selectDistinct();
} else {
   $dates_manager->select();
}

$list = $dates_manager->get();        // returns a cs_list of dates_items

if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
   $current_user = $environment->getCurrentUserItem();

   // only access display config, if user is not root
   if( !$current_user->isRoot() and $environment->inPrivateRoom() ) {
     $myentries_array = $current_context->getMyCalendarDisplayConfig();

     if(in_array("mycalendar_dates_assigned_to_me", $myentries_array)){
      $temp_list = new cs_list();
       $current_user = $environment->getCurrentUserItem();
       $current_user_list = $current_user->getRelatedUserList();

       $temp_element = $list->getFirst();
       while($temp_element){
        $temp_user = $current_user_list->getFirst();
        while($temp_user){
           if($temp_element->isParticipant($temp_user)){
             $temp_list->add($temp_element);
           }
           $temp_user = $current_user_list->getNext();
        }
        $temp_element = $list->getNext();
       }

       $list = $temp_list;
     }
     $list->sortby('date');
   }
}
if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
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
if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
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
if ( isset($count_all) ) {
   $view->setCountAll($count_all);
}
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setSelectedStatus($selstatus);
$view->setDisplayMode($seldisplay_mode);
$view->setClipboardIDArray($clipboard_id_array);
if ( isset($buzzword_list) ) {
   $view->setAvailableBuzzwords($buzzword_list);
}
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setAdditionalSelect();
$view->setActivationLimit($sel_activating_status);
$view->setSelectedColor($sel_color);
if (isset($color_array[0])){
   $view->setUsedColorArray($color_array);
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

// Safe information in session for later use
$session->setValue('date_clipboard', $clipboard_id_array);
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_dates_index_ids', $ids);
$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['selcolor'] = $sel_color;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['sel_array'] = $sel_array;
$index_search_parameter_array['selbuzzword'] = $selbuzzword;
$index_search_parameter_array['seltag_array'] = $seltag_array;
$index_search_parameter_array['sel_activating_status'] = $sel_activating_status;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_ids','true');








/**************/
/* Alter Code */
/**************/

}else{
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

// Get the translator object
$translator = $environment->getTranslationObject();

if (isset($_GET['back_to_index']) and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index')){
   $index_search_parameter_array = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['sort'] = $index_search_parameter_array['sort'];
   $params['selbuzzword'] = $index_search_parameter_array['selbuzzword'];
   $params['seltag_array'] = $index_search_parameter_array['seltag_array'];
   $params['interval'] = $index_search_parameter_array['interval'];
   $params['selcolor'] = $index_search_parameter_array['selcolor'];
   $params['sel_activating_status'] = $index_search_parameter_array['sel_activating_status'];
   $sel_array = $index_search_parameter_array['sel_array'];
   foreach($sel_array as $key => $value){
      $params['sel'.$key] = $value;
   }
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array');
   $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index');
   redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'index', $params);
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

if ( $seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') {

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
   }elseif($seldisplay_mode == 'calendar_month'){
    $presentation_mode = '2';
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
         $year = mb_substr($month,0,4);
         $real_month = mb_substr($month,4,2);
         $d_time = mktime(3,0,0,$real_month,'1',$year);
         $wday = date("w",$d_time);
         $week = mktime(3,0,0,$real_month,1 - ($wday - 1),$year);
         $presentation_mode = '2';
      }
      if (isset($_GET['year']) and $old_year != $year){
         $real_month = mb_substr($old_month,4,2);
         $real_day = mb_substr($old_month,6,2);
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
         $real_month = mb_substr($month,4,2);
         $day = mb_substr($month,6,2);
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
            $temp_year = mb_substr($month,0,4);
            $real_month = mb_substr($month,4,2);
            $d_time = mktime(3,0,0,$real_month,'1',$temp_year);
            $wday = date("w",$d_time);
            $temp_week = mktime(3,0,0,$real_month,1 - ($wday - 1),$temp_year);
            $presentation_mode = '2';
         }elseif (isset($_GET['year']) and $old_year != $year){
            $real_month = mb_substr($old_month,4,2);
            $real_day = mb_substr($old_month,6,2);
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

// Find current sort key
if ( isset($_GET['selcolor']) and $_GET['selcolor'] !='-2') {
   $sel_color = $_GET['selcolor'];
} else {
   $sel_color = '';
}

// Search / Select Area
if ( isset($_GET['option'])
     and isOption($_GET['option'],$translator->getMessage('COMMON_RESET')) ) {
   $from = 1;
   $search = '';
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
      if ($seldisplay_mode=='calendar'  or $seldisplay_mode == 'calendar_month' or $mode == 'formattach' or $mode == 'detailattach' or $environment->inPrivateRoom()){
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
         if (($context_item->_is_perspective($link_name[0]) and $context_item->withRubric($link_name[0])) or ($link_name[0] == 'user')) {
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
if ( isOption($delete_command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
   $params = $environment->getCurrentParameterArray();
   redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
               redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
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
$dates_manager = $environment->getDatesManager();

if ( empty($only_show_array) ) {
   $dates_manager->setContextLimit($environment->getCurrentContextID());
   $color_array = $dates_manager->getColorArray();
   if (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
      $dates_manager->setDateModeLimit(2);
      $dates_manager->setYearLimit($year);
      if (!empty($presentation_mode) and $presentation_mode =='2'){
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
         }
         $dates_manager->setMonthLimit($real_month);
      }else{
         $real_month = mb_substr($month,4,2);
         $first_char = mb_substr($real_month,0,1);
         if ($first_char == '0'){
            $real_month = mb_substr($real_month,1,2);
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

   if ( !empty($sel_color) and $sel_color != 2 ) {
      $dates_manager->setColorLimit('#'.$sel_color);
   }

   if ( !empty($ref_iid) and $mode == 'attached' ){
      $dates_manager->setRefIDLimit($ref_iid);
   }
   if ( !empty($ref_user) and $mode == 'attached' ){
      $dates_manager->setRefUserLimit($ref_user);
   }
   if ( !empty($sort) and ($seldisplay_mode!='calendar' or $seldisplay_mode == 'calendar_month' or $mode == 'formattach' or $mode == 'detailattach') ) {
      $dates_manager->setSortOrder($sort);
   }
   if ( !empty($search) ) {
      $dates_manager->setSearchLimit($search);
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
}

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
if (($seldisplay_mode == 'calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
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

if ( !empty($only_show_array) ) {
   $dates_manager->resetLimits();
   $dates_manager->setWithoutDateModeLimit();
   $dates_manager->setIDArrayLimit($only_show_array);
}

$ids = $dates_manager->getIDArray();       // returns an array of item ids
$count_all_shown = count($ids);

if ( empty($only_show_array) ) {
   if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
      if (!empty($year)) {
         $dates_manager->setYearLimit($year);
      }
      if (!empty($month)) {
         if (!empty($presentation_mode) and $presentation_mode =='2'){
            $real_month = mb_substr($month,4,2);
            $first_char = mb_substr($real_month,0,1);
            if ($first_char == '0'){
               $real_month = mb_substr($real_month,1,2);
            }
            $dates_manager->setMonthLimit($real_month);
         }else{
            $real_month = mb_substr($month,4,2);
            $first_char = mb_substr($real_month,0,1);
            if ($first_char == '0'){
               $real_month = mb_substr($real_month,1,2);
            }
            $dates_manager->setMonthLimit2($real_month);
         }
      }
      $dates_manager->setDateModeLimit($selstatus);
   }


   if ( $interval > 0 ) {
      $dates_manager->setIntervalLimit($from-1,$interval);
   }
}
if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
   $dates_manager->selectDistinct();
} else {
   $dates_manager->select();
}
$list = $dates_manager->get();        // returns a cs_list of dates_items

if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
   $list->sortby('date');
}

if (($seldisplay_mode=='calendar' or $seldisplay_mode == 'calendar_month') and !($mode == 'formattach' or $mode == 'detailattach') ){
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
if (isOption($option,$translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO')) and $_POST['index_view_action'] != '3'){
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
if ( isset($count_all) ) {
   $view->setCountAll($count_all);
}
$view->setCountAllShown($count_all_shown);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSortKey($sort);
$view->setSearchText($search);
$view->setSelectedStatus($selstatus);
$view->setDisplayMode($seldisplay_mode);
$view->setClipboardIDArray($clipboard_id_array);
if ( isset($buzzword_list) ) {
   $view->setAvailableBuzzwords($buzzword_list);
}
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setAdditionalSelect();
$view->setActivationLimit($sel_activating_status);
$view->setSelectedColor($sel_color);
if (isset($color_array[0])){
   $view->setUsedColorArray($color_array);
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

// Safe information in session for later use
$session->setValue('date_clipboard', $clipboard_id_array);
$session->setValue('interval', $interval); // interval is applied to all rubrics
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_selected_ids', $selected_ids);
$session->setValue('cid'.$environment->getCurrentContextID().'_dates_index_ids', $ids);
$index_search_parameter_array = array();
$index_search_parameter_array['interval'] = $interval;
$index_search_parameter_array['sort'] = $sort;
$index_search_parameter_array['selcolor'] = $sel_color;
$index_search_parameter_array['search'] = $search;
$index_search_parameter_array['sel_array'] = $sel_array;
$index_search_parameter_array['selbuzzword'] = $selbuzzword;
$index_search_parameter_array['seltag_array'] = $seltag_array;
$index_search_parameter_array['sel_activating_status'] = $sel_activating_status;
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index_parameter_array',$index_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_back_to_index','true');

}
?>