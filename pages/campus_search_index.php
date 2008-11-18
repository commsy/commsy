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

   // Find current rubric selection
   if ( isset($_POST['selrubric']) ) {
      $selrubric = $_POST['selrubric'];
      $from = 1;
   } elseif ( isset($_GET['selrubric']) ) {
      $selrubric = $_GET['selrubric'];
   }  else {
      $selrubric ='all';
   }
   // Find current restriction selection
   if ( isset($_POST['selrestriction']) ) {
      $selrestriction = $_POST['selrestriction'];
      $from = 1;
   } elseif ( isset($_GET['selrestriction']) ) {
      $selrestriction = $_GET['selrestriction'];
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
         $rubric_array[] = $link_name[0];
      }
   }
}

if ( $selrubric != 'all' ) {
   $rubric_array = array();
   $rubric_array[] = $selrubric;
}

// Get available buzzwords
$buzzword_manager = $environment->getLabelManager();
$buzzword_manager->resetLimits();
$buzzword_manager->setContextLimit($environment->getCurrentContextID());
$buzzword_manager->setTypeLimit('buzzword');
$buzzword_manager->setGetCountLinks();
$buzzword_manager->select();
$buzzword_list = $buzzword_manager->get();

// Get data from database
foreach ($rubric_array as $rubric) {
   if ( !( (!empty($selbuzzword) or !empty($last_selected_tag) ) and
    ( $rubric == CS_USER_TYPE or $rubric == CS_GROUP_TYPE or $rubric == CS_TOPIC_TYPE or $rubric == CS_INSTITUTION_TYPE )
   )){
      $rubric_ids = array();
      $rubric_list = new cs_list();
      $rubric_manager = $environment->getManager($rubric);
      if ($rubric!=CS_PROJECT_TYPE and $rubric!=CS_MYROOM_TYPE){
         $rubric_manager->setContextLimit($environment->getCurrentContextID());
      }
      $rubric_manager->setSearchLimit($search);
      $rubric_manager->setAttributeLimit($selrestriction);
      if ( !empty($selbuzzword) ) {
         $rubric_manager->setBuzzwordLimit($selbuzzword);
      }
      if ( !empty($last_selected_tag) ){
         $rubric_manager->setTagLimit($last_selected_tag);
      }

      if ($rubric=='user') {
         $rubric_manager->setUserLimit();
         $current_user= $environment->getCurrentUser();
         if ( $current_user->isUser() ) {
             $rubric_manager->setVisibleToAllAndCommsy();
         } else {
             $rubric_manager->setVisibleToAll();
         }
      } elseif ($rubric == CS_DATE_TYPE) {
         $rubric_manager->setWithoutDateModeLimit();
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
}

// Set data for view
$sublist = $search_list->getSubList($from-1,$interval);
$view->setList($sublist);
$view->setCountAllShown(count($campus_search_ids));
$view->setCountAll(count($campus_search_ids));
$view->setFrom($from);
$view->setInterval($interval);
$view->setSearchText($search);
$view->setSelectedRestriction($selrestriction);
$view->setSelectedRubric($selrubric);
$view->setSelectedFile($selfiles);
$view->setAvailableBuzzwords($buzzword_list);
$view->setSelectedBuzzword($selbuzzword);
$view->setSelectedTagArray($seltag_array);

// Add list view to page
$page->add($view);

// Safe information in session for later use
$session->setValue('cid'.$environment->getCurrentContextID().'_campus_search_index_ids', $campus_search_ids);
?>