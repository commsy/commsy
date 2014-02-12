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


$home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;
$new_private_room = $environment->inConfigArray('c_use_new_private_room',$environment->getCurrentContextID());
if ($new_private_room){
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

if ( $context_item->isProjectroom()
           or $context_item->isCommunityRoom()
           or $context_item->isPrivateRoom()
           or $context_item->isGroupRoom()
         ) {
   $session_item = $environment->getSessionItem();
   $history = $session_item->getValue('history');
   if ( isset($history[0]['context']) ) {
      if ( $history[0]['context'] != $environment->getCurrentContextID() ) {
         $history_context_id = $history[0]['context'];
         $manager = $environment->getItemManager();
         $history_context_item = $manager->getItem($history_context_id);
         $type = $history_context_item->getItemType();
         $item_manager = $environment->getManager($type);
         $history_context_item = $item_manager->getItem($history_context_id);
         if ( !isset($history_context_item)
              and !$environment->isArchiveMode()
            ) {
            $environment->activateArchiveMode();
            $item_manager2 = $environment->getManager($type);
            $history_context_item = $item_manager2->getItem($history_context_id);
            unset($item_manager2);
            $environment->deactivateArchiveMode();
         }
         if ( isset($history_context_item)
              and $history_context_item->isPortal()
            ) {
            $session_item->setValue('leave_home_context','portal');
         } else {
            $session_item->setValue('leave_home_context','community');
            $session_item->setValue('leave_home_context_iid',$history_context_id);
            $session_item->setValue('leave_home_context_room_id',$environment->getCurrentContextID());
         }
      }
   }

   if (!$context_item->isPrivateRoom()){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $title_view = $class_factory->getClass(HOME_TITLE_VIEW,$params);
      unset($params);
      $page->add($title_view);
   }

   $conf = $context_item->getHomeConf();
   if ( !empty($conf) ) {
      $rubrics = explode(',', $conf);
   } else {
      $rubrics = array();
   }

   $context_user = $environment->getCurrentUserItem();
   array_unshift($rubrics, 'activity_short');
   if ( $context_item->isProjectroom()
        or $context_item->isCommunityRoom()
        or $context_item->isGroupRoom()
      ) {

      include_once('pages/activity_short.php');
      $id_array = array();
      $v_id_array = array();
      $sub_id_array = array();
      $disc_id_array = array();

      if ($context_item->withInformationBox()){
         $id = $current_context->getInformationBoxEntryID();
         $manager = $environment->getItemManager();
         $item = $manager->getItem($id);
         $entry_manager = $environment->getManager($item->getItemType());
         $entry = $entry_manager->getItem($id);
         if($entry->isNotActivated() != '1'){
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $information_view = $class_factory->getClass(HOME_INFORMATIONBOX_VIEW,$params);
            unset($params);
            $page->addLeft($information_view);
         }
      }
      foreach ( $rubrics as $rubric ) {
         $rubric_array = explode('_', $rubric);
         if ( $rubric_array[1] != 'none' and  $rubric_array[1] != 'nodisplay') {
            if ( $rubric_array[0] != 'activity') {
               $list = new cs_list();
               $rubric = '';
               $param_class_array = array();
               $param_class_array['environment'] = $environment;
               $param_class_array['with_modifying_actions'] = $context_item->isOpen();
               switch ($rubric_array[0]){
                  case CS_ANNOUNCEMENT_TYPE:
                        $short_list_view = $class_factory->getClass(ANNOUNCEMENT_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getAnnouncementManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setDateLimit(getCurrentDateTimeInMySQL());
                        $manager->setSortOrder('modified');
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_DATE_TYPE:
                        $short_list_view = $class_factory->getClass(DATE_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getDatesManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->setDateModeLimit(2);
                        $count_all = $manager->getCountAll();
                        $manager->setFutureLimit();
                        $manager->setDateModeLimit(3);
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $rubric = 'dates';
                     break;
                  case CS_PROJECT_TYPE:
                        $room_type = CS_PROJECT_TYPE;
                        $short_list_view = $class_factory->getClass(PROJECT_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getProjectManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentPortalID());
                        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr  ) {
                           $manager->setCommunityRoomLimit($environment->getCurrentContextID());
                        } else {
                           # use redundant infos in community room
                           $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
                        }
                        $count_all = $manager->getCountAll();
                        $manager->setSortOrder('activity_rev');
                        if ( $interval > 0 ) {
                           $manager->setIntervalLimit(0,5);
                        }
                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_GROUP_TYPE:
                        $short_list_view = $class_factory->getClass(GROUP_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getGroupManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_TODO_TYPE:
                        $short_list_view = $class_factory->getClass(TODO_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getTodoManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setStatusLimit(4);
                        $manager->setSortOrder('date');
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $tmp_id_array = array();
                        while ($item){
                           $tmp_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $step_manager = $environment->getStepManager();
                        $step_list = $step_manager->getAllStepItemListByIDArray($tmp_id_array);
                        $item = $step_list->getFirst();
                        while ($item){
                           $sub_id_array[] = $item->getItemID();
                           $item = $step_list->getNext();
                        }
                        unset($step_list);
                        unset($step_manager);
                        unset($manager);
                        break;
                  case CS_TOPIC_TYPE:
                        $short_list_view = $class_factory->getClass(TOPIC_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getTopicManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_INSTITUTION_TYPE:
                        $short_list_view = $class_factory->getClass(INSTITUTION_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getInstitutionManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_USER_TYPE:
                        $short_list_view = $class_factory->getClass(USER_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getUserManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->setUserLimit();
                        $count_all = $manager->getCountAll();
                        if (!$current_user->isGuest()){
                           $manager->setVisibleToAllAndCommsy();
                        } else {
                           $manager->setVisibleToAll();
                        }
                        $manager->setAgeLimit($context_item->getTimeSpread());
                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_MATERIAL_TYPE:
                        $short_list_view = $class_factory->getClass(MATERIAL_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getMaterialManager();
                        $manager->reset();
                        $manager->create_tmp_table($environment->getCurrentContextID());
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setOrder('date');
                        if ($environment->inProjectRoom()){
                           $manager->setAgeLimit($context_item->getTimeSpread());
                        } else {
                           $manager->setIntervalLimit(0,5);
                           $home_rubric_limit = 5;
                        }
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $manager->delete_tmp_table();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $tmp_id_array = array();
                        while ($item){
                           $tmp_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $section_manager = $environment->getSectionManager();
                        $section_list = $section_manager->getAllSectionItemListByIDArray($tmp_id_array);
                        $item = $section_list->getFirst();
                        while ($item){
                           $sub_id_array[] = $item->getItemID();
                           $v_id_array[$item->getItemID()] = $item->getVersionID();
                           $item = $section_list->getNext();
                        }
                     break;
                  case CS_DISCUSSION_TYPE:
                        $short_list_view = $class_factory->getClass(DISCUSSION_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getDiscussionManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
                           $manager->setAgeLimit($context_item->getTimeSpread());
                        } elseif ($environment->inCommunityRoom()) {
                           $manager->setIntervalLimit(0,5);
                           $home_rubric_limit = 5;
                        }
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $disc_id_array = array();
                        while ($item){
                           $disc_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $discarticle_manager = $environment->getDiscussionArticleManager();
                        $discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
                        $item = $discarticle_list->getFirst();
                        while ($item){
                           $disc_id_array[] = $item->getItemID();
                           $item = $discarticle_list->getNext();
                        }
                     break;
                  }
               unset($param_class_array);
               $item = $list->getFirst();
               $ids = array();
               while ($item){
                  $id_array[] = $item->getItemID();
                  if ($rubric_array[0] == CS_MATERIAL_TYPE){
                     $v_id_array[$item->getItemID()] = $item->getVersionID();
                  }
                  $ids[] = $item->getItemID();
                  $item = $list->getNext();
               }
               if (empty($rubric)){
                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_array[0].'_index_ids', $ids);
               }else{
                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
               }
               $page->addLeft($short_list_view);
            }
         }
      }
      $noticed_manager = $environment->getNoticedManager();
      $id_array = array_merge($id_array, $disc_id_array);
      $noticed_manager->getLatestNoticedByIDArray($id_array);
      $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
      $id_array = array_merge($id_array, $sub_id_array);
      $link_manager = $environment->getLinkManager();
      $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
      $file_manager = $environment->getFileManager();
      $file_manager->setIDArrayLimit($file_id_array);
      $file_manager->select();

      if ( $current_context->withBuzzwords() ){
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $context_item->isOpen();
         $buzzword_view = $class_factory->getClass(HOME_BUZZWORD_VIEW,$params);
         unset($params);
         $page->addRight($buzzword_view);
      }
      if ( $current_context->withTags() ){
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $context_item->isOpen();
         $tag_view = $class_factory->getClass(HOME_TAG_VIEW,$params);
         unset($params);
         $page->addRight($tag_view);
      }
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $usage_info_view = $class_factory->getClass(HOME_USAGEINFO_VIEW,$params);
      unset($params);
      $page->addRight($usage_info_view);
   } else {
 /*     if ($context_item->withInformationBox()){
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $context_item->isOpen();
         $information_view = $class_factory->getClass(HOME_INFORMATIONBOX_VIEW,$params);
         unset($params);
         $page->addLeft($information_view);
      }*/
      include('pages/private_room_short.php');
   }


}elseif ( $context_item->isServer() or $context_item->isPortal() ) {
   $filename = 'external_pages/'.$context_item->getItemID().'/home_index_guide.php';
   if (file_exists  ($filename)){
      include_once($filename);
   }else{
      include_once('pages/home_index_guide.php');
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('no context',E_USER_ERROR);
}




/**************/
/* Alter Code */
/**************/

}else{

$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

if ( $context_item->isProjectroom()
     or $context_item->isCommunityRoom()
     or $context_item->isPrivateRoom()
     or $context_item->isGroupRoom()
   ) {
   $session_item = $environment->getSessionItem();
   $history = $session_item->getValue('history');
   if ( isset($history[0]['context']) ) {
      if ( $history[0]['context'] != $environment->getCurrentContextID() ) {
         $history_context_id = $history[0]['context'];
         $manager = $environment->getItemManager();
         $history_context_item = $manager->getItem($history_context_id);
         $type = $history_context_item->getItemType();
         $item_manager = $environment->getManager($type);
         $history_context_item = $item_manager->getItem($history_context_id);
         if ( !isset($history_context_item) ) {
            if ( !$environment->isArchiveMode() ) {
               $environment->activateArchiveMode();
               $item_manager2 = $environment->getManager($type);
               $history_context_item = $item_manager2->getItem($history_context_id);
               $environment->deactivateArchiveMode();
               unset($item_manager2);
            } else {
               $environment->deactivateArchiveMode();
               $item_manager2 = $environment->getManager($type);
               $history_context_item = $item_manager2->getItem($history_context_id);
               $environment->activateArchiveMode();
               unset($item_manager2);
            }
         }
         if ( $history_context_item->isPortal() ) {
            $session_item->setValue('leave_home_context','portal');
         } else {
            $session_item->setValue('leave_home_context','community');
            $session_item->setValue('leave_home_context_iid',$history_context_id);
            $session_item->setValue('leave_home_context_room_id',$environment->getCurrentContextID());
         }
      }
   }

   if (!$context_item->isPrivateRoom()){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $title_view = $class_factory->getClass(HOME_TITLE_VIEW,$params);
      unset($params);
      $page->add($title_view);
   }

   $conf = $context_item->getHomeConf();
   if ( !empty($conf) ) {
      $rubrics = explode(',', $conf);
   } else {
      $rubrics = array();
   }

   $context_user = $environment->getCurrentUserItem();
   array_unshift($rubrics, 'activity_short');
   if ( $context_item->isProjectroom()
        or $context_item->isCommunityRoom()
        or $context_item->isGroupRoom()
      ) {

      include_once('pages/activity_short.php');
      $id_array = array();
      $v_id_array = array();
      $sub_id_array = array();
      $disc_id_array = array();

      if ($context_item->withInformationBox()){
         $id = $current_context->getInformationBoxEntryID();
         $manager = $environment->getItemManager();
         $item = $manager->getItem($id);
         $entry_manager = $environment->getManager($item->getItemType());
         $entry = $entry_manager->getItem($id);
         if($entry->isNotActivated() != '1'){
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = $context_item->isOpen();
            $information_view = $class_factory->getClass(HOME_INFORMATIONBOX_VIEW,$params);
            unset($params);
            $page->addLeft($information_view);
         }
      }

      foreach ( $rubrics as $rubric ) {
         $rubric_array = explode('_', $rubric);
         if ( $rubric_array[1] != 'none' and  $rubric_array[1] != 'nodisplay') {
            if ( $rubric_array[0] != 'activity') {
               $list = new cs_list();
               $rubric = '';
               $param_class_array = array();
               $param_class_array['environment'] = $environment;
               $param_class_array['with_modifying_actions'] = $context_item->isOpen();
               switch ($rubric_array[0]){
                  case CS_ANNOUNCEMENT_TYPE:
                        $short_list_view = $class_factory->getClass(ANNOUNCEMENT_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getAnnouncementManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setDateLimit(getCurrentDateTimeInMySQL());
                        $manager->setSortOrder('modified');
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_DATE_TYPE:
                        $short_list_view = $class_factory->getClass(DATE_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getDatesManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->setDateModeLimit(2);
                        $count_all = $manager->getCountAll();
                        $manager->setFutureLimit();
                        $manager->setDateModeLimit(3);
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $rubric = 'dates';
                     break;
                  case CS_PROJECT_TYPE:
                        $room_type = CS_PROJECT_TYPE;
                        $short_list_view = $class_factory->getClass(PROJECT_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getProjectManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentPortalID());
                        if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr  ) {
                           $manager->setCommunityRoomLimit($environment->getCurrentContextID());
                        } else {
                           # use redundant infos in community room
                           $manager->setIDArrayLimit($context_item->getInternalProjectIDArray());
                        }
                        $count_all = $manager->getCountAll();
                        $manager->setSortOrder('activity_rev');
                        if ( $interval > 0 ) {
                           $manager->setIntervalLimit(0,5);
                        }
                        $manager->setQueryWithoutExtra();
                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_GROUP_TYPE:
                        $short_list_view = $class_factory->getClass(GROUP_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getGroupManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_TODO_TYPE:
                        $short_list_view = $class_factory->getClass(TODO_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getTodoManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setStatusLimit(4);
                        $manager->setSortOrder('date');
                        $manager->showNoNotActivatedEntries();
                        $manager->select();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $tmp_id_array = array();
                        while ($item){
                           $tmp_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $step_manager = $environment->getStepManager();
                        $step_list = $step_manager->getAllStepItemListByIDArray($tmp_id_array);
                        $item = $step_list->getFirst();
                        while ($item){
                           $sub_id_array[] = $item->getItemID();
                           $item = $step_list->getNext();
                        }
                        unset($step_list);
                        unset($step_manager);
                        unset($manager);
                        break;
                  case CS_TOPIC_TYPE:
                        $short_list_view = $class_factory->getClass(TOPIC_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getTopicManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_INSTITUTION_TYPE:
                        $short_list_view = $class_factory->getClass(INSTITUTION_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getInstitutionManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->select();
                        $list = $manager->get();
                        $count_all = $list->getCount();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_USER_TYPE:
                        $short_list_view = $class_factory->getClass(USER_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getUserManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $manager->setUserLimit();
                        $count_all = $manager->getCountAll();
                        if (!$current_user->isGuest()){
                           $manager->setVisibleToAllAndCommsy();
                        } else {
                           $manager->setVisibleToAll();
                        }
                        $manager->setAgeLimit($context_item->getTimeSpread());
                        $manager->select();
                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                     break;
                  case CS_MATERIAL_TYPE:
                        $short_list_view = $class_factory->getClass(MATERIAL_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getMaterialManager();
                        $manager->reset();
                        $manager->create_tmp_table($environment->getCurrentContextID());
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        $manager->setOrder('date');
                        if ($environment->inProjectRoom()){
                           $manager->setAgeLimit($context_item->getTimeSpread());
                        } else {
                           $manager->setIntervalLimit(0,5);
                           $home_rubric_limit = 5;
                        }
                        $manager->showNoNotActivatedEntries();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $manager->select();
                        $list = $manager->get();
                        $manager->delete_tmp_table();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $tmp_id_array = array();
                        while ($item){
                           $tmp_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $section_manager = $environment->getSectionManager();
                        $section_list = $section_manager->getAllSectionItemListByIDArray($tmp_id_array);
                        $item = $section_list->getFirst();
                        while ($item){
                           $sub_id_array[] = $item->getItemID();
                           $v_id_array[$item->getItemID()] = $item->getVersionID();
                           $item = $section_list->getNext();
                        }
                     break;
                  case CS_DISCUSSION_TYPE:
                        $short_list_view = $class_factory->getClass(DISCUSSION_SHORT_VIEW,$param_class_array);
                        $manager = $environment->getDiscussionManager();
                        $manager->reset();
                        $manager->setContextLimit($environment->getCurrentContextID());
                        $count_all = $manager->getCountAll();
                        if ($environment->inProjectRoom() or $environment->inGroupRoom() ) {
                           $manager->setAgeLimit($context_item->getTimeSpread());
                        } elseif ($environment->inCommunityRoom()) {
                           $manager->setIntervalLimit(0,5);
                           $home_rubric_limit = 5;
                        }
                        $manager->showNoNotActivatedEntries();
                        $manager->select();

                        $count_select = $manager->getCountAll();
                        $manager->setIntervalLimit(0, $home_rubric_limit);
                        $home_rubric_limit = CS_HOME_RUBRIC_LIST_LIMIT;

                        if($home_rubric_limit < $count_select) $short_list_view->setListShortened(true);

                        $list = $manager->get();
                        $short_list_view->setList($list);
                        $short_list_view->setCountAll($count_all);
                        $item = $list->getFirst();
                        $disc_id_array = array();
                        while ($item){
                           $disc_id_array[] = $item->getItemID();
                           $item = $list->getNext();
                        }
                        $discarticle_manager = $environment->getDiscussionArticleManager();
                        $discarticle_list = $discarticle_manager->getAllDiscArticlesItemListByIDArray($disc_id_array);
                        $item = $discarticle_list->getFirst();
                        while ($item){
                           $disc_id_array[] = $item->getItemID();
                           $item = $discarticle_list->getNext();
                        }
                     break;
                  }
               unset($param_class_array);
               $item = $list->getFirst();
               $ids = array();
               while ($item){
                  $id_array[] = $item->getItemID();
                  if ($rubric_array[0] == CS_MATERIAL_TYPE){
                     $v_id_array[$item->getItemID()] = $item->getVersionID();
                  }
                  $ids[] = $item->getItemID();
                  $item = $list->getNext();
               }
               if (empty($rubric)){
                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric_array[0].'_index_ids', $ids);
               }else{
                  $session->setValue('cid'.$environment->getCurrentContextID().'_'.$rubric.'_index_ids', $ids);
               }
               $page->addLeft($short_list_view);
            }
         }
      }
      $noticed_manager = $environment->getNoticedManager();
      $id_array = array_merge($id_array, $disc_id_array);
      $noticed_manager->getLatestNoticedByIDArray($id_array);
      $noticed_manager->getLatestNoticedAnnotationsByIDArray($id_array);
      $id_array = array_merge($id_array, $sub_id_array);
      $link_manager = $environment->getLinkManager();
      $file_id_array = $link_manager->getAllFileLinksForListByIDs($id_array, $v_id_array);
      $file_manager = $environment->getFileManager();
      $file_manager->setIDArrayLimit($file_id_array);
      $file_manager->select();
   } else {
      if ($context_item->withInformationBox()){
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = $context_item->isOpen();
         $information_view = $class_factory->getClass(HOME_INFORMATIONBOX_VIEW,$params);
         unset($params);
         $page->addLeft($information_view);
      }
      include('pages/private_room_short.php');
   }

   if ( $current_context->withBuzzwords() ){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $buzzword_view = $class_factory->getClass(HOME_BUZZWORD_VIEW,$params);
      unset($params);
      $page->addRight($buzzword_view);
   }
   if ( $current_context->withTags() ){
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $tag_view = $class_factory->getClass(HOME_TAG_VIEW,$params);
      unset($params);
      $page->addRight($tag_view);
   }
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $context_item->isOpen();
   $usage_info_view = $class_factory->getClass(HOME_USAGEINFO_VIEW,$params);
   unset($params);
   $page->addRight($usage_info_view);

}elseif ( $context_item->isServer() or $context_item->isPortal() ) {
   $filename = 'external_pages/'.$context_item->getItemID().'/home_index_guide.php';
   if (file_exists  ($filename)){
      include_once($filename);
   }else{
      include_once('pages/home_index_guide.php');
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('no context',E_USER_ERROR);
}

}
?>