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

// options und  parameters
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
if (!empty($_POST['delete_option'])) {
   $command_delete = $_POST['delete_option'];
} else {
   $command_delete = '';
}
if (!empty($_POST['iid'])) {
   $iid = $_POST['iid']; // item id of the user
   $_GET['iid'] = $iid;
} elseif (!empty($_GET['iid'])) {
   $iid = $_GET['iid']; // item id of the user
}
include_once('include/inc_delete_entry.php');

// Find out what to do
if ( isset($_POST['option']) and $_POST['option'] == $translator->getMessage('ACCOUNT_DELETE_BUTTON')) {
   $_GET['action'] = 'delete';
}
if ( isset($_GET['action']) and $_GET['action'] == 'delete' ) {
   $current_user_item = $environment->getCurrentUserItem();
   $context_item = $environment->getCurrentContextItem();
   if ( !empty($context_item) ) {
      if ( $current_user_item->isModerator()
           or ( isset($context_item)
                and $context_item->isModeratorByUserID($current_user_item->getUserID(),$current_user_item->getAuthSource())
              )
         ) {
         $form = $class_factory->getClass(ACCOUNT_STATUS_FORM,array('environment' => $environment));
         $form->setFormPost($_POST);
         $form->prepareForm();
         $form->loadValues();
         if ( $form->check() ) {
            $params = $environment->getCurrentParameterArray();
            $page->addDeleteBox(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
            $hidden_values = array();
            $hidden_values['iid'] = $_POST['iid'];
            $hidden_values['fullname'] = $_POST['fullname'];
            $hidden_values['lastlogin'] = $_POST['lastlogin'];
            $hidden_values['user_id'] = $_POST['user_id'];
            if ( !empty($_POST['contact_person']) ) {
               $hidden_values['contact_person'] = $_POST['contact_person'];
            }
            if ( !empty($_POST['status_old']) ) {
               $hidden_values['status_old'] = $_POST['status_old'];
            }
            $page->addDeleteBoxHiddenValues($hidden_values);
         }
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
   if ( $environment->inProjectRoom() or $environment->inCommunityRoom() ) {
      $errorbox->setText($translator->getMessage('MEMBER_EDIT_ERROR_JUST_DELETED',$user->getFullname()));
   } else {
      $errorbox->setText($translator->getMessage('ACCOUNT_EDIT_ERROR_JUST_DELETED',$user->getFullname(),$user->getUserID()));
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
   $error_string = $translator->getMessage('PROJECT_ROOM_IS_CLOSED',$context_item->getTitle());
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
   $errorbox->setText($translator->getMessage('ACCESS_NOT_GRANTED'));
   $page->add($errorbox);
   $command = 'error';
}

// first step administration form
if ( empty($command) and empty($command_delete) ) {
   $form = $class_factory->getClass(ACCOUNT_STATUS_FORM,array('environment' => $environment));
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
} elseif (isOption($command,$translator->getMessage('ADMIN_CANCEL_BUTTON')) or isOption($command,$translator->getMessage('MAIL_NOT_SEND_BUTTON'))) {
   $history = $session->getValue('history');
   $back_hop = 1;
   while ($history[$back_hop]['function'] == 'status' or $history[$back_hop]['module'] == 'mail') {
      $back_hop++;
   }
   redirect($history[$back_hop]['context'],$history[$back_hop]['module'],$history[$back_hop]['function'],$history[$back_hop]['parameter']);
} elseif ( isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON'))
           or isOption($command_delete,$translator->getMessage('COMMON_DELETE_BUTTON'))
           or isOption($command_delete,$translator->getMessage('COMMON_USER_REJECT_BUTTON'))
         ) {
   if (isOption($command_delete,$translator->getMessage('COMMON_DELETE_BUTTON'))) {

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
      // Datenschutz
      if($environment->getCurrentPortalItem()->getHideAccountname()){
      	$userid = 'XXX '.$translator->getMessage('COMMON_DATASECURITY');
      } else {
      	$userid = $user->getUserID();
      }
      
      include_once('classes/cs_mail_obj.php');
      $mail_obj = new cs_mail_obj();
      $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_USER_FORM_TITLE',$user->getFullname(),$translator->getMessage('COMMON_STEP_END')));

      $mail_subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_DELETE',$context_item->getTitle());
      $mail_body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
      $mail_body .= LF.LF;
      $mail_body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_DELETE',$userid,$context_item->getTitle());
      $mail_body .= LF.LF;
      $mail_body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());

      $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
      $mail_body .= LF.LF.$url;

      $mail_obj->setMailFormHints($translator->getMessage('USER_MAIL_ADMIN_DESC'));
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
      if ( isOption($command_delete,$translator->getMessage('COMMON_USER_REJECT_BUTTON')) ) {
         $_POST['status'] = 'close';
      }
      $form = $class_factory->getClass(ACCOUNT_STATUS_FORM,array('environment' => $environment));
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

            // reset inactivity
            $user->resetInactivity();
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
            
            $showTakeOver = true;
            if(empty($_POST['login_as'])){
               global $symfonyContainer;
               $allowModeratorTakeover = $symfonyContainer->getParameter('commsy.security.allow_moderator_takeover');
               
            	if (!$allowModeratorTakeover) {
            		$showTakeOver = false;
            	}
            } else {
                if ($_POST['login_as'] == 1) {
                    $showTakeOver = false;
                }
            }
            
            if (!$showTakeOver) {
            	$user->deactivateLoginAsAnotherUser();
            } else {
            	$user->unsetDeactivateLoginAsAnotherUser();
            }
            
            if(!empty($_POST['days_interval'])){
            	$user->setDaysForLoginAs($_POST['days_interval']);
            } else if($_POST['days_interval'] == 0){
            	$user->unsetDaysForLoginAs();
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
         if ( !empty($_POST['status_old'])
              and $_POST['status_old'] == 'request'
              and $environment->inProjectRoom()
            ) {
            if (isset($group)) {
               $user->setGroupByID($group->getItemID());
               $group->setModificatorItem($current_user);
               $group->save();
            }
         }
         $user->setChangeModificationOnSave(false);
         $user->save();

         // // if commsy user is rejected, reject all accounts in project- and community rooms
         // if ($user->isRejected() and $environment->inPortal()) {
         //    $user_list = $user->getRelatedUserList();
         //    $user_item = $user_list->getFirst();
         //    while ($user_item) {
         //       $user_item->reject();
         //       $user_item->save();
         //       $user_item = $user_list->getNext();
         //    }
         // }

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
         $mail_obj->setMailFormHeadLine($translator->getMessage('ADMIN_USER_FORM_TITLE',$user->getFullname(),$translator->getMessage('COMMON_STEP_END')));

         // change language for user
         $save_language = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($user->getLanguage());
         
         // Datenschutz
         if($environment->getCurrentPortalItem()->getHideAccountname()){
         	$userid = $translator->getMessage('MAIL_ONLY_VISIBLE_FOR',$user->getFullName());
         	$session->setValue('status', $status);
         	$session->setValue('userAccount',$user->getItemID());
         } else {
         	$userid = $user->getUserID();
         }

         if ($status == 'reject' or $status == 'close') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_LOCK',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_ACCOUNT_LOCK',$userid,$context_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
         } elseif ($status == 'user') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$userid,$context_item->getTitle());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$current_user->getFullname(),$context_item->getTitle());
         } elseif ($status == 'moderator') {
            $subject  = $translator->getMessage('MAIL_SUBJECT_USER_STATUS_MODERATOR',$context_item->getTitle());
            $body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_USER_STATUS_MODERATOR',$userid,$context_item->getTitle());
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

         $mail_obj->setMailFormHints($translator->getMessage('USER_MAIL_ADMIN_DESC'));
         $mail_obj->setSubject($mail_subject);
         $mail_obj->setContent($mail_body);
         $sender[$current_user->getFullName()] = $current_user->getEMail();
         $mail_obj->setSender($sender);
         $receiver[$user->getFullName()] = $user->getEMail();
         $mail_obj->addReceivers($receiver);


         // get back link out of history
         $history = $session->getValue('history');
         $back_hop = 1; // must be one hop, so no if-clause here
         while ( ( !empty($history[$back_hop]['function'])
                   and $history[$back_hop]['function'] == 'status'
                 )
                 or ( !empty($history[$back_hop]['module'])
                      and $history[$back_hop]['module'] == 'mail'
                    )
               ) {
            $back_hop++;
         }
         if ( !empty($history[$back_hop]['context'])
              and !empty($history[$back_hop]['module'])
              and !empty($history[$back_hop]['function'])
              and !empty($history[$back_hop]['parameter'])
            ) {
            $mail_obj->setBackLink($history[$back_hop]['context'],
                                   $history[$back_hop]['module'],
                                   $history[$back_hop]['function'],
                                   $history[$back_hop]['parameter']);
         } else {
            $mail_obj->setBackLink($environment->getCurrentContextID(),
                                   'account',
                                   'index',
                                   array());
         }
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