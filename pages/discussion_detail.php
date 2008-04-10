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

// Load required function libraries and class definitions
include_once('classes/cs_discussion_detail_view.php');
include_once('classes/cs_discarticle_form.php');
include_once('classes/cs_form_view.php');

// Verify parameters for this page
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
} else {
   include_once('functions/error_functions.php');trigger_error('A discussion item id must be given.', E_USER_ERROR);
}

include_once('include/inc_delete_entry.php');


$item_manager = $environment->getItemManager();
$type = $item_manager->getItemType($_GET['iid']);
if ($type != CS_DISCUSSION_TYPE) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view($environment, true);
   $errorbox->setText(getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
} else {
	//used to signal which "creator infos" of annotations are expanded...
	$creatorInfoStatus = array();
	if (!empty($_GET['creator_info_max'])) {
	  $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
	}
	// Load the shown item
	$discussion_manager = $environment->getDiscussionManager();
	$discussion_item = $discussion_manager->getItem($current_item_id);
	$current_user = $environment->getCurrentUser();

	if ( !isset($discussion_item) ) {
	   include_once('functions/error_functions.php');trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
	} elseif ( $discussion_item->isDeleted() ) {
            include_once('classes/cs_errorbox_view.php');
	   $errorbox = new cs_errorbox_view($environment, true);
	   $errorbox->setText(getMessage('ITEM_NOT_AVAILABLE'));
	   $page->add($errorbox);
	} elseif ( !$discussion_item->maySee($current_user) ) {
            include_once('classes/cs_errorbox_view.php');
	   $errorbox = new cs_errorbox_view($environment, true);
	   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
	   $page->add($errorbox);
	} else {

	   // Get clipboard
	   if ( $session->issetValue('discussion_clipboard') ) {
	      $clipboard_id_array = $session->getValue('discussion_clipboard');
	   } else {
	      $clipboard_id_array = array();
	   }

	   // Copy to clipboard
	   if ( isset($_GET['add_to_discussion_clipboard'])
	        and !in_array($current_item_id, $clipboard_id_array) ) {
	      $clipboard_id_array[] = $current_item_id;
	      $session->setValue('discussion_clipboard', $clipboard_id_array);
	   }


	   // Load discussion articles
	   $discussionarticles_manager = $environment->getDiscussionArticlesManager();
	   $discussionarticles_manager->setDiscussionLimit($discussion_item->getItemID(),$creatorInfoStatus);
      $discussion_type = $discussion_item->getDiscussionType();
      if ($discussion_type=='threaded'){
	      $discussionarticles_manager->setSortPosition();
      }
      if ( isset($_GET['status']) and $_GET['status'] == 'all_articles' ) {
	      $discussionarticles_manager->setDeleteLimit(false);
	   }
	   $discussionarticles_manager->select();
	   $articles_list = $discussionarticles_manager->get();

	   // Mark as read
	   $reader_manager = $environment->getReaderManager();
	   $reader = $reader_manager->getLatestReader($discussion_item->getItemID());
	   if ( empty($reader) or $reader['read_date'] < $discussion_item->getModificationDate() ) {
	      $reader_manager->markRead($discussion_item->getItemID(), 0);
	   }

	   //Set Noticed
	   $noticed_manager = $environment->getNoticedManager();
	   $noticed = $noticed_manager->getLatestNoticed($discussion_item->getItemID());
	   if ( empty($noticed) or $noticed['read_date'] < $discussion_item->getModificationDate() ) {
	      $noticed_manager->markNoticed($discussion_item->getItemID(),0);
	   }

	   // Create view
	   $context_item = $environment->getCurrentContextItem();
	   $detail_view = new cs_discussion_detail_view($environment, $context_item->isOpen(),$creatorInfoStatus);
	   $detail_view->setClipboardIDArray($clipboard_id_array);
	   $detail_view->setItem($discussion_item);
	   $detail_view->setSubItemList($articles_list);

	   // Set up browsing order
	   if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids') ) {
	      $discussion_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids');
	   } else {
	      $discussion_ids = array();
	   }
	   $detail_view->setBrowseIDs($discussion_ids);
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
	            case CS_INSTITUTION_TYPE:
	            if (empty($first)){
	               $first = CS_INSTITUTION_TYPE;
	            }
	            break;
	         }
	      }
	   }
	   if ($context_item->withRubric(CS_TOPIC_TYPE) ) {
	      $ids = $discussion_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
	      $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
	   }
	   if ( $context_item->withRubric(CS_GROUP_TYPE) ) {
	      $ids = $discussion_item->getLinkedItemIDArray(CS_GROUP_TYPE);
	      $session->setValue('cid'.$environment->getCurrentContextID().'_group_index_ids', $ids);
	   }
	   if ( $context_item->withRubric(CS_INSTITUTION_TYPE) ) {
	      $ids = $discussion_item->getLinkedItemIDArray(CS_INSTITUTION_TYPE);
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
	   } elseif ($first == 'group'){
	      $rubric_connections = array(CS_GROUP_TYPE);
	      if ($context_item->withRubric(CS_TOPIC_TYPE) ){
	         $rubric_connections[] = CS_TOPIC_TYPE;
	      }
	   } elseif ($first == CS_INSTITUTION_TYPE){
	      $rubric_connections = array(CS_INSTITUTION_TYPE);
	      if ($context_item->withRubric(CS_TOPIC_TYPE) ){
	         $rubric_connections[] = CS_TOPIC_TYPE;
	      }
	   }
	   $detail_view->setRubricConnections($rubric_connections);
	   if ( $context_item->withRubric(CS_MATERIAL_TYPE) ) {
	      $detail_view->setSubItemRubricConnections(array(CS_MATERIAL_TYPE));
	   }

	   if ( isset($_GET['status']) and $_GET['status'] == 'all_articles' ) {
	      $detail_view->setShowAllArticles(true);
	   } else {
	       $detail_view->setShowAllArticles(false);
	   }

	   $page->add($detail_view);


	   $mode = '';
	   if (isset($_GET['mode'])) {
		   $mode = $_GET['mode'];
	   }
	}
}
?>