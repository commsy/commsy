<?PHP
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
   trigger_error('A todo item id must be given.', E_USER_ERROR);
}

include_once('include/inc_delete_entry.php');

// initialize objects
$todo_manager = $environment->getToDosManager();
$todo_item = $todo_manager->getItem($current_item_id);

// Get the translator object
$translator = $environment->getTranslationObject();

if ( !isset($todo_item) ) {
   include_once('functions/error_functions.php');
   trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
} elseif ( $todo_item->isDeleted() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
   $page->add($errorbox);
} elseif ( !$todo_item->maySee($current_user) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
} else {
   // Get clipboard
   if ( $session->issetValue('todo_clipboard') ) {
      $clipboard_id_array = $session->getValue('todo_clipboard');
   } else {
      $clipboard_id_array = array();
   }

   // Copy to clipboard
   if ( isset($_GET['add_to_todo_clipboard'])
        and !in_array($current_item_id, $clipboard_id_array) ) {
      $clipboard_id_array[] = $current_item_id;
      $session->setValue('todo_clipboard', $clipboard_id_array);
   }
   //is current context open?
   $context_item = $environment->getCurrentContextItem();
   $context_open = $context_item->isOpen();


   // Enter or leave Topic
   if (!empty($_GET['todo_option'])) {
      $current_user = $environment->getCurrentUser();
      if ($_GET['todo_option']=='1') {
         $todo_item->addProcessor($current_user);
      } else if ($_GET['todo_option']=='2') {
         $todo_item->removeProcessor($current_user);
      }
   }

   //used to signal which "creator infos" of annotations are expanded...
   $creatorInfoStatus = array();
   if (!empty($_GET['creator_info_max'])) {
      $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
   }

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $context_open;
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(TODO_DETAIL_VIEW,$params);
   unset($params);


   // set the view's item
   $detail_view->setItem($todo_item);
   $detail_view->setClipboardIDArray($clipboard_id_array);
   $detail_view->setRubricConnections(array(CS_GROUP_TYPE,CS_MATERIAL_TYPE));


   //Set Read
   $reader_manager = $environment->getReaderManager();
   $reader = $reader_manager->getLatestReader($todo_item->getItemID());
   if ( empty($reader) or $reader['read_date'] < $todo_item->getModificationDate() ) {
      $reader_manager->markRead($todo_item->getItemID(),0);
   }
   //Set Noticed
   $noticed_manager = $environment->getNoticedManager();
   $noticed = $noticed_manager->getLatestNoticed($todo_item->getItemID());
   if ( empty($noticed) or $noticed['read_date'] < $todo_item->getModificationDate() ) {
      $noticed_manager->markNoticed($todo_item->getItemID(),0);
   }

   // set up browsing
   if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_todo_index_ids') ) {
      $todo_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_todo_index_ids');
   } else {
      $todo_ids = array();
   }
   $detail_view->setBrowseIDs($todo_ids);
   if ( isset($_GET['pos']) ) {
      $detail_view->setPosition($_GET['pos']);
   }

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
   $material_ids = $todo_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
   $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $material_ids);
   if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
      $ids = $todo_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
   }
   if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
      $ids = $todo_item->getLinkedItemIDArray(CS_GROUP_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
   }
   $rubric_connections = array();
   if ($first == CS_TOPIC_TYPE){
      $rubric_connections = array(CS_TOPIC_TYPE);
      if ($context_item->withRubric(CS_GROUP_TYPE) ){
         $rubric_connections[] = CS_GROUP_TYPE;
      }
   }elseif($first == 'group'){
      $rubric_connections = array(CS_GROUP_TYPE);
      if ($context_item->withRubric(CS_TOPIC_TYPE) ){
         $rubric_connections[] = CS_TOPIC_TYPE;
      }
   }
   $rubric_connections[] = CS_MATERIAL_TYPE;
   $detail_view->setRubricConnections($rubric_connections);

   $annotations = $todo_item->getAnnotationList();
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
}
?>