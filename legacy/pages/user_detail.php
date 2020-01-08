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
   trigger_error('A user item id must be given.', E_USER_ERROR);
}

// Get the translator object
$translator = $environment->getTranslationObject();

$item_manager = $environment->getItemManager();
$type = $item_manager->getItemType($_GET['iid']);
if ($type != CS_USER_TYPE) {
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

   // Load the shown user
   $user_manager = $environment->getUserManager();
   $user_item = $user_manager->getItem($current_item_id);
   $current_user = $environment->getCurrentUser();
   $current_module = $environment->getCurrentModule();

   if ( !isset($user_item) ) {
      include_once('functions/error_functions.php');
      trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $user_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$user_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } elseif ( ( $current_user->isRoot()
                or $current_user->isModerator()
              )
              and $environment->inPortal()
              and isset($_GET['mode'])
              and $_GET['mode'] == 'take_over'
   			  and (!$current_user->isDeactivatedLoginAsAnotherUser() 
   			  			or $current_user->isTemporaryAllowedToLoginAs())
            ) {
      $history = $session->getValue('history');
      $cookie = $session->getValue('cookie');
      $javascript = $session->getValue('javascript');
      $https = $session->getValue('https');
      $flash = $session->getValue('flash');
      $session_id = $session->getSessionID();
      $session = new cs_session_item();
      $session->createSessionID($user_item->getUserID());
      $session->setValue('auth_source',$user_item->getAuthSource());
      $session->setValue('root_session_id',$session_id);
      if ( $cookie == '1' ) {
         $session->setValue('cookie',2);
      } elseif ( empty($cookie) ) {
         // do nothing, so CommSy will try to save cookie
      } else {
         $session->setValue('cookie',0);
      }
      if ($javascript == '1') {
         $session->setValue('javascript',1);
      } elseif ($javascript == '-1') {
         $session->setValue('javascript',-1);
      }
      if ($https == '1') {
         $session->setValue('https',1);
      } elseif ($https == '-1') {
         $session->setValue('https',-1);
      }
      if ($flash == '1') {
         $session->setValue('flash',1);
      } elseif ($flash == '-1') {
         $session->setValue('flash',-1);
      }

      // save portal id in session to be sure, that user didn't
      // switch between portals
      if ( $environment->inServer() ) {
         $session->setValue('commsy_id',$environment->getServerID());
      } else {
         $session->setValue('commsy_id',$environment->getCurrentPortalID());
      }
      $environment->setSessionItem($session);
      redirect($environment->getCurrentContextID(),'home','index',array());

   } else if ($current_user->isRoot() 
   				and $environment->inPortal()
   				and isset($_GET['mode'])
              	and $_GET['mode'] == 'deactivateLoginAs') {
   	  
   	  if($user_item->isDeactivatedLoginAsAnotherUser()){
   	  	 $user_item->unsetDeactivateLoginAsAnotherUser();
   	  } else {
   	  	 $user_item->deactivateLoginAsAnotherUser();
   	  }
   	  $user_item->save();
   	  redirect($environment->getCurrentContextID(),'account','detail',array('iid' => $current_item_id));
   } else if ($environment->inPortal()
         and isset($_GET['mode'])
         and $_GET['mode'] == 'hideMailDefault') {

      $user_item->setDefaultMailNotVisible();
      $user_item->save();

      redirect($environment->getCurrentContextID(),'account','detail',array('iid' => $current_item_id));
   } else if ($environment->inPortal()
         and isset($_GET['mode'])
         and $_GET['mode'] == 'hideMailAllRooms') {

      $portal_user_item = $user_item->getRelatedPortalUserItem();
      $portal_user_item->setDefaultMailNotVisible();
      $portal_user_item->save();

      $user_list = $user_item->getRelatedUserList();

      $user_item = $user_list->getFirst();
      while($user_item) {
         $user_item->setEmailNotVisible();
         $user_item->save();
         $user_item = $user_list->getNext();
      }

      redirect($environment->getCurrentContextID(),'account','detail',array('iid' => $current_item_id));
   } else if ($environment->inPortal()
         and isset($_GET['mode'])
         and $_GET['mode'] == 'showMailDefault') {


      $user_item->setDefaultMailVisible();
      $user_item->save();

      redirect($environment->getCurrentContextID(),'account','detail',array('iid' => $current_item_id));
   } else if ($environment->inPortal()
         and isset($_GET['mode'])
         and $_GET['mode'] == 'showMailAllRooms') {

      $portal_user_item = $user_item->getRelatedPortalUserItem();
      $portal_user_item->setDefaultMailVisible();
      $portal_user_item->save();

      $user_list = $user_item->getRelatedUserList();

      $user_item = $user_list->getFirst();
      while($user_item) {
         $user_item->setEmailVisible();
         $user_item->save();
         $user_item = $user_list->getNext();
      }

      redirect($environment->getCurrentContextID(),'account','detail',array('iid' => $current_item_id));
   } else {

      // Mark as read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($user_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $user_item->getModificationDate() ) {
         $reader_manager->markRead($user_item->getItemID(), 0);
      }
      //Set Noticed
      $noticed_manager = $environment->getNoticedManager();
      $noticed = $noticed_manager->getLatestNoticed($user_item->getItemID());
      if ( empty($noticed) or $noticed['read_date'] < $user_item->getModificationDate() ) {
         $noticed_manager->markNoticed($user_item->getItemID(),0);
      }

      // Create view
      $current_context = $environment->getCurrentContextItem();

      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $current_context->isOpen();
      $params['creator_info_status'] = $creatorInfoStatus;
      $detail_view = $class_factory->getClass(USER_DETAIL_VIEW,$params);
      unset($params);

      if (isset($display_mod) and $display_mod == 'admin') {
         $detail_view->setDisplayModToAdmin();
      }
      $detail_view->setItem($user_item);
      if ( $user_item->getItemID() == $current_user->getItemID()
           or ( isset($display_mod) and $display_mod == 'admin' and $current_user->isModerator() )
         ) {
         if (!$environment->inPrivateRoom()){
            $detail_view->setSubItem($user_item);
         }
      }

      // Set up browsing order
      if ( !isset($_GET['single'])
           and $session->issetValue('cid'.$environment->getCurrentContextID().'_'.$current_module.'_index_ids')) {
         $user_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$current_module.'_index_ids');
      } else {
         $user_ids = array();
      }
      $detail_view->setBrowseIDs($user_ids);
      if ( isset($_GET['pos']) ) {
         $detail_view->setPosition($_GET['pos']);
      }

      // Set up rubric connections and browsing
      $context_item = $environment->getCurrentContextItem();
      if ( $environment->getCurrentModule() != 'account'
           and ( $context_item->isProjectRoom()
                 or $context_item->isCommunityRoom()
               )
         ) {
         $current_room_modules = $context_item->getHomeConf();
         if ( !empty($current_room_modules) ){
            $room_modules = explode(',',$current_room_modules);
         } else {
            $room_modules = array();
         }
         $first = array();
         $second = array();
         foreach ( $room_modules as $module ) {
            $link_name = explode('_', $module);
            if ( $link_name[1] != 'none' and $link_name[0] != $_GET['mod'] and $link_name[0] != CS_USER_TYPE) {
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
         $room_modules = $first;
         $rubric_connections = array();
         foreach ($room_modules as $module){
            if ($context_item->withRubric($module) ) {
               $ids = $user_item->getLinkedItemIDArray($module);
               $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
               if ($module != CS_TOPIC_TYPE and
                   $module != CS_GROUP_TYPE ){
                   $ids = $user_item->getModifiedItemIDArray($module,$user_item->getItemID());
                   $detail_view->addModifiedItemIDArray($module,$ids);
               }
               $rubric_connections[] = $module;
            }
         }

         $room_modules = $second;
         foreach ($room_modules as $module) {
            if ($context_item->withRubric($module) ) {
               if ( $environment->inPortal()) {
                  $ids = array();
                  if ($module == CS_PROJECT_TYPE) {
                     $room_list = $user_item->getRelatedProjectList();
                  } elseif ($module == CS_COMMUNITY_TYPE) {
                     $room_list = $user_item->getRelatedCommunityList();
                  }
                  if ($room_list->isNotEmpty()) {
                      $room_item = $room_list->getFirst();
                      while ($room_item) {
                         if ($room_item->isOpen()) {
                            $ids[] = $room_item->getItemID();
                         }
                         $room_item = $room_list->getNext();
                      }
                  }
               } else {
                  if ( $module == CS_GROUP_TYPE or $module == CS_TOPIC_TYPE) {
                     $ids = $user_item->getLinkedItemIDArray($module);
                     $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
                  } else {
                     $ids = $user_item->getModifiedItemIDArray($module,$user_item->getItemID());
                  }
               }
               $detail_view->addModifiedItemIDArray($module,$ids);
            }
         }
         $detail_view->setRubricConnections($rubric_connections);
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

      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($detail_view);
      }else{
         $page->add($detail_view);
      }
   }
}
?>