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
} else {
   include_once('functions/error_functions.php');
   trigger_error('A dates item id must be given.', E_USER_ERROR);
}

include_once('include/inc_delete_entry.php');

// Get the translator object
$translator = $environment->getTranslationObject();

$item_manager = $environment->getItemManager();
$type = $item_manager->getItemType($_GET['iid']);
if ($type != CS_DATE_TYPE) {
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
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(DATE_DETAIL_VIEW,$params);
   unset($params);

   $dates_manager = $environment->getDatesManager();
   $dates_item = $dates_manager->getItem($current_item_id);

   if ( !isset($dates_item) ) {
      include_once('functions/error_functions.php');
      trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $dates_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$dates_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {
      // Get clipboard
      if ( $session->issetValue('date_clipboard') ) {
         $clipboard_id_array = $session->getValue('date_clipboard');
      } else {
         $clipboard_id_array = array();
      }

      // Copy to clipboard
      if ( isset($_GET['add_to_date_clipboard'])
           and !in_array($current_item_id, $clipboard_id_array) ) {
         $clipboard_id_array[] = $current_item_id;
         $session->setValue('date_clipboard', $clipboard_id_array);
      }


      if (!empty($_GET['date_option'])) {
         $current_user = $environment->getCurrentUser();
         if ($_GET['date_option']=='1') {
            $dates_item->addParticipant($current_user);
         } else if ($_GET['date_option']=='2') {
            $dates_item->removeParticipant($current_user);
         }
      }
      //is current room open?
      $context_item = $environment->getCurrentContextItem();
      $room_open = $context_item->isOpen();

      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $room_open;
      $params['creator_info_status'] = $creatorInfoStatus;
      $detail_view = $class_factory->getClass(DATE_DETAIL_VIEW,$params);
      unset($params);

      $detail_view->setClipboardIDArray($clipboard_id_array);

      // set the view's item
      $detail_view->setItem($dates_item);

      //Set Read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($dates_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $dates_item->getModificationDate() ) {
         $reader_manager->markRead($dates_item->getItemID(),0);
      }

      //Set Noticed
      $noticed_manager = $environment->getNoticedManager();
      $noticed = $noticed_manager->getLatestNoticed($dates_item->getItemID());
      if ( empty($noticed) or $noticed['read_date'] < $dates_item->getModificationDate() ) {
         $noticed_manager->markNoticed($dates_item->getItemID(),0);
      }

      // set up browsing
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_dates_index_ids')) {
         $dates_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_dates_index_ids');
      } else {
         $dates_ids = array();
      }
      $detail_view->setBrowseIDs($dates_ids);

      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = '';
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
            switch ($link_name[0]) {
               case 'group':
               if (empty($first)){
                  $first = 'group';
               }
               break;
               case CS_TOPIC_TYPE:
               if (empty($first)){
                  $first = CS_TOPIC_TYPE;
               }
               break;
            }
         }
      }
      // set up ids of linked items
      $material_ids = $dates_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $material_ids);
      if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
      }
      if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_GROUP_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
      }
            if ( $context_item->withRubric(CS_INSTITUTION_TYPE) ) {
         $ids = $dates_item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_institutions_index_ids', $ids);
      }
      $rubric_connections = array();
      if ($first == CS_TOPIC_TYPE){
         $rubric_connections = array(CS_TOPIC_TYPE);
         if ($context_item->withRubric(CS_GROUP_TYPE) ){
            $rubric_connections[] = CS_GROUP_TYPE;
         }
         if ($context_item->withRubric(CS_INSTITUTION_TYPE)) {
            $rubric_connections[] = CS_INSTITUTION_TYPE;
         }
      }elseif($first == 'group'){
         $rubric_connections = array(CS_GROUP_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
            $rubric_connections[] = CS_TOPIC_TYPE;
         }
      }
            elseif ($first == CS_INSTITUTION_TYPE){
         $rubric_connections = array(CS_INSTITUTION_TYPE);
         if ($context_item->withRubric(CS_TOPIC_TYPE) ){
            $rubric_connections[] = CS_TOPIC_TYPE;
         }
      }
      $rubric_connections[] = CS_MATERIAL_TYPE;
      $detail_view->setRubricConnections($dates_item);

      // add annotations to detail view
      $annotations = $dates_item->getAnnotationList();
      $reader_manager = $environment->getReaderManager();
      $noticed_manager = $environment->getNoticedManager();
      $annotation = $annotations->getFirst();
      $id_array = array();
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
   }
}
?>