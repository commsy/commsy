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

// Verify parameters for this page
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('An announcement item id must be given.', E_USER_ERROR);
}

//used to signal which "creator infos" of annotations are expanded...
$creatorInfoStatus = array();
if (!empty($_GET['creator_info_max'])) {
  $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
}

include_once('include/inc_delete_entry.php');

$item_manager = $environment->getItemManager();
$type = $item_manager->getItemType($_GET['iid']);
if ($type != CS_ANNOUNCEMENT_TYPE) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
} else {
   // initialize objects
   $announcement_manager = $environment->getAnnouncementManager();
   $current_context = $environment->getCurrentContextItem();

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $current_context->isOpen();
   $params['creator_info_status'] = $creatorInfoStatus;
   $detail_view = $class_factory->getClass(ANNOUNCEMENT_DETAIL_VIEW,$params);
   unset($params);

   // set the view's item
   $announcement_item = $announcement_manager->getItem($current_item_id);
   $current_user = $environment->getCurrentUser();
   if ( !isset($announcement_item) ) {
      include_once('functions/error_functions.php');
      trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $announcement_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$announcement_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {
      // Get clipboard
      if ( $session->issetValue('announcement_clipboard') ) {
         $clipboard_id_array = $session->getValue('announcement_clipboard');
      } else {
         $clipboard_id_array = array();
      }

      // Copy to clipboard
      if ( isset($_GET['add_to_announcement_clipboard'])
           and !in_array($current_item_id, $clipboard_id_array) ) {
         $clipboard_id_array[] = $current_item_id;
         $session->setValue('announcement_clipboard', $clipboard_id_array);
      }

      $detail_view->setItem($announcement_item);
      $detail_view->setClipboardIDArray($clipboard_id_array);

      //Set Read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($announcement_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $announcement_item->getModificationDate() ) {
         $reader_manager->markRead($announcement_item->getItemID(),0);
      }

      //Set Noticed
      $noticed_manager = $environment->getNoticedManager();
      $noticed = $noticed_manager->getLatestNoticed($announcement_item->getItemID());
      if ( empty($noticed) or $noticed['read_date'] < $announcement_item->getModificationDate() ) {
         $noticed_manager->markNoticed($announcement_item->getItemID(),0);
      }

      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids')) {
         $announcement_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids');
      } else {
         $announcement_ids = array();
      }

      $detail_view->setBrowseIDs($announcement_ids);

      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules =  $default_room_modules;
      }
      $first = array();
      $second = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' and $link_name[0] !=$_GET['mod']) {
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
            $ids = $announcement_item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }

      $detail_view->setRubricConnections($announcement_item);

      $annotations = $announcement_item->getAnnotationList();
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
      while ($annotation ) {
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

		 // assessment
		 $current_context = $environment->getCurrentContextItem();
         if($current_context->isAssessmentActive()) {
			$assessment_manager = $environment->getAssessmentManager();
			$assessment = $assessment_manager->getAssessmentForItemAverage($announcement_item);
			$voted = $assessment_manager->hasCurrentUserAlreadyVoted($announcement_item);
			$own_vote = $assessment_manager->getAssessmentForItemOwn($announcement_item);
			$detail = $assessment_manager->getAssessmentForItemDetail($announcement_item);
			unset($assessment_manager);
			if($assessment !== '') {
				$detail_view->setAssessment($assessment[0], $assessment[1], $voted, $own_vote, $detail);
			}
		 }


      $page->add($detail_view);
   }
}
?>