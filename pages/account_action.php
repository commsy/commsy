<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

function performAction ( $environment, $action_array, $post_array ) {
   // perform action
   $user_manager = $environment->getUserManager();
   $admin = $user_manager->getItem($action_array['user_item_id']);

   if ( isset($post_array['copy'])
        and !empty($post_array['copy'])
        and !in_array($action_array['user_item_id'],$action_array['selected_ids'])
        and count($action_array['selected_ids']) > 1
      ) {
      $action_array['selected_ids'][] = $action_array['user_item_id'];
   }

   foreach ( $action_array['selected_ids'] as $user_item_id ) {
      $user = $user_manager->getItem($user_item_id);

      if ( $action_array['action'] == 'USER_ACCOUNT_DELETE' ) {
         if ( $environment->inPortal() or $environment->inServer() ) {
            $authentication = $environment->getAuthenticationObject();
            $authentication->delete($user_item_id);
            unset($authentication);
         } else {

            ################################
            # FLAG: group room
            ################################
            if ( $environment->inGroupRoom() ) {
               $current_context = $environment->getCurrentContextItem();
               $group_item = $current_context->getLinkedGroupItem();
               if ( isset($group_item) and !empty($group_item) ) {
                  $project_room_item = $current_context->getLinkedProjectItem();
                  if ( isset($project_room_item) and !empty($project_room_item) ) {
                     $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                     $group_item->removeMember($project_room_user_item);
                     unset($project_room_user_item);
                  }
                  unset($project_room_item);
               }
               unset($group_item);
               unset($current_context);
            }
            ################################
            # FLAG: group room
            ################################
            $user->delete();
         }
         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_ACCOUNT_LOCK' ) {
         $user->reject();
         $user->save();

         ################################
         # FLAG: group room
         ################################
         if ( $environment->inGroupRoom() ) {
            $current_context = $environment->getCurrentContextItem();
            $group_item = $current_context->getLinkedGroupItem();
            if ( isset($group_item) and !empty($group_item) ) {
               $project_room_item = $current_context->getLinkedProjectItem();
               if ( isset($project_room_item) and !empty($project_room_item) ) {
                  $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                  $group_item->removeMember($project_room_user_item);
                  unset($group_item);
                  unset($project_room_user_item);
               }
               unset($project_room_item);
            }
            unset($current_context);
         }
         ################################
         # FLAG: group room
         ################################

         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_ACCOUNT_FREE' ) {

         // link to group 'ALL' in project rooms
         if ($environment->inProjectRoom()) {
           $group_list = $user->getGroupList();
           if ($group_list->isEmpty()) {
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
               if (isset($group)) {
                  $user->setGroupByID($group->getItemID());
                  $group->setModificatorItem($user);
                  $group->save();
                  unset($group);
               }
               unset($group_list);
               unset($group_manager);
            }
         }
         // don't change users with status user or Moderator
         if ( (!$user->isUser()) and (!$user->isModerator()) ) {
            $user->makeUser();
            $user->save();

            ################################
            # FLAG: group room
            ################################
            if ( $environment->inGroupRoom() ) {
               $current_context = $environment->getCurrentContextItem();
               $group_item = $current_context->getLinkedGroupItem();
               if ( isset($group_item) and !empty($group_item) ) {
                  $project_room_item = $current_context->getLinkedProjectItem();
                  if ( isset($project_room_item) and !empty($project_room_item) ) {
                     $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                     $group_item->addMember($project_room_user_item);
                     unset($group_item);
                     unset($project_room_user_item);
                  }
                  unset($project_room_item);
               }
               unset($group_item);
               unset($current_context);
            }
            ################################
            # FLAG: group room
            ################################

         }
         $send_to = $user->getEmail();

      } elseif ( $action_array['action'] == 'USER_STATUS_USER' ) {

         // link to group 'ALL' in project rooms
         if ($environment->inProjectRoom()) {
            $group_list = $user->getGroupList();
            if ($group_list->isEmpty()) {
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
               if (isset($group)) {
                  $user->setGroupByID($group->getItemID());
                  $group->setModificatorItem($user);
                  $group->save();
                  unset($group);
               }
               unset($group_list);
               unset($group_manager);
            }
         }

         $user->makeUser();
         $user->save();
         $send_to = $user->getEmail();

         ################################
         # FLAG: group room
         ################################
         if ( $environment->inGroupRoom() ) {
            $current_context = $environment->getCurrentContextItem();
            $group_item = $current_context->getLinkedGroupItem();
            if ( isset($group_item) and !empty($group_item) ) {
               $project_room_item = $current_context->getLinkedProjectItem();
               if ( isset($project_room_item) and !empty($project_room_item) ) {
                  $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                  $group_item->addMember($project_room_user_item);
                  unset($project_room_user_item);
                  unset($group_item);
               }
               unset($project_room_item);
            }
            unset($current_context);
            unset($group_item);
         }
         ################################
         # FLAG: group room
         ################################

      } elseif ( $action_array['action'] == 'USER_STATUS_MODERATOR' ) {

         // link to group 'ALL' in project rooms
         if ($environment->inProjectRoom()) {
            $group_list = $user->getGroupList();
            if ($group_list->isEmpty()) {
               $group_manager = $environment->getLabelManager();
               $group_manager->setExactNameLimit('ALL');
               $group_manager->setContextLimit($environment->getCurrentContextID());
               $group_manager->select();
               $group_list = $group_manager->get();
               if ($group_list->getCount() == 1) {
                  $group = $group_list->getFirst();
                  $group->setTitle('ALL'); // needed, but not good (TBD)
               }
               unset($group_list);

               // save link to the group ALL
               if (isset($group)) {
                  $user->setGroupByID($group->getItemID());
                  $group->setModificatorItem($user);
                  $group->save();
                  unset($group);
               }
               unset($group_manager);
            }
         }

         $user->makeModerator();
         $user->save();
         $send_to = $user->getEmail();

         ################################
         # FLAG: group room
         ################################
         if ( $environment->inGroupRoom() ) {
            $current_context = $environment->getCurrentContextItem();
            $group_item = $current_context->getLinkedGroupItem();
            if ( isset($group_item) and !empty($group_item) ) {
               $project_room_item = $current_context->getLinkedProjectItem();
               if ( isset($project_room_item) and !empty($project_room_item) ) {
                  $project_room_user_item = $project_room_item->getUserByUserID($user->getUserID(),$user->getAuthSource());
                  $group_item->addMember($project_room_user_item);
                  unset($project_room_user_item);
                  unset($group_item);
               }
               unset($project_room_item);
            }
            unset($current_context);
            unset($group_item);
         }
         ################################
         # FLAG: group room
         ################################

      } elseif ( $action_array['action'] == 'USER_MAKE_CONTACT_PERSON' ) {
         $user->makeContactPerson();
         $user->save();
         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON' ) {
         $user->makeNoContactPerson();
         $user->save();
         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_EMAIL_SEND' ) {
         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
         $send_to = $user->getEmail();
      } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
         $send_to = $user->getEmail();
      }

      // change task status
      if ( $action_array['action'] == 'USER_ACCOUNT_DELETE'
           or $action_array['action'] == 'USER_ACCOUNT_LOCK'
           or $action_array['action'] == 'USER_ACCOUNT_FREE'
           or $action_array['action'] == 'USER_STATUS_USER'
           or $action_array['action'] == 'USER_STATUS_MODERATOR'
         ) {
         $task_manager = $environment->getTaskManager();
         $task_list = $task_manager->getTaskListForItem($user);
         if ($task_list->getCount() > 0) {
            $task_item = $task_list->getFirst();
            while ($task_item) {
               if ($task_item->getStatus() == 'REQUEST' and ($task_item->getTitle() == 'TASK_USER_REQUEST' or $task_item->getTitle() == 'TASK_PROJECT_ROOM_REQUEST')) {
                  $task_item->setStatus('CLOSED');
                  $task_item->save();
               }
               $task_item = $task_list->getNext();
            }
         }
         unset($task_list);
         unset($task_item);
         unset($task_manager);
      }

      // if commsy user is rejected, reject all accounts in projectrooms and community rooms
      if ( $user->isRejected() and $environment->inPortal() ) {
         $user_list = $user->getRelatedUserList();
         $user_item = $user_list->getFirst();
         while ($user_item) {
            $user_item->reject();
            $user_item->save();
            $user_item = $user_list->getNext();
         }
         unset($user_list);
         unset($user_item);
      }

      // send email
      if ( ( isset($post_array['with_mail']) and $post_array['with_mail'] == '1') ) {
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_from_email($admin->getEmail());
         $mail->set_from_name($admin->getFullname());
         $mail->set_reply_to_email($admin->getEmail());
         $mail->set_reply_to_name($admin->getFullname());

         // subject and body
         // language
         $translator = $environment->getTranslationObject();
         $room  = $environment->getCurrentContextItem();
         $url_to_room = LF.LF;
         $url_to_room .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$environment->getCurrentContextID();
         $subject = $_POST['subject'];
         $content = $_POST['content'];
         $content = str_replace('%1',$user->getFullname(),$content);

         // now prepare for each action separately
         if ( $action_array['action'] == 'USER_ACCOUNT_DELETE'
              or $action_array['action'] == 'USER_ACCOUNT_LOCK'
              or $action_array['action'] == 'USER_ACCOUNT_FREE'
              or $action_array['action'] == 'USER_STATUS_USER'
              or $action_array['action'] == 'USER_STATUS_MODERATOR'
              or $action_array['action'] == 'USER_UNMAKE_CONTACT_PERSON'
              or $action_array['action'] == 'USER_MAKE_CONTACT_PERSON'
            ) {
            $content = str_replace('%2',$user->getUserID(),$content);
            $content = str_replace('%3',$room->getTitle(),$content);
         } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_PASSWORD' ) {
            $content = str_replace('%2',$room->getTitle(),$content);
            $content = str_replace('%3',$user->getUserID(),$content);
         } elseif ( $action_array['action'] == 'USER_EMAIL_ACCOUNT_MERGE' ) {
            $account_text = '';
            $user_manager->resetLimits();
            $user_manager->setContextLimit($environment->getCurrentContextID());
            $user_manager->setUserLimit();
            $user_manager->setSearchLimit($user->getEmail());
            $user_manager->select();
            $user_list = $user_manager->get();
            if (!$user_list->isEmpty()) {
               if ($user_list->getCount() > 1) {
                  $first = true;
                  $user_item = $user_list->getFirst();
                  while ($user_item) {
                     if ($first) {
                        $first = false;
                     } else {
                        $account_text .= LF;
                     }
                     $account_text .= $user_item->getUserID();
                     $user_item = $user_list->getNext();
                  }
               } else {
                  include_once('functions/error_functions.php');
                  trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
               }
            } else {
               include_once('functions/error_functions.php');
               trigger_error('that is impossible, list must be greater than one',E_USER_WARNING);
            }
            $content = str_replace('%2',$user->getEmail(),$content);
            $content = str_replace('%3',$room->getTitle(),$content);
            $content = str_replace('%4',$account_text,$content);
         }

         unset($translator);
         unset($room);

         if ( isset($subject) and !empty($subject) ) {
            $mail->set_subject($subject);
         }
         if ( isset($content) and !empty($content) ) {
            $mail->set_message($content);
         }
         $mail->set_to($send_to);

         // cc / bcc
         $cc_string = '';
         $bcc_string = '';
         $cc_array = array();
         $bcc_array = array();
         if (isset($post_array['cc']) and $post_array['cc'] == 'cc') {
            $cc_array[] = $admin->getEmail();
         }
         if (isset($post_array['bcc']) and $post_array['bcc'] == 'bcc') {
            $bcc_array[] = $admin->getEmail();
         }
         if (isset($post_array['cc_moderator']) and $post_array['cc_moderator'] == 'cc_moderator') {
            $current_context = $environment->getCurrentContextItem();
            $mod_list = $current_context->getModeratorList();
            if (!$mod_list->isEmpty()) {
               $moderator_item = $mod_list->getFirst();
               while ($moderator_item) {
                  $email = $moderator_item->getEmail();
                  if (!empty($email)) {
                     $cc_array[] = $email;
                  }
                  unset($email);
                  $moderator_item = $mod_list->getNext();
               }
            }
            unset($current_context);
         }
         if (isset($post_array['bcc_moderator']) and $post_array['bcc_moderator'] == 'bcc_moderator') {
            $current_context = $environment->getCurrentContextItem();
            $mod_list = $current_context->getModeratorList();
            if (!$mod_list->isEmpty()) {
               $moderator_item = $mod_list->getFirst();
               while ($moderator_item) {
                  $email = $moderator_item->getEmail();
                  if (!empty($email)) {
                     $bcc_array[] = $email;
                  }
                  unset($email);
                  $moderator_item = $mod_list->getNext();
               }
            }
            unset($current_context);
         }

         if ( isset($post_array['copy'])
              and !empty($post_array['copy'])
              and !in_array($action_array['user_item_id'],$action_array['selected_ids'])
              and count($action_array['selected_ids']) == 1
             ) {
             $cc_array[] = $admin->getEmail();
         }


         if (!empty($cc_array)) {
            $cc_array = array_unique($cc_array);
         }
         if (!empty($bcc_array)) {
            $bcc_array = array_unique($bcc_array);
         }
         $cc_string = implode(",",$cc_array);
         $bcc_string = implode(",",$bcc_array);
         unset($cc_array);
         unset($bcc_array);
         if (!empty($cc_string)) {
            $mail->set_cc_to($cc_string);
         }
         if (!empty($bcc_string)) {
            $mail->set_bcc_to($bcc_string);
         }
         unset($cc_string);
         unset($bcc_string);

         $mail->send();
         unset($mail);
      }

      unset($user);
   }
   unset($user_manager);
   unset($admin);
}

