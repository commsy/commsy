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
if ( isset($_GET['option']) and isOption($_GET['option'],getMessage('COMMON_RESET')) ) {
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

// Get data from database
foreach ($rubric_array as $rubric) {
   $rubric_ids = array();
   $rubric_list = new cs_list();
   $rubric_manager = $environment->getManager($rubric);
   /*Vorbereitung der Manager und Abzählen aller Einträge */
   if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
      $rubric_manager->setContextLimit($environment->getCurrentContextID());
   }
   if ($rubric == CS_DATE_TYPE) {
      $rubric_manager->setWithoutDateModeLimit();
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

   if ( !empty($selfiles) ) {
      $rubric_manager->setOnlyFilesLimit();
   }

   if ( $rubric != CS_MYROOM_TYPE ) {
      $rubric_manager->selectDistinct();
      $rubric_list = $rubric_manager->get();
   } else {
      $rubric_list = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID());;
   }

   $search_list->addList($rubric_list);
   if ($rubric!=CS_MYROOM_TYPE) {
      $temp_rubric_ids = $rubric_manager->getIDArray();
   } else {
      $current_user= $environment->getCurrentUser();
      $temp_rubric_ids = $rubric_manager->getRelatedContextListForUser($current_user->getUserID(),$current_user->getAuthSource(),$environment->getCurrentPortalID(),'id_array');;
   }
   if (!empty($temp_rubric_ids)){
      $rubric_ids = $temp_rubric_ids;
   }
   $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $rubric_ids);
   $campus_search_ids = array_merge($campus_search_ids, $rubric_ids);

}

// Set data for view
$sublist = $search_list->getSubList($from-1,$interval);
$view->setList($sublist);
$view->setCountAllShown(count($campus_search_ids));
$view->setCountAll($count_all);
$view->setFrom($from);
$view->setInterval($interval);
$view->setSearchText($search);
$view->setSelectedRestriction($selrestriction);
$view->setSelectedFile($selfiles);
$view->setAvailableBuzzwords($buzzword_list);
if($context_item->isDesign7()){
   $view->setChoosenRubric($selrubric);
}
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);
$view->setActivationLimit($sel_activating_status);

// Add list view to page
$page->add($view);

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
$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array', $campus_search_parameter_array);
$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids', $campus_search_ids);
?>