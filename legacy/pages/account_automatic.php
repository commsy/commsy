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

// include all classes and functions needed for this script
include_once('classes/cs_mail.php');
include_once('functions/date_functions.php');
include_once('functions/text_functions.php');

// translation - object
$translator = $environment->getTranslationObject();

// this script can be activate with get parameters
if (!empty($_GET['status'])) {
   $automatic = true;
   $status = $_GET['status'];
   $command = 'automatic';
} else {
   $command = '';
   $automatic = false;
}
if (!empty($_GET['iid'])) {
   $iid = $_GET['iid']; // item id of the user
}

$user_manager = $environment->getUserManager();
$user = $user_manager->getItem($iid);
if ( isset($user) ) {
   $last_status = $user->getStatus();
}
$current_user = $environment->getCurrentUserItem();

// error if user is already deleted
if (empty($command) and $user->getDeletionDate()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   if ($environment->inProjectRoom()) {
      $errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_JUST_DELETED',$user->getFullname()));
   } else {
      $errorbox->setText($translator->getMessage('ACCOUNT_EDIT_ERROR_JUST_DELETED',$user->getFullname(),$user->getUserID()));
   }
   $page->add($errorbox);
   $command = 'error';
}

//check if room is open
$context_item = $environment->getCurrentContextItem();
if (!$context_item->isOpen()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

if ($command == 'automatic') {
   $status = $_GET['status'];

   $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
   if ( $environment->inCommunityRoom() ) {
      $save_language = $translator->getSelectedLanguage();
      $translator->setSelectedLanguage($user->getLanguage());
   }

   if($environment->getCurrentPortalItem()->getHideAccountname()){
   	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
   } else {
   	$userid = $user->getUserID();
   }
   $current_user = $environment->getCurrentUserItem();
   if ($status == 'reject' or $status == 'close') {
      $subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$context_item->getTitle());
      $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$userid,$context_item->getTitle());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
   } elseif ($status == 'free') {
      $subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$context_item->getTitle());
      $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$context_item->getTitle());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
   } elseif ($status == 'user') {
      $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$context_item->getTitle());
      $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$context_item->getTitle());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
   }

   // this script can only free or close accounts automatically now
   /*elseif ($status == 'moderator') {
      $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$context_item->getTitle());
      $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$user->getUserID(),$context_item->getTitle());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
  } elseif ($status == 'delete') {
      $subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$context_item->getTitle());
      $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$user->getUserID(),$context_item->getTitle());
      $body .= LF.LF;
      $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
   } */

   else {
      include_once('functions/error_functions.php');trigger_error('lost change status',E_USER_ERROR);
   }

   $body .= LF.LF;
   $body .= $url;

   if ( $environment->inCommunityRoom() and isset($save_language) ) {
      $translator->setSelectedLanguage($save_language);
      unset($save_language);
   }

   // change user status
   if ($status == 'user' or $status == 'free') {
      $user->makeUser();
   } elseif ($status == 'reject' or $status == 'close') {
      $user->reject();
   }

   // this script can only free or close accounts automatically now
   /*elseif ($status == 'moderator') {
      $user->makeModerator();
   }*/

   //get group 'ALL'
   $group_manager = $environment->getLabelManager();
   $group_manager->setExactNameLimit('ALL');
   $group_manager->setContextLimit($environment->getCurrentContextID());
   $group_manager->select();
   $group_list = $group_manager->get();
   if ($group_list->getCount() == 1) {
      $group = $group_list->getFirst();
      $group->setTitle('ALL'); // needed, but not good (TBD)
   }
   // save link to the group ALL
   if ($environment->inProjectRoom() and $status != 'reject' and $status != 'close') {
      if (isset($group)) {
         $user->setGroupByID($group->getItemID());
         $group->setModificatorItem($current_user);
         $group->save();
      }
   }
   $user->setChangeModificationOnSave(false);
   $user->save();

   // if commsy user is rejected, reject all accounts in projectrooms
   if ($user->isRejected() and $environment->inPortal()) {
      $user_list = $user->getRelatedUserList();
      $user_item = $user_list->getFirst();
      while ($user_item) {
         $user_item->reject();
         $user_item->save();
         $user_item = $user_list->getNext();
      }
      // make sense ??? (TBD)
      if (isset($group)) {
         $group->setModificatorItem($current_user);
         $group->save();
      }
   }

   // if user is rejected, update group all
   if ( $user->isRejected() and $environment->inProjectRoom() ) {
      if (isset($group)) {
         $group->setModificatorItem($current_user);
         $group->save();
      }
   }

   // if commsy user is re-opend, re-open own room user
   if ( $environment->inPortal()
        and isset($last_status)
        and ( empty($last_status)
              or $last_status == 0
            )
      ) {
      $user_own_room = $user->getRelatedPrivateRoomUserItem();
      if ( isset($user_own_room) ) {
         $user_own_room->makeModerator();
         $user_own_room->makeContactPerson();
         $user_own_room->save();
      }
   }

   $error_number = $user_manager->getErrorNumber();
   $error_string = '';
   if (empty($error_number)) {

      // change task status
      if ($environment->inCommunityRoom() or $environment->inProjectRoom()) {
         $task_manager = $environment->getTaskManager();
         $task_list = $task_manager->getTaskListForItem($user);
         if ($task_list->getCount() > 0) {
            $task_item = $task_list->getFirst();
            while ($task_item) {
               if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_USER_REQUEST' or $task_item->getTitle() == 'TASK_PROJECT_MEMBER_REQUEST')) {
                  $task_item->setStatus('CLOSED');
                  $task_item->save();
                  $task_title = $task_item->getTitle(); // needed for email
               }
               $task_item = $task_list->getNext();
            }
         }
      }

      if (empty($error_number)) {
         // send mail to user
         $mail = new cs_mail();
         $mail->set_to($user->getEmail());

          global $symfonyContainer;
          $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
          $mail->set_from_email($emailFrom);

         $mail->set_from_name($environment->getCurrentPortalItem()->getTitle());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();

         // back to index pages
         $history = $session->getValue('history');
         redirect($history[0]['context'],$history[0]['module'],$history[0]['function'],$history[0]['parameter']);
      } else {
         $error_string .= $translator->getMessage('COMMON_ERROR_SAVE_TASK').'<br />';
      }
   } else {
      $error_string .= $translator->getMessage('USER_ERROR_SAVE').'<br />';
   }
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($error_string);
   $page->add($errorbox);
} else {
   include_once('functions/error_functions.php');
   trigger_error('no automatic status set',E_USER_ERROR);
}
?>