<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

// include all classes and functions needed for this script
include_once('classes/cs_form.php');
include_once('classes/cs_mail.php');
include_once('functions/date_functions.php');
include_once('functions/text_functions.php');
include_once('classes/cs_account_status_form.php');

// translation - object
$translator = $environment->getTranslationObject();

// options und  parameters
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
   if (!empty($_POST['iid'])) {
      $iid = $_POST['iid']; // item id of the user
   }
} else {
   $command = '';
   if (!empty($_GET['iid'])) {
      $iid = $_GET['iid']; // item id of the user
   }
}

$user_manager = $environment->getUserManager();
$user = $user_manager->getItem($iid);
$current_user = $environment->getCurrentUserItem();

// error if user is already deleted
if (empty($command) and $user->getDeletionDate()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   if ( $environment->inProjectRoom() or $environment->inCommunityRoom() ) {
      $errorbox->setText(getMessage('MEMBER_EDIT_ERROR_JUST_DELETED',$user->getFullname()));
   } else {
      $errorbox->setText(getMessage('ACCOUNT_EDIT_ERROR_JUST_DELETED',$user->getFullname(),$user->getUserID()));
   }
   $page->add($errorbox);
   $command = 'error';
}

//check if context is open
$context_item = $environment->getCurrentContextItem();
if (!$context_item->isOpen()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

// Check access rights
$room_item = $environment->getCurrentContextItem();

if ($current_user->isGuest()) {
   if (!$room_item->isOpenForGuests()) {
      redirect($environment->getCurrentPortalId(),'home','index','');
   } else {
      $params = array() ;
      $params['cid'] = $room_item->getItemId();
      redirect($environment->getCurrentPortalId(),'home','index',$params);
   }
} elseif (!$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText(getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

// first step administration form
if (empty($command)) {
   $form = new cs_account_status_form($environment);
   $form->setItem($user);
   $form->prepareForm();
   $form->loadValues();

   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
   unset($params);
   $form_view->setAction(curl($environment->getCurrentContextID(),'account','status',''));
   $form_view->setForm($form);
   if ( $environment->inServer() or $environment->inPortal() ) {
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
} elseif (isOption($command,getMessage('ADMIN_CANCEL_BUTTON')) or isOption($command,getMessage('MAIL_NOT_SEND_BUTTON'))) {
   $history = $session->getValue('history');
   $back_hop = 1;
   while ($history[$back_hop]['function'] == 'status' or $history[$back_hop]['module'] == 'mail') {
      $back_hop++;
   }
   redirect($history[$back_hop]['context'],$history[$back_hop]['module'],$history[$back_hop]['function'],$history[$back_hop]['parameter']);
} elseif (isOption($command,getMessage('COMMON_CHANGE_BUTTON')) or isOption($command,getMessage('ACCOUNT_DELETE_BUTTON'))) {
   if (isOption($command,getMessage('ACCOUNT_DELETE_BUTTON'))) {

      // change task status
      $task_manager = $environment->getTaskManager();
      $task_list = $task_manager->getTaskListForItem($user);
      if ($task_list->getCount() > 0) {
         $task_item = $task_list->getFirst();
         while ($task_item) {
            if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_USER_REQUEST' or $task_item->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
               $task_item->setStatus('CLOSED');
               $task_item->save();
               $task_title = $task_item->getTitle();
            }
            $task_item = $task_list->getNext();
         }
      }

      // in a project room
      if ( $environment->inProjectRoom()
           or $environment->inCommunityRoom()
           or $environment->inGroupRoom()
         ) {

         ################################
         # FLAG: group room
         ################################
         if ( $environment->inGroupRoom() ) {
            $group_item = $current_context->getLinkedGroupItem();
            if ( isset($group_item) and !empty($group_item) ) {
               $project_room_item = $current_context->getLinkedProjectItem();
               if ( isset($project_room_item) and !empty($project_room_item) ) {
                  $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                  $group_item->removeMember($project_room_user_item);
               }
            }
         }
         ################################
         # FLAG: group room
         ################################

         $user->delete();
      }
      // at the portal of CommSy
      else {
         $authentication = $environment->getAuthenticationObject();
         $authentication->delete($iid);
      }

      // send mail to user
      $language = '';
      if ( $environment->inProjectRoom() or $environment->inCommunityRoom()) {
         $language = $context_item->getLanguage();
   if ($language == 'user') {
            $language = $user->getLanguage();
      if ($language == 'browser') {
         $lanugage = $environment->getSelectedLanguage();
      }
   }
      } else {
         $language = $user->getLanguage();
   if ($language == 'browser') {
      $lanugage = $environment->getSelectedLanguage();
   }
      }
      include_once('classes/cs_mail_obj.php');
      $mail_obj = new cs_mail_obj();
      $mail_obj->setMailFormHeadLine(getMessage('ADMIN_USER_FORM_TITLE',$user->getFullname(),getMessage('COMMON_STEP_END')));

      $mail_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$context_item->getTitle());
      $mail_body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $mail_body .= LF.LF;
      $mail_body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$user->getUserID(),$context_item->getTitle());
      $mail_body .= LF.LF;
      $mail_body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

      $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
      $mail_body .= LF.LF.$url;

      $mail_obj->setMailFormHints(getMessage('USER_MAIL_ADMIN_DESC'));
      $mail_obj->setSubject($mail_subject);
      $mail_obj->setContent($mail_body);
      $sender[$current_user->getFullName()] = $current_user->getEMail();
      $mail_obj->setSender($sender);
      $receiver[$user->getFullName()] = $user->getEMail();
      $mail_obj->addReceivers($receiver);
      $mail_obj->setBackLink($environment->getCurrentContextID(),
                             'account',
                             'index',
                             '');
      $mail_obj->toSession();
      redirect($environment->getCurrentContextID(),'mail','process','');
   } // end delete user

   // change status to ... but not delete a user
   else {
      $form = new cs_account_status_form($environment);
      $form->setFormPost($_POST);
      $form->prepareForm();
      $form->loadValues();
      if ($form->check()) {
         $status = $_POST['status'];
         // save data and display mail form
         // first: save data
         $status = $_POST['status'];
         if ( $status == 'user' ) {
            $user->makeUser();
            if (!empty($_POST['contact_person'])) {
               $user->makeContactPerson();
            } else {
               $user->makeNoContactPerson();
            }

            ################################
            # FLAG: group room
            ################################
            if ( $environment->inGroupRoom() ) {
               $group_item = $current_context->getLinkedGroupItem();
               if ( isset($group_item) and !empty($group_item) ) {
                  $project_room_item = $current_context->getLinkedProjectItem();
                  if ( isset($project_room_item) and !empty($project_room_item) ) {
                     $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                     $group_item->addMember($project_room_user_item);
                  }
               }
            }
            ################################
            # FLAG: group room
            ################################

         } elseif ( $status == 'reject' or $status == 'close' ) {
            $user->reject();

            ################################
            # FLAG: group room
            ################################
            if ( $environment->inGroupRoom() ) {
               $group_item = $current_context->getLinkedGroupItem();
               if ( isset($group_item) and !empty($group_item) ) {
                  $project_room_item = $current_context->getLinkedProjectItem();
                  if ( isset($project_room_item) and !empty($project_room_item) ) {
                     $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                     $group_item->removeMember($project_room_user_item);
                  }
               }
            }
            ################################
            # FLAG: group room
            ################################

         } elseif ( $status == 'moderator' ) {
            $user->makeModerator();
            if (!empty($_POST['contact_person'])) {
               $user->makeContactPerson();
            } else {
               $user->makeNoContactPerson();
            }

            ################################
            # FLAG: group room
            ################################
            if ( $environment->inGroupRoom() ) {
               $group_item = $current_context->getLinkedGroupItem();
               if ( isset($group_item) and !empty($group_item) ) {
                  $project_room_item = $current_context->getLinkedProjectItem();
                  if ( isset($project_room_item) and !empty($project_room_item) ) {
                     $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                     $group_item->addMember($project_room_user_item);
                  }
               }
            }
            ################################
            # FLAG: group room
            ################################

         }

         // find group 'ALL'
         if ($environment->inProjectRoom()) {
            $group_manager = $environment->getLabelManager();
            $group_manager->setExactNameLimit('ALL');
            $group_manager->setContextLimit($environment->getCurrentContextID());
            $group_manager->select();
            $group_list = $group_manager->get();
            if ($group_list->getCount() == 1) {
               $group = $group_list->getFirst();
               $group->setTitle('ALL'); // needed, but not very good (TBD)
            }
         }

         // save link to the group ALL, if we are in a project room
         if ($_POST['status_old'] == 'request' and $environment->inProjectRoom()) {
            if (isset($group)) {
               $user->setGroupByID($group->getItemID());
               $group->setModificatorItem($current_user);
               $group->save();
            }
         }
         $user->setChangeModificationOnSave(false);
         $user->save();

         // if commsy user is rejected, reject all accounts in project- and community rooms
         if ($user->isRejected() and $environment->inPortal()) {
            $user_list = $user->getRelatedUserList();
            $user_item = $user_list->getFirst();
            while ($user_item) {
               $user_item->reject();
               $user_item->save();
               $user_item = $user_list->getNext();
            }
         }

         // change task status
         $task_manager = $environment->getTaskManager();
         $task_list = $task_manager->getTaskListForItem($user);
         if ($task_list->getCount() > 0) {
            $task_item = $task_list->getFirst();
            while ($task_item) {
               if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_USER_REQUEST' or $task_item->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                  $task_item->setStatus('CLOSED');
                  $task_item->save();
                  $task_title = $task_item->getTitle();
               }
               $task_item = $task_list->getNext();
            }
         }

         // now the mail form
         $language = '';
         if ( $environment->inProjectRoom() or $environment->inCommunityRoom()) {
            $language = $context_item->getLanguage();
          if ($language == 'user') {
               $language = $user->getLanguage();
             if ($language == 'browser') {
               $lanugage = $environment->getSelectedLanguage();
             }
          }
         } else {
            $language = $user->getLanguage();
          if ($language == 'browser') {
            $lanugage = $environment->getSelectedLanguage();
          }
         }
         include_once('classes/cs_mail_obj.php');
         $mail_obj = new cs_mail_obj();
         $mail_obj->setMailFormHeadLine(getMessage('ADMIN_USER_FORM_TITLE',$user->getFullname(),getMessage('COMMON_STEP_END')));

         // change language for user
         $save_language = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($user->getLanguage());

         if ($status == 'reject' or $status == 'close') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$user->getUserID(),$context_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
         } elseif ($status == 'user') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user->getUserID(),$context_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
         } elseif ($status == 'moderator') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$user->getUserID(),$context_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
         } else {
            include_once('functions/error_functions.php');trigger_error('lost change status',E_USER_ERROR);
         }

         // change language back
         $translator->setSelectedLanguage($save_language);
         unset($save_language);

         $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
         $body .= LF.LF.$url;
         $mail_body = $body;
         $mail_subject = $subject;

         $mail_obj->setMailFormHints(getMessage('USER_MAIL_ADMIN_DESC'));
         $mail_obj->setSubject($mail_subject);
         $mail_obj->setContent($mail_body);
         $sender[$current_user->getFullName()] = $current_user->getEMail();
         $mail_obj->setSender($sender);
         $receiver[$user->getFullName()] = $user->getEMail();
         $mail_obj->addReceivers($receiver);

         // get back link out of history
         $history = $session->getValue('history');
         $back_hop = 1; // must be one hop, so no if-clause here
       while ($history[$back_hop]['function'] == 'status' or $history[$back_hop]['module'] == 'mail') {
           $back_hop++;
         }
         $mail_obj->setBackLink($history[$back_hop]['context'],
                                $history[$back_hop]['module'],
                                $history[$back_hop]['function'],
                                $history[$back_hop]['parameter']);
         $mail_obj->toSession();
         redirect($environment->getCurrentContextID(),'mail','process','');
      } else {
         $params = array();
         $params['environment'] = $environment;
         $params['with_modifying_actions'] = true;
         $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
         unset($params);
         $form_view->setAction(curl($environment->getCurrentContextID(),'account','status',''));
         $form_view->setForm($form);
         if ( $environment->inServer() or $environment->inPortal() ) {
            $page->addForm($form_view);
         } else {
            $page->add($form_view);
         }
      }
   }
}
?>