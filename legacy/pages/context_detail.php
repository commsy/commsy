<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

include_once('classes/cs_mail.php');
// Verify parameters for this page
if (!empty($_GET['account'])) {
   $account_mode = $_GET['account'];
} else {
   $account_mode = 'none';
}

if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];

   // redirect user into room, if s/he is member allready
   if ( $account_mode == 'member' ) {
      $current_user = $environment->getCurrentUserItem();
      if ( isset($current_user) and $current_user->getUserID() != 'guest' ) {
         $room_manager = $environment->getRoomManager();
         $room_item = $room_manager->getItem($current_item_id);
         if ( isset($room_item) and $room_item->isUser($current_user) ) {
            redirect($current_item_id,'home','index','');
         }
      }
   }
} else {
   include_once('functions/error_functions.php');trigger_error('An item id must be given.', E_USER_ERROR);
}

if ( isset($_GET['action']) and $_GET['action'] == 'delete' ) {
   $params = $environment->getCurrentParameterArray();
   $page->addDeleteBox(curl($environment->getCurrentContextID(),CS_PROJECT_TYPE,'detail',$params));
}

include_once('include/inc_delete_entry.php');


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
   if ( $language == 'user' ) {
      $language = $user_item->getLanguage();
      if ( $language == 'browser' ) {
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

       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $mail->set_from_email($emailFrom);

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

if (isOption($option, $translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON'))){
   $room_manager = $environment->getRoomManager();
   $room_item = $room_manager->getItem($current_item_id);
   $session = $environment->getSessionItem();
   $params['iid']= $current_item_id;

   // build new user_item
   if ( !$room_item->checkNewMembersWithCode()
        or ( $room_item->getCheckNewMemberCode() == $_POST['code'])
      ) {
       $current_user = $environment->getCurrentUserItem();
       $private_room_user_item = $current_user->getRelatedPrivateRoomUserItem();
       if ( isset($private_room_user_item) ) {
          $user_item = $private_room_user_item->cloneData();
          $picture = $private_room_user_item->getPicture();
       } else {
          $user_item = $current_user->cloneData();
          $picture = $current_user->getPicture();
       }
       $user_item->setContextID($current_item_id);
       if (!empty($picture)) {
          $value_array = explode('_',$picture);
          $value_array[0] = 'cid'.$user_item->getContextID();

          $new_picture_name = implode('_',$value_array);
          $disc_manager = $environment->getDiscManager();
          $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
          $user_item->setPicture($new_picture_name);
       }
       if (isset($_POST['description_user'])) {
          $user_item->setUserComment($_POST['description_user']);
       }

       //check room_settings
       if ( !$room_item->checkNewMembersNever()
            and !$room_item->checkNewMembersWithCode()
          ) {
          $user_item->request();
          $check_message = 'YES'; // for mail body
          $account_mode = 'info';
       } else {
          $user_item->makeUser(); // for mail body
          $check_message = 'NO';
          $account_mode = 'to_room';
          // save link to the group ALL
          $group_manager = $environment->getLabelManager();
          $group_manager->setExactNameLimit('ALL');
          $group_manager->setContextLimit($current_item_id);
          $group_manager->select();
          $group_list = $group_manager->get();
          if ($group_list->getCount() == 1) {
             $group = $group_list->getFirst();
             $group->setTitle('ALL');
             $user_item->setGroupByID($group->getItemID());
          }
       }

       // test if user id already exists (reload page)
       $user_id = $user_item->getUserID();
       $user_test_item = $room_item->getUserByUserID($user_id,$user_item->getAuthSource());
       if ( !isset($user_test_item)
            and mb_strtoupper($user_id, 'UTF-8') != 'GUEST'
            and mb_strtoupper($user_id, 'UTF-8') != 'ROOT'
          ) {
          $user_item->save();
          $user_item->setCreatorID2ItemID();

          // save task
          if ( !$room_item->checkNewMembersNever()
               and !$room_item->checkNewMembersWithCode()
             ) {
             $task_manager = $environment->getTaskManager();
             $task_item = $task_manager->getNewItem();
             $current_user = $environment->getCurrentUserItem();
             $task_item->setCreatorItem($current_user);
             $task_item->setContextID($room_item->getItemID());
             $task_item->setTitle('TASK_USER_REQUEST');
             $task_item->setStatus('REQUEST');
             $task_item->setItem($user_item);
             $task_item->save();
          }

          // send email to moderators if necessary
          $user_manager = $environment->getUserManager();
          $user_manager->resetLimits();
          $user_manager->setModeratorLimit();
          $user_manager->setContextLimit($current_item_id);
          $user_manager->select();
          $user_list = $user_manager->get();
          $email_addresses = array();
          $moderator_item = $user_list->getFirst();
          $recipients = '';
          while ($moderator_item) {
             $want_mail = $moderator_item->getAccountWantMail();
             if (!empty($want_mail) and $want_mail == 'yes') {
                $email_addresses[] = $moderator_item->getEmail();
                $recipients .= $moderator_item->getFullname()."\n";
             }
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

          if ( count($email_addresses) > 0 ) {
             $save_language = $translator->getSelectedLanguage();
             $translator->setSelectedLanguage($language);
             $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
             $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
             $body .= LF.LF;
             $body .= $translator->getMessage('USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$user_item->getUserID(),$user_item->getEmail(),$room_item->getTitle());
             $body .= LF.LF;

             $tempMessage = "";
             switch ( cs_strtoupper($check_message) ) {
                case 'YES':
                   $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                   break;
                case 'NO':
                   $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                   break;
                default:
                   $body .= $translator->getMessage('COMMON_MESSAGETAG_ERROR')." context_detail(244) ";
                   break;
             }

             $body .= LF.LF;
             if (!empty($_POST['description_user'])) {
                $body .= $translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$_POST['description_user']);
                $body .= LF.LF;
             }
             $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
             $body .= LF;
             if ( cs_strtoupper($check_message) == 'YES') {
                $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
                $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_item_id.'&mod=account&fct=index'.'&selstatus=1';
             } else {
                $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_item_id;
             }
             $mail = new cs_mail();
             $mail->set_to(implode(',',$email_addresses));

              global $symfonyContainer;
              $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
              $mail->set_from_email($emailFrom);

             $current_context = $environment->getCurrentContextItem();
             $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
             $mail->set_reply_to_name($user_item->getFullname());
             $mail->set_reply_to_email($user_item->getEmail());
             $mail->set_subject($subject);
             $mail->set_message($body);
             $mail->send();
             $translator->setSelectedLanguage($save_language);
          }

          // send email to user when account is free automatically (PROJECT ROOM)
          if ($user_item->isUser()) {

             // get contact moderator (TBD) now first moderator
             $user_list = $room_item->getModeratorList();
             $contact_moderator = $user_list->getFirst();

             // change context to project room
             $translator->setEmailTextArray($room_item->getEmailTextArray());
             $translator->setContext('project');
             $save_language = $translator->getSelectedLanguage();

             // language
             $language = $room_item->getLanguage();
             if ($language == 'user') {
                $language = $user_item->getLanguage();
                if ($language == 'browser') {
                   $language = $environment->getSelectedLanguage();
                }
             }

             $translator->setSelectedLanguage($language);
             
             // Datenschutz
             if($environment->getCurrentPortalItem()->getHideAccountname()){
             	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
             } else {
             	$userid = $user->getUserID();
             }

             // email texts
             $subject = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$room_item->getTitle());
             $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
             $body .= LF.LF;
             $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
             $body .= LF.LF;
             $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$room_item->getTitle());
             $body .= LF.LF;
             $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$room_item->getTitle());
             $body .= LF.LF;
             $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();

             // send mail to user
             $mail = new cs_mail();
             $mail->set_to($user_item->getEmail());
             $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));

              global $symfonyContainer;
              $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
              $mail->set_from_email($emailFrom);

             $mail->set_reply_to_email($contact_moderator->getEmail());
             $mail->set_reply_to_name($contact_moderator->getFullname());
             $mail->set_subject($subject);
             $mail->set_message($body);
             $mail->send();
          }
      }
   } elseif ( $room_item->checkNewMembersWithCode()
              and $room_item->getCheckNewMemberCode() != $_POST['code']
            ) {
      $account_mode = 'member';
      $error = 'code';
   }
   if ($account_mode =='to_room'){
      redirect($current_item_id, 'home', 'index', '');
   } else {
      $params['account']= $account_mode;
      if ( isset($error) and !empty($error) ) {
         $params['error'] = $error;
      }
      redirect($environment->getCurrentContextID(), CS_PROJECT_TYPE, 'detail', $params);
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
} elseif ( !$item->maySee($current_user)) {
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

        //Set Noticed
        $noticed_manager = $environment->getNoticedManager();
        $noticed = $noticed_manager->getLatestNoticed($item->getItemID());
        if ( empty($noticed) or $noticed['read_date'] < $item->getModificationDate() ) {
           $noticed_manager->markNoticed($item->getItemID(),0);
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
   } elseif ( $environment->inPrivateRoom() ) {
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

   // annotation: add annotation list here
   $annotations = $item->getAnnotationList();
   $reader_manager = $environment->getReaderManager();
   $noticed_manager = $environment->getNoticedManager();
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
   $detail_view->setAnnotationList($item->getAnnotationList());

   // highlight search words in detail views
   $current_context_item = $environment->getCurrentContextItem();
   $session_item = $environment->getSessionItem();
   if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
      $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
      if ( !empty($search_array['search']) ) {
         $detail_view->setSearchText($search_array['search']);
      }
      unset($search_array);
   }
   unset($current_context_item);

   $page->add($detail_view);
}
?>