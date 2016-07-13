<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

$site_room_type = CS_MYROOM_TYPE;
include_once('classes/cs_mail.php');
// Verify parameters for this page

if ( isset($_GET['delete_room_id']) and !empty($_GET['delete_room_id']) ){
   $manager = $environment->getMyRoomManager();
   $room_item =  $manager->getItem($_GET['delete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setNotShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   $params = $environment->getCurrentParameterArray();
   unset($params['delete_room_id']);
   redirect($environment->getCurrentContextID(),'myroom','detail',$params);
   unset($params);
} elseif ( isset($_GET['undelete_room_id']) and !empty($_GET['undelete_room_id']) ){
   $manager = $environment->getMyRoomManager();
   $room_item =  $manager->getItem($_GET['undelete_room_id']);
   if ( !empty($room_item) ){
      $user = $environment->getCurrentUserItem();
      $room_item->setShownInPrivateRoomHome($user->getUserID());
      $room_item->save();
   }
   $params = $environment->getCurrentParameterArray();
   unset($params['undelete_room_id']);
   redirect($environment->getCurrentContextID(),'myroom','detail',$params);
   unset($params);
}


if (!empty($_GET['account'])) {
   $account_mode = $_GET['account'];
} else {
   $account_mode = 'none';
}

if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];

   include_once('include/inc_delete_entry.php');
      // redirect user into room, if s/he is member allready
   if ($account_mode == 'member') {
      $current_user = $environment->getCurrentUserItem();
      if (isset($current_user) and $current_user->getUserID() != 'guest') {
       $room_manager = $environment->getRoomManager();
       $room_item = $room_manager->getItem($current_item_id);
       if (isset($room_item) and $room_item->isUser($current_user)) {
         redirect($current_item_id,'home','index','');
       }
      }
   }
} else {
   include_once('functions/error_functions.php');
   trigger_error('An item id must be given.', E_USER_ERROR);
}

if (isset($_POST['option'])){
   $option = $_POST['option'];
} else {
   $option= 'none';
}
// get translation object
$translator = $environment->getTranslationObject();

