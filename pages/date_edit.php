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

// Function used for redirecting to connected rubrics
if (isset($_GET['return_attach_buzzword_list'])){
   $_POST = $session->getValue('buzzword_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
if (isset($_GET['return_attach_tag_list'])){
   $_POST = $session->getValue('tag_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
if (isset($_GET['return_attach_item_list'])){
   $_POST = $session->getValue('linked_items_post_vars');
   unset($_POST['option']);
   unset($_POST['right_box_option']);
}
// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session,$environment;
   $session->unsetValue($environment->getCurrentModule().'_add_files');
   $session->unsetValue($environment->getCurrentModule().'_add_buzzwords');
   $session->unsetValue($environment->getCurrentModule().'_add_tags');
   $session->unsetValue($current_iid.'_post_vars');
}

//***********************Begin Calendar Functions**********************//
function getCalendarParameterArrayByItem($item){
   global $session,$environment;
   $converted_day_start = convertDateFromInput($item->getStartingDay(),$environment->getSelectedLanguage());
   $params = array();
   if ($converted_day_start['conforms'] == TRUE) {
      $params['year'] = mb_substr($converted_day_start['datetime'],0,4);
      $params['day'] = mb_substr($converted_day_start['datetime'],8,2);
      $params['month'] = $params['year'].mb_substr($converted_day_start['datetime'],5,2).$params['day'];
      $d_time = mktime ( 3, 0, 0, mb_substr($converted_day_start['datetime'],5,2), $params['day'], $params['year'] );
      $wday = date ( "w", $d_time );
      $params['week'] = mktime ( 3, 0, 0, mb_substr($converted_day_start['datetime'],5,2), $params['day'] - ($wday - 1 ), $params['year']);
      $parameter_presentation_mode = $session->getValue('date_presentation_mode');
      if ( isset ($parameter_presentation_mode) and !empty($parameter_presentation_mode) ){
         $params['presentation_mode'] = $parameter_presentation_mode;
      }else{
         $params['presentation_mode'] = '1';
      }
   }else{
      $params = getCalendarParameterArray();
   }
   return $params;
}

function getCalendarParameterArray(){
   global $session,$environment;
   $params = array();
   $parameter_year = $session->getValue('date_year');
   if ( isset ($parameter_year) and !empty($parameter_year) ){
      $params['year'] = $parameter_year;
   }else{
      $params['year'] =  date ( "Y");
   }
   $parameter_month = $session->getValue('date_month');
   if ( isset ($parameter_month) and !empty($parameter_month) ){
      $params['month'] = $parameter_month;
   }else{
      $params['month'] =  date ( "Ymd");
   }
   $parameter_day = $session->getValue('date_day');
   if ( isset ($parameter_day) and !empty($parameter_day) ){
      $params['day'] = $parameter_day;
   }else{
      $params['day'] =  date ( "d");
   }
   $parameter_week = $session->getValue('date_week');
   if ( isset ($parameter_week) and !empty($parameter_week) ){
      $params['week'] = $parameter_week;
   }else{
      $month = mb_substr($params['month'],4,2);
      $d_time = mktime ( 3, 0, 0, $month , $params['day'], $params['year'] );
      $wday = date ( "w", $d_time );
      $parameter_week = mktime ( 3, 0, 0, $month, $params['day'] - ( $wday - 1 ), $params['year'] );
      $params['week'] =  $parameter_week;
   }
   $parameter_presentation_mode = $session->getValue('date_presentation_mode');
   if ( isset ($parameter_presentation_mode) and !empty($parameter_presentation_mode) ){
      $params['presentation_mode'] = $parameter_presentation_mode;
   }else{
      $params['presentation_mode'] = '1';
   }
   return $params;
}

function setCalendarSessionArray($parameter_array){
   global $session,$environment;
   if (isset ($parameter_array['year'])){
      $session->setValue('date_year',$parameter_array['year']);
   }
   if (isset ($parameter_array['month'])){
      $session->setValue('date_month',$parameter_array['month']);
   }
   if (isset ($parameter_array['week'])){
      $session->setValue('date_week',$parameter_array['week']);
   }
   if (isset ($parameter_array['day'])){
      $session->setValue('date_day',$parameter_array['day']);
   }
   if (isset ($parameter_array['time'])){
      $session->setValue('date_time',$parameter_array['time']);
   }else{
      $session->setValue('date_time','0');
   }
   if (isset ($parameter_array['presentation_mode'])){
      $session->setValue('date_presentation_mode',$parameter_array['presentation_mode']);
   }
}

function unsetCalendarSessionArray(){
   global $session,$environment;
   $session->unsetValue('date_year');
   $session->unsetValue('date_month');
   $session->unsetValue('date_day');
   $session->unsetValue('date_time');
   $session->unsetValue('date_presentation_mode');
}

//***********************End Calendar Functions**********************//
//*******************************************************************//


if ( isset($_GET['seldisplay_mode']) ) {
   $seldisplay_mode = $_GET['seldisplay_mode'];
   $session->setValue($environment->getCurrentContextID().'_dates_seldisplay_mode',$_GET['seldisplay_mode']);
} elseif ( !empty($_POST['seldisplay_mode']) ) {
   $session->setValue($environment->getCurrentContextID().'_dates_seldisplay_mode',$_POST['seldisplay_mode']);
   $seldisplay_mode = $_POST['seldisplay_mode'];
}elseif ( $session->issetValue($environment->getCurrentContextID().'_dates_seldisplay_mode') ) {
   $seldisplay_mode = $session->getValue($environment->getCurrentContextID().'_dates_seldisplay_mode');
} else {
   $seldisplay_mode ='';
}


// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

$parameter_array = $environment->getCurrentParameterArray();
setCalendarSessionArray($parameter_array);
// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}
$with_anchor = false;

// Get item to be edited

if ( !empty($_GET['mode']) ) {
   $private_date = true;
} elseif ( !empty($_POST['mode']) ) {
   $private_date = true;
} else {
   $private_date = false;
}

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   $dates_item = NULL;
} else {
   $dates_manager = $environment->getDatesManager();
   $dates_item = $dates_manager->getItem($current_iid);
   if(empty($_POST)){
      $buzzword_array = array();
      $buzzwords = $dates_item->getBuzzwordList();
      $buzzword = $buzzwords->getFirst();
      while($buzzword){
         $buzzword_array[] = $buzzword->getItemID();
         $buzzword = $buzzwords->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids',$buzzword_array);
   }
   if(empty($_POST)){
      $tag_array = array();
      $tags = $dates_item->getTagList();
      $tag = $tags->getFirst();
      while($tag){
         $tag_array[] = $tag->getItemID();
         $tag = $tags->getNext();
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids',$tag_array);
   }
   if(empty($_POST)){
      $link_item_array = array();
      $link_item_array = $dates_item->getAllLinkedItemIDArray();
      $session->setValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids',$link_item_array);
   }
}

// Check access rights
if ( $context_item->isProjectRoom() and $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);

} elseif ( $current_iid != 'NEW' and !isset($dates_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
}  elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($dates_item) and
              $dates_item->mayEdit($current_user))) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
      $session->unsetValue('buzzword_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
      $session->unsetValue('tag_post_vars');
      $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
      $session->unsetValue('linked_items_post_vars');
      cleanup_session($current_iid);
      if (isset($_POST['seldisplay_mode'])){
         $params = array();
         if (isset($dates_item)){
            $params = getCalendarParameterArrayByItem($dates_item);
         }else{
            $params = getCalendarParameterArray();
         }
         unsetCalendarSessionArray();
         if (isset($history['1']['function']) and $history['1']['function'] !='index'){
            $params['iid']= $current_iid;
            redirect($environment->getCurrentContextID(),
                     CS_DATE_TYPE, 'detail',$params);
         }else{
            redirect($environment->getCurrentContextID(),
                     CS_DATE_TYPE, 'index',$params);
         }
      } else {
         if ( $current_iid == 'NEW' ) {
            $params = array();
            $params = getCalendarParameterArray();
            unsetCalendarSessionArray();
            redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'index', $params);
         } else {
            $params = array();
            unsetCalendarSessionArray();
            $params['iid'] = $current_iid;
            redirect($environment->getCurrentContextID(), CS_DATE_TYPE, 'detail', $params);
         }
      }
   }


   // Show form and/or save item
   else {

      // Initialize the form
      $class_params= array();
      $class_params['environment'] = $environment;
      $form = $class_factory->getClass(DATE_FORM,$class_params);
      unset($class_params);

      include_once('include/inc_fileupload_edit_page_handling.php');

      if ($seldisplay_mode == 'calendar'){
         $form->setCalendarDateStatus();
      }
      $date = $session->getValue('date_day');
      if ( !empty($date) ){
         if ($environment->getSelectedLanguage() == 'en'){
            if (mb_strlen($date)==1){
               $date = '0'.$date;
            }
            $month = $session->getValue('date_month');
            $month = mb_substr($month,4,2);
            $date = $date.' '.$environment->getTranslationObject()->getShortMonthName($month).' '.$session->getValue('date_year');
         }else{
            $month = $session->getValue('date_month');
            $month = mb_substr($month,4,2);
            $date .= '.'.$month;
            $date .= '.'.$session->getValue('date_year');
         }
         $form->setPrivateDateStartingDate($date);
      }
      $time = $session->getValue('date_time');
      if ( !empty($time) ){
         if ($environment->getSelectedLanguage() =='en'){
            $time = $time;
            if ( $time > 12 ){
               $time = $time - 12;
               $time1 = $time.':00 pm';
               $time2 = $time + 1;
               $time2 = $time2.':00 pm';
            }else{
               $time1 = $time.':00 am';
               $time2 = $time + 2;
               $time2 = $time2.':00 am';
            }
         }else{
            $time1 = $time.':00';
            $time2 = $time + 1;
            $time2 = $time2.':00';
         }
         $form->setPrivateDateStartingTime($time1);
         $form->setPrivateDateEndingTime($time2);
      }
      include_once('include/inc_right_boxes_handling.php');

      // Load form data from postvars
      if ( !empty($_POST) ) {
         if (empty($session_post_vars)){
            $session_post_vars = $_POST;
         }
         if ( !empty($command) and isOption($command, $translator->getMessage('COMMON_NEW_BUZZWORD_BUTTON')) ){
            $session_post_vars['new_buzzword']='';
         }
          if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         if ( isset($post_buzzword_ids) AND !empty($post_buzzword_ids) ) {
            $session_post_vars['buzzwordlist'] = $post_buzzword_ids;
         }
         if ( isset($post_tag_ids) AND !empty($post_tag_ids) ) {
            $session_post_vars['taglist'] = $post_tag_ids;
         }
         $form->setFormPost($session_post_vars);
      }

      // Back from multi upload
      elseif ( $from_multiupload ) {
         if ( isset($post_file_ids) AND !empty($post_file_ids) ) {
            $session_post_vars['filelist'] = $post_file_ids;
         }
         $form->setFormPost($session_post_vars);
      }
      // Load form data from database
      elseif ( isset($dates_item) ) {
         $form->setItem($dates_item);

         // Files
         $file_list = $dates_item->getFileList();
         if ( !$file_list->isEmpty() ) {
            $file_array = array();
            $file_item = $file_list->getFirst();
            while ( $file_item ) {
               $temp_array = array();
               $temp_array['name'] = $file_item->getDisplayName();
               $temp_array['file_id'] = (int)$file_item->getFileID();
               $file_array[] = $temp_array;
               $file_item = $file_list->getNext();
            }
            if ( !empty($file_array)) {
               $session->setValue($environment->getCurrentModule().'_add_files', $file_array);
            }
         }
      }

      // Create data for a new item
      elseif ( $current_iid == 'NEW' ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');trigger_error('dates_edit was called in an unknown manner', E_USER_ERROR);
      }

      if ($session->issetValue($environment->getCurrentModule().'_add_files')) {
         $form->setSessionFileArray($session->getValue($environment->getCurrentModule().'_add_files'));
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and
           (isOption($command, $translator->getMessage('DATES_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('DATES_CHANGE_BUTTON'))
            or isOption($command, $translator->getMessage('DATES_PRIVATE_SAVE_BUTTON'))
            or isOption($command, $translator->getMessage('DATES_PRIVATE_CHANGE_BUTTON'))) ) {

         $correct = $form->check();
         if ( $correct ) {

            // Create new item
            $item_is_new = false;
            if ( !isset($dates_item) ) {
               $dates_manager = $environment->getdatesManager();
               $dates_item = $dates_manager->getNewItem();
               $dates_item->setContextID($environment->getCurrentContextID());
               $user = $environment->getCurrentUserItem();
               $dates_item->setCreatorItem($user);
               $dates_item->setCreationDate(getCurrentDateTimeInMySQL());
               $item_is_new = true;
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $dates_item->setModificatorItem($user);
            $dates_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if ( isset($_POST['title']) ) {
               $dates_item->setTitle($_POST['title']);
            }
            if ( isset($_POST['description']) ) {
               $dates_item->setDescription($_POST['description']);
            }

            if ( isset($_POST['public']) ) {
               if ( $dates_item->isPublic() != $_POST['public'] ) {
                  $dates_item->setPublic($_POST['public']);
               }
            } else {
               if ( isset($_POST['private_editing']) ) {
                  $dates_item->setPrivateEditing('0');
               } else {
                  $dates_item->setPrivateEditing('1');
               }
            }

            if ( isset($_POST['hide']) ) {
                // variables for datetime-format of end and beginning
                $dt_hiding_time = '00:00:00';
                $dt_hiding_date = '9999-00-00';
                $dt_hiding_datetime = '';
                $converted_activate_day_start = convertDateFromInput($_POST['dayActivateStart'],$environment->getSelectedLanguage());
                if ($converted_activate_day_start['conforms'] == TRUE) {
                   $dt_hiding_datetime = $converted_activate_day_start['datetime'].' ';
                   $converted_activate_time_start = convertTimeFromInput($_POST['timeStart']);
                   if ($converted_activate_time_start['conforms'] == TRUE) {
                      $dt_hiding_datetime .= $converted_activate_time_start['datetime'];
                   }else{
                      $dt_hiding_datetime .= $dt_hiding_time;
                   }
                }else{
                   $dt_hiding_datetime = $dt_hiding_date.' '.$dt_hiding_time;
                }
                $dates_item->setModificationDate($dt_hiding_datetime);
            }else{
               if($dates_item->isNotActivated()){
                  $dates_item->setModificationDate(getCurrentDateTimeInMySQL());
               }
            }

            if ( isset($_POST['mode']) ) {
               $dates_item->setDateMode('1');
            }else{
               $dates_item->setDateMode('0');
            }

            // variables for datetime-format of end and beginning
            $dt_start_time = '00:00:00';
            $dt_end_time = '00:00:00';
            $dt_start_date = '0000-00-00';
            $dt_end_date = '0000-00-00';


            $converted_time_start = convertTimeFromInput($_POST['timeStart']);
            if ($converted_time_start['conforms'] == TRUE) {
               $dates_item->setStartingTime($converted_time_start['datetime']);
               $dt_start_time = $converted_time_start['datetime'];
            } else {
               $dates_item->setStartingTime($converted_time_start['display']);
            }

            $converted_day_start = convertDateFromInput($_POST['dayStart'],$environment->getSelectedLanguage());
            if ($converted_day_start['conforms'] == TRUE) {
               $dates_item->setStartingDay($converted_day_start['datetime']);
               $dt_start_date = $converted_day_start['datetime'];
            } else {
               $dates_item->setStartingDay($converted_day_start['display']);
            }

            if (!empty($_POST['timeEnd'])) {
               $converted_time_end = convertTimeFromInput($_POST['timeEnd']);
               if ($converted_time_end['conforms'] == TRUE) {
                  $dates_item->setEndingTime($converted_time_end['datetime']);
                  $dt_end_time = $converted_time_end['datetime'];
               } else {
                  $dates_item->setEndingTime($converted_time_end['display']);
               }
            } else {
               $dates_item->setEndingTime('');
            }

            if (!empty($_POST['dayEnd'])) {
               $converted_day_end = convertDateFromInput($_POST['dayEnd'],$environment->getSelectedLanguage());
               if ($converted_day_end['conforms'] == TRUE) {
                  $dates_item->setEndingDay($converted_day_end['datetime']);
                  $dt_end_date = $converted_day_end['datetime'];
               } else {
                  $dates_item->setEndingDay($converted_day_end['display']);
               }
            } else {
               $dates_item->setEndingDay('');
            }

            if ($dt_end_date == '0000-00-00') {
               $dt_end_date = $dt_start_date;
            }

            $dates_item->setDateTime_start($dt_start_date.' '.$dt_start_time);
            $dates_item->setDateTime_end($dt_end_date.' '.$dt_end_time);

            if (!empty($_POST['place'])) {
               $dates_item->setPlace($_POST['place']);
            } else {
               $dates_item->setPlace('');
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids')){
               $dates_item->setBuzzwordListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids'));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids')){
               $dates_item->setTagListByID($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids'));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
            }
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')){
               $dates_item->setLinkedItemsByIDArray(array_unique($session->getValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids')));
               $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            }

            if(isset($_POST['date_addon_color'])){
               $dates_item->setColor($_POST['date_addon_color']);
            }
            
            $item_files_upload_to = $dates_item;
            include_once('include/inc_fileupload_edit_page_save_item.php');

            // Save item
            $dates_item->save();
            if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids')){
               $id_array =  array_reverse($session->getValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids'));
            }else{
               $id_array =  array();
            }
            if ($item_is_new){
               $id_array[] = $dates_item->getItemID();
               $id_array = array_reverse($id_array);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_index_ids',$id_array);
           }

            // Redirect
            cleanup_session($current_iid);
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_buzzword_ids');
            $session->unsetValue('buzzword_post_vars');
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_'.$environment->getCurrentModule().'_tag_ids');
            $session->unsetValue('tag_post_vars');
            $session->unsetValue('cid'.$environment->getCurrentContextID().'_linked_items_index_selected_ids');
            $session->unsetValue('linked_items_post_vars');
            $context_item = $environment->getCurrentContextItem();
            $seldisplay_mode = $session->getValue($environment->getCurrentContextID().'_dates_seldisplay_mode');
            if (empty($seldisplay_mode)){
               $seldisplay_mode = $context_item->getDatesPresentationStatus();
            }
            if (isset($_POST['seldisplay_mode']) or $seldisplay_mode== 'calendar') {
               if ($seldisplay_mode == 'calendar') {
            $noticed_manager = $environment->getNoticedManager();
            $noticed = $noticed_manager->getLatestNoticed($dates_item->getItemID());
            if ( empty($noticed) or $noticed['read_date'] < $dates_item->getModificationDate() ) {
               $noticed_manager->markNoticed($dates_item->getItemID(),0);
            }
               }
                $params = array();
                $params = getCalendarParameterArrayByItem($dates_item);
                $params['seldisplay_mode'] = $seldisplay_mode;
                if($params['presentation_mode'] == '1' and !empty($params['week'])){
                   $converted_day_start = convertDateFromInput($_POST['dayStart'],$environment->getSelectedLanguage());
                   if ($converted_day_start['conforms'] == TRUE) {
                      $year = mb_substr($converted_day_start['datetime'],0,4);
                      $month = mb_substr($converted_day_start['datetime'],5,2);
                      $day = mb_substr($converted_day_start['datetime'],8,2);
                      $d_time = mktime ( 3, 0, 0, $month, $day, $year );
                      $wday = date ( "w", $d_time );
                      $parameter_week = mktime ( 3, 0, 0, $month, $day - ( $wday - 1 ), $year );
                      $params['week'] = $parameter_week;
                   }
                }
                unsetCalendarSessionArray();
                /*
                $history = $session->getValue('history');
                $i = 1;
                $j = $i+1;
                $funct = 'index';
                while (isset($history[$j]['function']) and $history[$i]['function'] == 'edit'){
                   $funct = $history[$j]['function'];
                   $i++;
                   $j++;
                }
                if ($funct !='index'){
                */
                  $params['iid'] = $current_iid;
                  if ( !is_numeric($current_iid) ) {
                     $params['iid'] = $dates_item->getItemID();
                  }
                  redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'detail',$params);
                /*
            }else{
                  redirect($environment->getCurrentContextID(),CS_DATE_TYPE, 'index',$params);
            }
            */
            }else{
               $params = array();
               $params['iid'] = $dates_item->getItemID();
               redirect($environment->getCurrentContextID(),
                     CS_DATE_TYPE, 'detail', $params);
            }
         }
      }

      // display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_VIEW,$params);
      unset($params);
      if ($with_anchor){
        $form_view->withAnchor();
      }
      if (isset($focus_element_onload)) {
         $form_view->setFocusElementOnLoad($focus_element_onload);
      }
      if (isset($focus_element_anchor)) {
         $form_view->setFocusElementAnchor($focus_element_anchor);
      }
      if (!mayEditRegular($current_user, $dates_item)) {
         $form_view->warnChanger();
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $params['width'] = 500;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('COMMON_EDIT_AS_MODERATOR'));
         $page->add($errorbox);
      }
      $form_view->setAction(curl($environment->getCurrentContextID(),CS_DATE_TYPE,'edit',''));
      $form_view->setForm($form);
      $page->add($form_view);
   }
}
?>