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

// Verify parameters for this page
if (!empty($_GET['iid'])) {
   $current_item_id = $_GET['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('An item id must be given.', E_USER_ERROR);
}

include_once('include/inc_delete_entry.php');

$label_manager = $environment->getGroupManager();
$item = $label_manager->getItem($_GET['iid']);

// Get the translator object
$translator = $environment->getTranslationObject();

###############################################
# FLAG: group room
###############################################
if (!empty($_GET['account'])) {
   $account_mode = $_GET['account'];
} else {
   $account_mode = 'none';
}
if (isset($_POST['option'])){
   $option = $_POST['option'];
} else {
   $option= 'none';
}

if ( isOption($option, $translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON')) ) {
   $room_item = $item->getGroupRoomItem();
   if ( isset($room_item) and !empty($room_item) ) {
      $session = $environment->getSessionItem();
      $params['iid']= $current_item_id;

      // build new user_item
      if ( !$room_item->checkNewMembersWithCode()
           or ( $room_item->getCheckNewMemberCode() == $_POST['code'])
         ) {
         $current_user = $environment->getCurrentUserItem();
         $user_item = $current_user->cloneData();
         $picture = $current_user->getPicture();
         $user_item->setContextID($room_item->getItemID());
         if ( !empty($picture) ) {
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
         }

         // test if user id allready exist (reload page)
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
            $user_list = $room_item->getModeratorList();
            $email_addresses = array();
            $moderator_item = $user_list->getFirst();
            $recipients = '';
            while ( $moderator_item ) {
               $want_mail = $moderator_item->getAccountWantMail();
               if ( !empty($want_mail) and $want_mail == 'yes' ) {
                  $email_addresses[] = $moderator_item->getEmail();
                  $recipients .= $moderator_item->getFullname().LF;
               }
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

            if ( count($email_addresses) > 0 ) {
               $save_language = $translator->getSelectedLanguage();
               $translator->setSelectedLanguage($language);
               $subject = $translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$room_item->getTitle());
               $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
               $body .= LF.LF;
               // Datenschutz
               if($this->_environment->getCurrentPortalItem()->getHideAccountname()){
               	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
               } else {
               	$userid = $portal_user->getUserID();
               }
               $body .= $translator->getMessage('GROUPROOM_USER_JOIN_CONTEXT_MAIL_BODY',$user_item->getFullname(),$userid,$user_item->getEmail(),$room_item->getTitle());
               $body .= LF.LF;

               switch ( $check_message )
               {
                   case 'YES':
                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_YES');
                     break;
                   case 'NO':
                     $body .= $translator->getMessage('USER_GET_MAIL_STATUS_NO');
                     break;
                   default:
                     break;
               }

               $body .= LF.LF;
               if ( !empty($_POST['description_user']) ) {
                  $body .= $translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$_POST['description_user']);
                  $body .= LF.LF;
               }
               $body .= $translator->getMessage('MAIL_SEND_TO',$recipients);
               if ( !$room_item->checkNewMembersNever() ) {
                  $body .= LF;
                  $body .= $translator->getMessage('MAIL_USER_FREE_LINK').LF;
                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID().'&mod=account&fct=index&selstatus=1';
               } else {
                  $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$room_item->getItemID();
               }
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to(implode(',',$email_addresses));
               $server_item = $environment->getServerItem();
               $default_sender_address = $server_item->getDefaultSenderAddress();
               if ( !empty($default_sender_address) ) {
                  $mail->set_from_email($default_sender_address);
               } else {
                  $mail->set_from_email('@');
               }
               $current_context = $environment->getCurrentContextItem();
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
               $mail->set_reply_to_name($user_item->getFullname());
               $mail->set_reply_to_email($user_item->getEmail());
               $mail->set_subject($subject);
               $mail->set_message($body);
               $mail->send();
               $translator->setSelectedLanguage($save_language);
            }

            // send email to user when account is free automatically
            // and make member of the group in the group room
            if ( $user_item->isUser() ) {

               // make member
               $item->addMember($current_user);

               // get contact moderator (TBD) now first contect moderator
               $user_list = $room_item->getContactModeratorList();
               $contact_moderator = $user_list->getFirst();

               // change context to group room
               $translator->setEmailTextArray($room_item->getEmailTextArray());
               $translator->setContext(CS_GROUPROOM_TYPE);
               $save_language = $translator->getSelectedLanguage();

               // language
               $language = $room_item->getLanguage();
               if ( $language == 'user' ) {
                  $language = $user_item->getLanguage();
                  if ( $language == 'browser' ) {
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
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to($user_item->getEmail());
               $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$room_item->getTitle()));
               $server_item = $environment->getServerItem();
               $default_sender_address = $server_item->getDefaultSenderAddress();
               if ( !empty($default_sender_address) ) {
                  $mail->set_from_email($default_sender_address);
               } else {
                  $mail->set_from_email('@');
               }
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
      if ( $account_mode == 'to_room' ) {
         redirect($room_item->getItemID(), 'home', 'index', '');
      } else {
         $params['account'] = $account_mode;
         if ( isset($error) and !empty($error) ) {
            $params['error'] = $error;
         }
         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),'detail',$params);
      }
   }
}


$type = $item->getItemType();
if ($type != CS_GROUP_TYPE) {
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
   $group_manager = $environment->getGroupManager();
   $group_item = $group_manager->getItem($current_item_id);
   $current_user = $environment->getCurrentUser();

   if ( !isset($group_item) ) {
      include_once('functions/error_functions.php');
      trigger_error('Item '.$current_item_id.' does not exist!', E_USER_ERROR);
   } elseif ( $group_item->isDeleted() ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('ITEM_NOT_AVAILABLE'));
      $page->add($errorbox);
   } elseif ( !$group_item->maySee($current_user) ) {
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
      unset($params);
      $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
      $page->add($errorbox);
   } else {

      // Enter or leave Group
      if (!empty($_GET['group_option'])) {
         $current_user = $environment->getCurrentUser();
         if ( $_GET['group_option']=='1' ) {
            $group_item->addMember($current_user);
            if($environment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() == "1"){
                $wiki_manager = $environment->getWikiManager();
                $wiki_manager->updateNotification();
            }
         } elseif ( $_GET['group_option']=='2' ) {
            $group_item->removeMember($current_user);
            if($environment->getCurrentContextItem()->WikiEnableDiscussionNotificationGroups() == "1"){
                $wiki_manager = $environment->getWikiManager();
                $wiki_manager->updateNotification();
            }

            ##################################
            # FLAG: group room
            ##################################
            if ( $group_item->isGroupRoomActivated() ) {
               $current_user = $environment->getCurrentUserItem();
               $grouproom_item = $group_item->getGroupRoomItem();
               if ( isset($grouproom_item) and !empty($grouproom_item) ) {
                  $group_room_user_item = $grouproom_item->getUserByUserID($current_user->getUserID(),$current_user->getAuthSource());
                  $group_room_user_item->reject();
                  $group_room_user_item->save();
               }
            }
            ##################################
            # FLAG: group room
            ##################################
         }
      }

      // Mark as read
      $reader_manager = $environment->getReaderManager();
      $reader = $reader_manager->getLatestReader($group_item->getItemID());
      if ( empty($reader) or $reader['read_date'] < $group_item->getModificationDate() ) {
         $reader_manager->markRead($group_item->getItemID(), 0);
      }
      //Set Noticed
      $noticed_manager = $environment->getNoticedManager();
      $noticed = $noticed_manager->getLatestNoticed($group_item->getItemID());
      if ( empty($noticed) or $noticed['read_date'] < $group_item->getModificationDate() ) {
         $noticed_manager->markNoticed($group_item->getItemID(),0);
      }

      // Create view
      $context_item = $environment->getCurrentContextItem();
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = $context_item->isOpen();
      $params['creator_info_status'] = $creatorInfoStatus;
      $detail_view = $class_factory->getClass(GROUP_DETAIL_VIEW,$params);
      unset($params);
      $detail_view->setItem($group_item);

      #######################################
      # FLAG: group room
      #######################################
      $detail_view->setAccountMode($account_mode);
      #######################################
      # FLAG: group room
      #######################################

      // Set up browsing order
      if ($session->issetValue('cid'.$environment->getCurrentContextID().'_group_index_ids')) {
         $group_ids = $session->getValue('cid'.$environment->getCurrentContextID().'_group_index_ids');
      } else {
         $group_ids = array();
      }
      $detail_view->setBrowseIDs($group_ids);
      if ( isset($_GET['pos']) ) {
         $detail_view->setPosition($_GET['pos']);
      }

      // Set up rubric connections and browsing
      if ( $context_item->withRubric(CS_USER_TYPE) ) {
         $ids = $group_item->getLinkedItemIDArray(CS_USER_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_user_index_ids', $ids);
      }
      $rubric_connections = array();
      if ( $context_item->withRubric(CS_TOPIC_TYPE) ) {
         $ids = $group_item->getLinkedItemIDArray(CS_TOPIC_TYPE);
         $session->setValue('cid'.$environment->getCurrentContextID().'_topics_index_ids', $ids);
         $rubric_connections = array(CS_TOPIC_TYPE);
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
               case CS_ANNOUNCEMENT_TYPE:
                  $ids = $group_item->getLinkedItemIDArray(CS_ANNOUNCEMENT_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_announcement_index_ids', $ids);
                  $rubric_connections[] = CS_ANNOUNCEMENT_TYPE;
                  break;
               case 'todo':
                  $context = $environment->getCurrentContextItem();
                  if ($context->withRubric(CS_TODO_TYPE)){
                     $ids = $group_item->getLinkedItemIDArray(CS_TODO_TYPE);
                     $session->setValue('cid'.$environment->getCurrentContextID().'_todo_index_ids', $ids);
                     $rubric_connections[] = CS_TODO_TYPE;
                  }
                  break;
               case CS_DATE_TYPE:
                  $ids = $group_item->getLinkedItemIDArray(CS_DATE_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_dates_index_ids', $ids);
                  $rubric_connections[] = CS_DATE_TYPE;
                  break;
               case 'material':
                  $ids = $group_item->getLinkedItemIDArray(CS_MATERIAL_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_material_index_ids', $ids);
                  $rubric_connections[] = CS_MATERIAL_TYPE;
                  break;
               case 'discussion':
                  $ids = $group_item->getLinkedItemIDArray(CS_DISCUSSION_TYPE);
                  $session->setValue('cid'.$environment->getCurrentContextID().'_discussion_index_ids', $ids);
                  $rubric_connections[] = CS_DISCUSSION_TYPE;
                  break;
            }
         }
      }
      $detail_view->setRubricConnections($rubric_connections);

      // highlight search words in detail views
      $session_item = $environment->getSessionItem();
      if ( $session->issetValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array') ) {
         $search_array = $session->getValue('cid'.$environment->getCurrentContextID().'_campus_search_parameter_array');
         if ( !empty($search_array['search']) ) {
            $detail_view->setSearchText($search_array['search']);
         }
         unset($search_array);
      }

      // Add view to page ... and done
      $page->add($detail_view);
   }
}
?>