if (isOption($option, $translator->getMessage('CONTACT_MAIL_SEND_BUTTON'))){
   $params['iid']= $current_item_id;
   $user_manager = $environment->getUserManager();
   $user_item = $environment->getCurrentUserItem();
   $project_manager = $environment->getProjectManager();
   $room_item = $project_manager->getItem($current_item_id);
   $user_list = $room_item->getContactModeratorList();
   $email_addresses = array();
   $moderator_item = $user_list->getFirst();
   $recipients = '';
   while ($moderator_item) {
      $email_addresses[] = $moderator_item->getEmail();
      $recipients .= $moderator_item->getFullname().LF;
      $moderator_item = $user_list->getNext();
   }

   // language
   $language = $room_item->getLanguage();
   if ($language == 'user') {
      $language = $user_item->getLanguage();
      if ($language == 'browser') {
         $language = $environment->getSelectedLanguage();
      }
   }

   if (count($email_addresses) > 0) {
      $save_language = $translator->getSelectedLanguage();
      $translator->setSelectedLanguage($language);
      $subject = $translator->getMessage('USER_ASK_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
      $body  = '';
      if (!empty($_POST['description_user'])) {
          $body .= $_POST['description_user'];
          $body .= LF.LF;
        $body .= '---'.LF;
      }
      $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
      $body .= LF;
      $mail = new cs_mail();
      $mail->set_to(implode(',',$email_addresses));
      //$mail->set_from_email($user_item->getEmail());
      //$mail->set_from_name($user_item->getFullname());
      $mail->set_from_email($environment->getServerItem()->getDefaultSenderAddress());
      $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
      $mail->set_reply_to_name($user_item->getFullname());
      $mail->set_reply_to_email($user_item->getEmail());
      $mail->set_subject($subject);
      $mail->set_message($body);
      $mail->send();
      $translator->setSelectedLanguage($save_language);
   }

   if ( $environment->getCurrentModule() == CS_PROJECT_TYPE ) {
      redirect($environment->getCurrentContextID(), CS_PROJECT_TYPE, 'detail', $params);
   } elseif ( $environment->getCurrentModule() == CS_MYROOM_TYPE ) {
      redirect($environment->getCurrentContextID(), CS_MYROOM_TYPE, 'detail', $params);
   }
}

//used to signal which "creator infos" of annotations are expanded...
$creatorInfoStatus = array();
if (!empty($_GET['creator_info_max'])) {
  $creatorInfoStatus = explode('-',$_GET['creator_info_max']);
}

// initialize objects
$manager = $environment->getManager($site_room_type);
$item = $manager->getItem($current_item_id);

if ( !isset($item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ERROR_ILLEGAL_IID'));
   $page->add($errorbox);
} elseif ( $item->isDeleted() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
   $page->add($errorbox);
} elseif ( !$item->maySee($current_user) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
} else {
   //is current room open?
   $context_item = $environment->getCurrentContextItem();
   $room_open = $context_item->isOpen();
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = $context_item->isOpen();
   $params['creator_info_status'] = $creatorInfoStatus;
   if ($site_room_type == CS_PROJECT_TYPE) {
      $detail_view = $class_factory->getClass(PROJECT_DETAIL_VIEW,$params);
   } elseif ($site_room_type == CS_MYROOM_TYPE) {
      $detail_view = $class_factory->getClass(MYROOM_DETAIL_VIEW,$params);
   } elseif ($site_room_type == CS_COMMUNITY_TYPE) {
      $detail_view = $class_factory->getClass(COMMUNITY_DETAIL_VIEW,$params);
   }
   unset($params);

   //set account mode
   $detail_view->setAccountMode($account_mode);

   // set the view's item
   $detail_view->setItem($item);

   //Set Read
   $reader_manager = $environment->getReaderManager();
   $reader = $reader_manager->getLatestReader($item->getItemID());
   if ( empty($reader) or $reader['read_date'] < $item->getModificationDate() ) {
      $reader_manager->markRead($item->getItemID(),0);
   }

   // set up browsing
   if ($session->issetValue('cid'.$environment->getCurrentContextID().'_'.$site_room_type.'_index_ids')) {
      $ids = $session->getValue('cid'.$environment->getCurrentContextID().'_'.$site_room_type.'_index_ids');
   } else {
      $ids = array();
   }
   $detail_view->setBrowseIDs($ids);

   $context_type = $context_item->getType();
   if ($context_type == CS_PORTAL_TYPE and $site_room_type == CS_PROJECT_TYPE) {
      // set up ids of linked items
      $community_ids = $item->getLinkedItemIDArray(CS_COMMUNITY_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_community_index_ids', $community_ids);
      $rubric_connections = array();
      $rubric_connections[] = CS_COMMUNITY_TYPE;
      $detail_view->setRubricConnections($rubric_connections);
   }
   elseif ($context_type == CS_PORTAL_TYPE and $site_room_type == CS_COMMUNITY_TYPE) {
      // set up ids of linked items
      $project_ids = $item->getLinkedItemIDArray(CS_PROJECT_TYPE);
      $session->setValue('cid'.$environment->getCurrentContextID().'_project_index_ids', $project_ids);
      $rubric_connections = array();
      $rubric_connections[] = CS_PROJECT_TYPE;
      $detail_view->setRubricConnections($rubric_connections);
   }
   elseif ($context_type == CS_COMMUNITY_TYPE and $site_room_type == CS_PROJECT_TYPE) {
      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules = array();
      }
      $first = array();
      $secon = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
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
         if ($context_item->withRubric($module) and $module !=CS_PROJECT_TYPE) {
            $ids = $item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }
      $detail_view->setRubricConnections($rubric_connections);
   }elseif( $environment->inPrivateRoom() ){
      $context_item = $environment->getCurrentContextItem();
      $current_room_modules = $context_item->getHomeConf();
      if ( !empty($current_room_modules) ){
         $room_modules = explode(',',$current_room_modules);
      } else {
         $room_modules = array();
      }
      $first = array();
      $secon = array();
      foreach ( $room_modules as $module ) {
         $link_name = explode('_', $module);
         if ( $link_name[1] != 'none' ) {
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
         if ($context_item->withRubric($module) and $module !=CS_MYROOM_TYPE) {
            $ids = $item->getLinkedItemIDArray($module);
            $session->setValue('cid'.$environment->getCurrentContextID().'_'.$module.'_index_ids', $ids);
            $rubric_connections[] = $module;
         }
      }
      $detail_view->setRubricConnections($rubric_connections);
   }

   if (isset($_GET['mode']) and ($_GET['mode'] == 'print')) {
      $detail_view->_shown_as_printable = true;
   }

   $annotations = $item->getAnnotationList();
   $detail_view->setAnnotationList($item->getAnnotationList());

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