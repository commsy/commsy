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

// Verify parameters for this page
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
} elseif (!empty($_POST['pin_iid'])) {
   $current_item_id = $_POST['pin_iid'];
} elseif (!empty($_GET['pin_iid'])) {
   $current_item_id = $_GET['pin_iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('A institution item id must be given.', E_USER_ERROR);
}

include_once('include/inc_delete_entry.php');

// Get the translator object
$translator = $environment->getTranslationObject();

$label_manager = $environment->getLabelManager();
$item = $label_manager->getItem($_GET['iid']);
$type = $item->getItemType();
if ($type != CS_INSTITUTION_TYPE) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
} else {

   //used to signal which "creator infos" of annotations are expanded...
   $creatorInfoStatus = array();
   if (!empty($_GET['creator_info_max'])) {
     $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
   }

   // initialize objects
   $current_context = $environment->getCurrentContextItem();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(INSTITUTION_DETAIL_VIEW,$params);
   unset($params);

   $institution_manager = $environment->getLabelManager();

   // set the view's item
   $institution_item = $institution_manager->getItem($current_item_id);
   $current_user = $environment->getCurrentUser();
   if ( !isset($institution_item) ) {
      include_once('functions/error_functions.php');trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $institution_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$institution_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {

      $detail_view->setItem($institution_item);

      // Enter or leave Institution
      if (!empty($_GET['institution_option'])) {
         $current_user = $environment->getCurrentUser();
         if ($_GET['institution_option']=='1') {
            $institution_item->addMember($current_user);
         } else if ($_GET['institution_option']=='2') {
            $institution_item->removeMember($current_user);
         }
      }

      //Set Read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($institution_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $institution_item->getModificationDate() ) {
         $reader_manager->markRead($institution_item->getItemID(),0);
      }
      //Set Noticed
      $noticed_manager = $environment->getNoticedManager();
      $noticed = $noticed_manager->getLatestNoticed($institution_item->getItemID());
      if ( empty($noticed) or $noticed['read_date'] < $institution_item->getModificationDate() ) {
         $noticed_manager->markNoticed($institution_item->getItemID(),0);
      }

      // set up browsing
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_institution_index_ids')) {
         $institution_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_institution_index_ids');
      } else {
         $institution_ids = array();
      }
      $detail_view->setBrowseIDs($institution_ids);

      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = array();
      $secon = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' and $link_name[0] !=$_GET['mod'] and $link_name[0] != CS_USER_TYPE) {
            switch ($detail_view->_is_perspective($link_name[0])) {
               case true:
                  $first[] = $link_name[0];
               break;
               case false:
                  $second[] = $link_name[0];
               break;
            }
         }
      }
      $room_modules = array_merge($first,$second);
      $rubric_connections = array();
      foreach ($room_modules as $module){
         if ($context_item->withRubric($module) ) {
            $ids = $institution_item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }

      $detail_view->setRubricConnections($rubric_connections);

      $annotations = $institution_item->getAnnotationList();
            $id_array = array();
            $annotation = $annotations->getFirst();
            while($annotation){
               $id_array[] = $annotation->getItemID();
               $annotation = $annotations->getNext();
            }
            $reader_manager->getLatestReaderByIDArray($id_array);
            $noticed_manager->getLatestNoticedByIDArray($id_array);
      $annotation = $annotations->getFirst();
      while($annotation ){
         $reader = $reader_manager->getLatestReader($annotation->getItemID());
         if ( empty($reader) or $reader['read_date'] < $annotation->getModificationDate() ) {
            $reader_manager->markRead($annotation->getItemID(),0);
         }
         $noticed = $noticed_manager->getLatestNoticed($annotation->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $annotation->getModificationDate() ) {
            $noticed_manager->markNoticed($annotation->getItemID(),0);
         }
         $annotation = $annotations->getNext();
      }
      $detail_view->setAnnotationList($annotations);

      // highlight search words in detail views
      $session_item = $environment->getSessionItem();
      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
         if ( !empty($search_array['search']) ) {
            $detail_view->setSearchText($search_array['search']);
         }
         unset($search_array);
      }

      $page->add($detail_view);

      // Safe information in session for later use
      $session->setValue('cid'.$environment->getCurrentContextID().'_institution_index_ids', $institution_ids);
   }
}
?>