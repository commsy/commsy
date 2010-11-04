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
      $environment->setCurrentParameter('search',$_POST['search']);
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
   } else if (isset($_POST['selbuzzword']) and $_POST['selbuzzword'] != '-2') {
        $selbuzzword = $_POST['selbuzzword'];
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
   }else if(isset($_POST['seltag']) and $_POST['seltag'] =='yes') {
        // Seltag aus der Hiddenform holen
        $i = 0;
      while ( !isset($_POST['seltag_'.$i]) ){
         $i++;
      }
      $seltag_array[] = $_POST['seltag_'.$i];
      $j = 0;
      while(isset($_POST['seltag_'.$i]) and $_POST['seltag_'.$i] !='-2'){
         if (!empty($_POST['seltag_'.$i])){
            $seltag_array[$i] = $_POST['seltag_'.$i];
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
   // Find current selection seltag (Kategorie) fehlt die POST Abfrage in cs_index_view
   // ein Hiddenfeld hinzufügen.
   if (isset($_POST['selgroup'])) {
      $selgroup = $_POST['selgroup'];
   } elseif ( isset($_GET['selgroup']) ) {
      $selgroup = $_GET['selgroup'];
   } else {
      $selgroup = '';
   }

   if (isset($_POST['selcolor'])) {
      $selcolor = $_POST['selcolor'];
   } else if(isset($_GET['selcolor'])){
      $selcolor = $_GET['selcolor'];
   } else {
      $selcolor = '';
   }

   if (isset($_POST['seluser'])) {
      $seluser = $_POST['seluser'];
   } else if (isset($_GET['seluser'])){
      $seluser = $_GET['seluser'];
   } else {
      $seluser = '';
   }

   if (isset($_POST['selstatus'])) {
      $selstatus = $_POST['selstatus'];
   } else if (isset($_GET['selstatus'])){
      $selstatus = $_GET['selstatus'];
   } else {
      $selstatus = 2;
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


$search_list = new cs_list();
$campus_search_ids = array();
$params = array();
$params['environment'] = $environment;
$params['with_modifying_actions'] = true;
$view = $class_factory->getClass(ITEM_INDEX_VIEW,$params);
unset($params);

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
} else if (isset($_POST['selactivatingstatus']) and $_POST['selactivatingstatus'] !='-2') {
   $sel_activating_status = $_POST['selactivatingstatus'];
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

/*Durchführung möglicher Einschränkungen*/
foreach($sel_array as $rubric => $value){
   $label_manager = $environment->getManager($rubric);
   $label_manager->setContextLimit($environment->getCurrentContextID());
   $label_manager->select();
   $rubric_list = $label_manager->get();
   $temp_rubric_list = clone $rubric_list;
   $view->setAvailableRubric($rubric,$temp_rubric_list);
   $view->setSelectedRubric($rubric,$value);
   unset($rubric_list);
}

// translation of entry to rubrics for new private room
if ( $environment->inPrivateRoom()
     and in_array(CS_ENTRY_TYPE,$rubric_array)
   ) {
   $temp_array = array();
   $temp_array2 = array();
   $rubric_array2 = array();
   $temp_array[] = CS_ANNOUNCEMENT_TYPE;
   $temp_array[] = CS_TODO_TYPE;
   $temp_array[] = CS_DISCUSSION_TYPE;
   $temp_array[] = CS_MATERIAL_TYPE;
   $temp_array[] = CS_DATE_TYPE;
   foreach ( $temp_array as $temp_rubric ) {
      if ( !in_array($temp_rubric,$rubric_array) ) {
         $temp_array2[] = $temp_rubric;
      }
   }
   foreach ( $rubric_array as $temp_rubric ) {
      if ( $temp_rubric != CS_ENTRY_TYPE ) {
         $rubric_array2[] = $temp_rubric;
      } else {
         $rubric_array2 = array_merge($rubric_array2,$temp_array2);
      }
   }
   $rubric_array = $rubric_array2;
   unset($rubric_array2);
}

// Get data from database
global $c_plugin_array;
foreach ($rubric_array as $rubric) {
   if ( !isset($c_plugin_array)
        or !in_array(strtolower($rubric),$c_plugin_array)
      ) {
      $rubric_ids = array();
      $rubric_list = new cs_list();
      $rubric_manager = $environment->getManager($rubric);
      if ( $rubric == CS_PROJECT_TYPE ) {
         $rubric_manager->setQueryWithoutExtra();
      }
      /*Vorbereitung der Manager und Abzählen aller Einträge */
      if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
         $rubric_manager->setContextLimit($environment->getCurrentContextID());
      } elseif ( $rubric == CS_PROJECT_TYPE
                 and $environment->inCommunityRoom()
               ) {
         $rubric_manager->setContextLimit($environment->getCurrentPortalID());
         $current_community_item = $environment->getCurrentContextItem();
         $rubric_manager->setIDArrayLimit($current_community_item->getInternalProjectIDArray());
         unset($current_community_item);
      }
      if ($rubric == CS_DATE_TYPE AND $selstatus == 2) {
         $rubric_manager->setWithoutDateModeLimit();
      }
      else if ($rubric == CS_DATE_TYPE AND $selstatus != 2) {
          $rubric_manager->setDateModeLimit($selstatus);
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

      foreach($sel_array as $rubric => $value){
         if (!empty($value)){
            $rubric_manager->setRubricLimit($rubric,$value);
         }
      }

      if ( $sel_activating_status != '1') {
         $rubric_manager->showNoNotActivatedEntries();
      }
      $rubric_manager->setSearchLimit($search);
      $rubric_manager->setAttributeLimit($selrestriction);
      if ( !empty($selbuzzword) ) {
         $rubric_manager->setBuzzwordLimit($selbuzzword);
      }
      if ( !empty($last_selected_tag) ){
         $rubric_manager->setTagLimit($last_selected_tag);
      }
      if ( !empty($selcolor) and $selcolor != '2' and $selrubric == "date") {
          $rubric_manager->setColorLimit('#'.$selcolor);
      }

      if ( ($selrubric == "todo") and !empty($selstatus)) {
          $rubric_manager->setStatusLimit($selstatus);
      }

      if (!empty($seluser)) {
          $rubric_manager->setUserLimit($seluser);
      }

      if ( !empty($selfiles) ) {
         $rubric_manager->setOnlyFilesLimit();
      }

      if ( $rubric != CS_MYROOM_TYPE ) {
         $rubric_manager->selectDistinct();
         $rubric_list = $rubric_manager->get();
         $temp_rubric_ids = $rubric_manager->getIDArray();
      } else {
         $rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
         $temp_rubric_ids = $rubric_list->getIDArray();
      }

      $search_list->addList($rubric_list);
      if (!empty($temp_rubric_ids)){
         $rubric_ids = $temp_rubric_ids;
      }
      $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $rubric_ids);
      $campus_search_ids = array_merge($campus_search_ids, $rubric_ids);
   }
}
if($interval == 0){
	$interval = $search_list->getCount();
}
// Set data for view
$sublist = $search_list->getSubList($from-1,$interval);
$view->setList($sublist);
$view->setCountAllShown($search_list->getCount());
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
$view->setActivationLimit($sel_activating_status);
$view->setSelectedUser($seluser);
$view->setSelectedGroup($selgroup);
$view->setSelectedStatus($selstatus);
$view->setSelectedColor($selcolor);



// Add list view to page
$page->add($view);

// Safe information in session for later use
$campus_search_parameter_array = array();
$campus_search_parameter_array['search'] = $search;
$campus_search_parameter_array['selrestriction'] = $selrestriction;
$campus_search_parameter_array['selrubric'] = $selrubric;
$campus_search_parameter_array['selbuzzword'] = $selbuzzword;
$campus_search_parameter_array['selstatus'] = $selstatus;
$campus_search_parameter_array['selcolor'] = $selcolor;
$campus_search_parameter_array['selgroup'] = $selgroup;
$campus_search_parameter_array['seluser'] = $seluser;
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
?>