<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Janneck, José Manuel González Vázquez
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
   trigger_error('A material item id must be given.', E_USER_ERROR);
}

if (isset($_GET['version_id'])) {
   $current_version_id = $_GET['version_id'];
   if ( empty($current_version_id) ) {
      $current_version_id = 0;
   }
} else {
   $session->unsetValue('version_index_ids');
}

$item_manager = $environment->getItemManager();
$current_item_iid = $_GET['iid'];
$type = $item_manager->getItemType($_GET['iid']);;

include_once('include/inc_delete_entry.php');

// Get the translator object
$translator = $environment->getTranslationObject();
if ($type != CS_MATERIAL_TYPE) {
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

   // Load the shown item
   $material_manager = $environment->getMaterialManager();
   $material_version_list = $material_manager->getVersionList($current_item_id);
   $material_item = $material_version_list->getFirst();
   $current_user = $environment->getCurrentUser();

   if ( empty($material_item) ) {
      $material_manager->setDeleteLimit(false);
      $item = $material_manager->getItem($current_item_id);
      $material_manager->setDeleteLimit(true);
      if (!empty($item) and $item->isDeleted()) {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
         unset($params);
         $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
         $page->add($errorbox);
      }
   } elseif ($material_item->isNotActivated() and $current_user->getItemID() !=  $material_item->getCreatorID() and !$current_user->isModerator()){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
      $page->add($errorbox);
   } elseif ( !$material_item->maySee($current_user) and !$material_item->mayExternalSee($current_user)) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {
      if(isset($_GET['export_to_wiki'])){
         $wiki_manager = $environment->getWikiManager();
         //$wiki_manager->exportItemToWiki($current_item_iid,CS_MATERIAL_TYPE);
         global $c_use_soap_for_wiki;
         if(!$c_use_soap_for_wiki){
            $wiki_manager->exportItemToWiki($current_item_iid,CS_MATERIAL_TYPE);
         } else {
            $wiki_manager->exportItemToWiki_soap($current_item_iid,CS_MATERIAL_TYPE);
         }
         $params = $environment->getCurrentParameterArray();
         unset($params['export_to_wiki']);
         redirect($environment->getCurrentContextID(),'material', 'detail', $params);
      }

      if(isset($_GET['remove_from_wiki'])){
         $wiki_manager = $environment->getWikiManager();
         global $c_use_soap_for_wiki;
         if($c_use_soap_for_wiki){
            $wiki_manager->removeItemFromWiki_soap($current_item_iid,CS_MATERIAL_TYPE);
         }
         $params = $environment->getCurrentParameterArray();
         unset($params['remove_from_wiki']);
         redirect($environment->getCurrentContextID(),'material', 'detail', $params);
      }
      
     if(isset($_GET['export_to_wordpress'])){
         $wordpress_manager = $environment->getWordpressManager();

         $wordpress_manager->exportItemToWordpress($current_item_iid,CS_MATERIAL_TYPE);
         $params = $environment->getCurrentParameterArray();
         unset($params['export_to_wordpress']);
         redirect($environment->getCurrentContextID(),'material', 'detail', $params);
      }

      if(isset($_GET['workflow_read'])){
         $item_manager->markItemAsWorkflowRead($current_item_iid, $current_user->getItemID());
         $params = $environment->getCurrentParameterArray();
         unset($params['workflow_read']);
         redirect($environment->getCurrentContextID(),'material', 'detail', $params);
      }
      if(isset($_GET['workflow_not_read'])){
         $item_manager->markItemAsWorkflowNotRead($current_item_iid, $current_user->getItemID());
         $params = $environment->getCurrentParameterArray();
         unset($params['workflow_not_read']);
         redirect($environment->getCurrentContextID(),'material', 'detail', $params);
      }
      

      // Get clipboard
      if ( $session->issetValue('material_clipboard') ) {
         $clipboard_id_array = $session->getValue('material_clipboard');
      } else {
         $clipboard_id_array = array();
      }

      // Copy to clipboard
      if ( isset($_GET['add_to_material_clipboard'])
           and !in_array($current_item_id, $clipboard_id_array) ) {
         $clipboard_id_array[] = $current_item_id;
         $session->setValue('material_clipboard', $clipboard_id_array);
      }

      // Make old version current
      if ( isset($_GET['act_version']) ){
         $latest_version_item = $material_version_list->getFirst();
         $old_version_item = $material_version_list->getNext();
         while ( $old_version_item
                 and $_GET['act_version'] != $old_version_item->getVersionID() ) {
            $old_version_item = $material_version_list->getNext();
         }
         $clone_item = $old_version_item->cloneCopy(true);
         $latest_version_id = $latest_version_item->getVersionID();
         $clone_item->setVersionID($latest_version_id+1);
         $clone_item->save();
         $old_version_item->delete();
         $params = array();
         $params['iid'] = $current_item_iid;
         redirect($environment->getCurrentContextID(),
                  'material', 'detail', $params);
      }

      // Delete old version
//      elseif ( isset($_GET['del_version']) ) {
//         $latest_version_item = $material_version_list->getFirst();
//         $old_version_item = $material_version_list->getNext();
//         while ($old_version_item ) {
//            if ( $_GET['del_version'] == $old_version_item->getVersionID() ) {
//               $old_version_item->delete();
//               break;
//            }
//            $old_version_item = $material_version_list->getNext();
//         }
//         $params = array();
//         $params['iid'] = $current_item_iid;
//         redirect($environment->getCurrentContextID(), 'material', 'detail', $params);
//      }

      // Show the material
      else {

         // Mark read
         $material_item = $material_version_list->getFirst();
         $reader_manager = $environment->getReaderManager();
         $reader = $reader_manager->getLatestReader($material_item->getItemID());
         if ( empty($reader) or $reader['read_date'] < $material_item->getModificationDate() ) {
            $reader_manager->markRead($material_item->getItemID(), $material_item->getVersionID());
         }
         //Set Noticed
         $noticed_manager = $environment->getNoticedManager();
         $noticed = $noticed_manager->getLatestNoticed($material_item->getItemID());
         if ( empty($noticed) or $noticed['read_date'] < $material_item->getModificationDate() ) {
            $noticed_manager->markNoticed($material_item->getItemID(),0);
         }

         // Get the context item
         $context_item = $environment->getCurrentContextItem();

         // Initialize the appropriate view
         $latest_version_item = $material_version_list->getFirst();
         if ( isset($current_version_id)
              and $latest_version_item->getVersionID() != $current_version_id ) {
            //Old version
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $detail_view = $class_factory->getClass(MATERIAL_VERSION_DETAIL_VIEW,$params);
            unset($params);
            $detail_view->setVersionList($material_version_list, $current_version_id);
         } else {
            //current version
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $params['creator_info_status'] = $creatorInfoStatus;
            $detail_view = $class_factory->getClass(MATERIAL_DETAIL_VIEW,$params);
            unset($params);
            $detail_view->setVersionList($material_version_list);
            $detail_view->setClipboardIDArray($clipboard_id_array);
         }

         // Set up browsing order
         if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_material_index_ids')) {
            $browse_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_material_index_ids');
         } else {
            $browse_ids = array();
         }
         $detail_view->setBrowseIDs($browse_ids);
         if ( isset($_GET['pos']) ) {
            $detail_view->setPosition($_GET['pos']);
         }

         // Set up rubric connections and browsing

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
            $ids = $material_item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }

      $detail_view->setRubricConnections($rubric_connections);

            // Subitems
            if ($context_item->withRubric(CS_MATERIAL_TYPE) ) {
               $detail_view->setSubItemRubricConnections(array(CS_MATERIAL_TYPE));
            }

            // Set up annotations
            $version_item = $material_version_list->getFirst();
            if ( isset($current_version_id) ) {
               while ( $version_item
                       and $version_item->getVersionID() != $current_version_id ) {
                  $version_item = $material_version_list->getNext();
               }
            }

         if ( !empty($version_item) ) {
            $annotations = $version_item->getAnnotationList();
            $reader_manager = $environment->getReaderManager();
            $noticed_manager = $environment->getNoticedManager();
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
			
			// assessment
			$current_context = $environment->getCurrentContextItem();
         	if($current_context->isAssessmentActive()) {
				$assessment_manager = $environment->getAssessmentManager();
				$assessment = $assessment_manager->getAssessmentForItemAverage($version_item);
				$voted = $assessment_manager->hasCurrentUserAlreadyVoted($version_item);
				$own_vote = $assessment_manager->getAssessmentForItemOwn($version_item);
				$detail = $assessment_manager->getAssessmentForItemDetail($version_item);
				unset($assessment_manager);
				if($assessment !== '') {
					$detail_view->setAssessment($assessment[0], $assessment[1], $voted, $own_vote, $detail);
				}
			}
         }

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
}
?>