$translator = $environment->getTranslationObject();
$session_item = $environment->getSessionItem();
$action_array = $session_item->getValue('index_action');

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if ( $command != 'error' ) {
   if ( isOption($command,$translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      redirect($action_array['backlink']['cid'],$action_array['backlink']['mod'],$action_array['backlink']['fct'],$action_array['backlink']['par']);
   } else {

      include_once('classes/cs_account_action_form.php');
      $form = new cs_account_action_form($environment);

      // init display data
      if ( !empty($_POST) ) {     // second call of form: set post data
         $form->setFormPost($_POST);
      }

      $form->setActionArray($action_array);
      $form->prepareForm();
      $form->loadValues();

      $temp = $action_array['action'];
      $tempMessage = "";
      switch( $temp )
      {
         case 'USER_ACCOUNT_DELETE':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_DELETE_BUTTON');
            break;
         case 'USER_ACCOUNT_FREE':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_FREE_BUTTON');
            break;
         case 'USER_ACCOUNT_LOCK':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_ACCOUNT_LOCK_BUTTON');
            break;
         case 'USER_MAKE_CONTACT_PERSON':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_MAKE_CONTACT_PERSON_BUTTON');
            break;
         case 'USER_STATUS_MODERATOR':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_MODERATOR_BUTTON');
            break;
         case 'USER_STATUS_USER':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_STATUS_USER_BUTTON');
            break;
         case 'USER_UNMAKE_CONTACT_PERSON':
            $tempMessage = $translator->getMessage('INDEX_ACTION_PERFORM_USER_UNMAKE_CONTACT_PERSON_BUTTON');
            break;
         default:
            $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR');
            break;
      }

      if ( !empty($command)
           and ( isOption($command,$tempMessage)
                 or isOption($command,$translator->getMessage('INDEX_ACTION_SEND_MAIL_BUTTON'))
               )
         ) {
         $correct = $form->check();
         if ( $correct
              or !isset($_POST['with_mail'])
            ) {
            performAction($environment,$action_array,$_POST);
            redirect($action_array['backlink']['cid'],$action_array['backlink']['mod'],$action_array['backlink']['fct'],$action_array['backlink']['par']);
         }
      }

      // display form
      if ( $environment->getCurrentModule() == 'account') {
         include_once('classes/cs_configuration_form_view.php');
         $form_view = new cs_configuration_form_view($environment,'action','');
      } else {
         include_once('classes/cs_form_view.php');
         $form_view = new cs_form_view($environment,'action','');
      }
      $params = array();
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),'action',$params));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ){
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
   }
}
?>