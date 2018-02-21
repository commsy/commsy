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
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

include_once('classes/cs_left_page.php');
class cs_become_member_page extends cs_left_page {

   function __construct($environment) {
      cs_left_page::__construct($environment);
   }

   function execute () {
      $success = false;

      $class_params= array();
      $class_params['environment'] = $this->_environment;
      $form = $class_factory->getClass(BECOME_MEMBER_FORM,$class_params);
      unset($class_params);
      // Load form data from postvars
      if ( !empty($this->_post_vars) ) {
         $form->setFormPost($this->_post_vars);
      }
      $form->prepareForm();
      $form->loadValues();

      $user_manager = $this->_environment->getUserManager();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_manager->setUserIDLimit($current_user->getUserID());
      $user_manager->setAuthSourceLimit($current_user->getAuthSource());
      $user_manager->select();
      $user_list = $user_manager->get();
      $room_user = NULL;
      if ($user_list->isNotEmpty()) {
        $room_user = $user_list->getFirst();
      }


      // cancel
      if ( !empty($this->_command)
          and ( isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
                or isOption($this->_command, $this->_translator->getMessage('COMMON_FORWARD_BUTTON'))
              )
         ){
         $this->_redirect_back();
      }

      // Save item
      elseif ( !empty($this->_command)
              and isOption($this->_command, $this->_translator->getMessage('USER_BECOME_MEMBER_BUTTON'))
              and !isset($room_user)
             ) {
        $correct = $form->check();
        if ( $correct ) {
            // build new user_item
            $current_user = $this->_environment->getCurrentUserItem();
            $private_room_user_item = $current_user->getRelatedPrivateRoomUserItem();
            if ( isset($private_room_user_item) ) {
               $user_item = $private_room_user_item->cloneData();
               $picture = $private_room_user_item->getPicture();
            } else {
               $user_item = $current_user;
               $picture = '';
            }

            //check room_settings
            $current_context = $this->_environment->getCurrentContextItem();
            if ( !$current_context->checkNewMembersNever()
                 and !$current_context->checkNewMembersWithCode()
               ) {
               $user_item->request();
               $check_message = 'YES'; // for mail body
            } else {
               $user_item->makeUser(); // for mail body
               $check_message = 'NO';
            }
            $user_item->setContextID($current_context->getItemID());
            if (!empty($picture)) {
               $value_array = explode('_',$picture);
               $value_array[0] = 'cid'.$user_item->getContextID();
               $new_picture_name = implode('_',$value_array);
               $disc_manager = $this->_environment->getDiscManager();
               $disc_manager->copyImageFromRoomToRoom($picture,$user_item->getContextID());
               $user_item->setPicture($new_picture_name);
            }

            $portal_user = $user_item->getRelatedPortalUserItem();
            $conf = $portal_user->getConfigurationHideMailByDefault();
            if(!empty($conf)) {
              // use default user config
              if($conf) {
                // hide 
                $user_item->setDefaultMailNotVisible();
              } else {
                $user_item->setDefaultMailVisible();
              }
            } else {
              // default portal config
              if($conf){
                $user_item->setDefaultMailNotVisible();
              } else {
                $user_item->setDefaultMailVisible();
              }
            }
            

            $user_item->save();
            $user_item->setCreatorID2ItemID();

            // save task
            if ( !$current_context->checkNewMembersNever()
                 and !$current_context->checkNewMembersWithCode()
               ) {
               $task_manager = $this->_environment->getTaskManager();
               $task_item = $task_manager->getNewItem();
               $task_item->setCreatorItem($user_item);
               $task_item->setContextID($current_context->getItemID());
               $task_item->setTitle('TASK_USER_REQUEST');
               $task_item->setStatus('REQUEST');
               $task_item->setItem($user_item);
               $task_item->save();
            }

            // send email to moderators if necessary
            $user_list = $current_context->getModeratorList();
            $email_addresses = array();
            $moderator_item = $user_list->getFirst();
            $recipients = '';
            $language = $current_context->getLanguage();
            while ($moderator_item) {
               $want_mail = $moderator_item->getAccountWantMail();
               if (!empty($want_mail) and $want_mail == 'yes') {
                  if ($language == 'user' and $moderator_item->getLanguage() != 'browser') {
                     $email_addresses[$moderator_item->getLanguage()][] = $moderator_item->getEmail();
                  } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                      $email_addresses[$this->_environment->getSelectedLanguage()][] = $moderator_item->getEmail();
                  } else {
                     $email_addresses[$language][] = $moderator_item->getEmail();
                  }
                  $recipients .= $moderator_item->getFullname().LF;
               }
               $moderator_item = $user_list->getNext();
            }
           $save_language = $this->_translator->getSelectedLanguage();
           foreach ($email_addresses as $key => $value) {
              $this->_translator->setSelectedLanguage($key);
               if (count($value) > 0) {
                  $subject = $this->_translator->getMessage('USER_JOIN_CONTEXT_MAIL_SUBJECT',$user_item->getFullname(),$current_context->getTitle());
                  $body  = $this->_translator->getMessage('MAIL_AUTO',$this->_translator->getDateInLang(getCurrentDateTimeInMySQL()),$this->_translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                  $body .= LF.LF;
                  $temp_language = $user_item->getLanguage();
                  if ($temp_language == 'browser') {
                      $temp_language = $this->_environment->getSelectedLanguage();
                  }
                  $tempMessage = '';
                  switch ( mb_strtoupper($temp_language, 'UTF-8') ){
                     case 'DE':
                        $tempMessage = $this->_translator->getMessage('DE');
                        break;
                     case 'EN':
                        $tempMessage = $this->_translator->getMessage('EN');
                        break;
                     case 'RU':
                        $tempMessage = $this->_translator->getMessage('RU');
                        break;
                     default:
                        // $tempMessage = $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR'.' cs_become_member_page(168) ');
                        break;
                  }
                  $body .= $this->_translator->getMessage('USER_GET_MAIL_BODY',
                                                          $user_item->getFullname(),
                                                          $user_item->getUserID(),
                                                          $user_item->getEmail(),
                                                          $tempMessage
                                                         );
                  $body .= LF.LF;
                  $tempMessage = "";
                  switch( $check_message )
                  {
                     case 'YES':
                        $tempMessage = $this->_translator->getMessage('USER_GET_MAIL_STATUS_YES');
                        break;
                     case 'NO':
                        $tempMessage = $this->_translator->getMessage('USER_GET_MAIL_STATUS_NO');
                        break;
                     default:
                        break;
                  }
                  $body .= $tempMessage;
                  $body .= LF.LF;

                  if (!empty($this->_post_vars['description'])) {
                     $body .= $this->_translator->getMessage('MAIL_COMMENT_BY',$user_item->getFullname(),$this->_post_vars['description']);
                     $body .= LF.LF;
                  }
                  $body .= $this->_translator->getMessage('MAIL_SEND_TO',$recipients);
                  $body .= LF;
                  if (!$user_item->isUser()) {
                      $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_context->getItemID().'&mod=account&fct=index'.'&selstatus=1';
                  } else {
                      $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_context->getItemID();
                  }
                  include_once('classes/cs_mail.php');
                  $mail = new cs_mail();
                  $mail->set_to(implode(',',$value));

                  global $symfonyContainer;
                  $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                  $mail->set_from_email($emailFrom);

                  $current_context = $this->_environment->getCurrentContextItem();
                  $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));
                  $mail->set_reply_to_name($user_item->getFullname());
                  $mail->set_reply_to_email($user_item->getEmail());
                  $mail->set_subject($subject);
                  $mail->set_message($body);
                  $mail->send();
               }
           }
           $this->_translator->setSelectedLanguage($save_language);

            // send email to user when account is free automatically
            if ($user_item->isUser()) {

               $mod_text = '';
               $mod_list = $current_context->getContactModeratorList();
               if (!$mod_list->isEmpty()) {
                  $mod_item = $mod_list->getFirst();
                  $contact_moderator = $mod_item;
                  while ($mod_item) {
                     if (!empty($mod_text)) {
                        $mod_text .= ','.LF;
                     }
                     $mod_text .= $mod_item->getFullname();
                     $mod_text .= ' ('.$mod_item->getEmail().')';
                     $mod_item = $mod_list->getNext();
                  }
               }

               // email texts
               $subject = $this->_translator->getMessage('MAIL_SUBJECT_USER_STATUS_USER',$current_context->getTitle());
               $body  = $this->_translator->getMessage('MAIL_AUTO',$this->_translator->getDateInLang(getCurrentDateTimeInMySQL()),$this->_translator->getTimeInLang(getCurrentDateTimeInMySQL()));
               $body .= LF.LF;
               $body .= $this->_translator->getEmailMessage('MAIL_BODY_HELLO',$user_item->getFullname());
               $body .= LF.LF;
               $body .= $this->_translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$user_item->getUserID(),$current_context->getTitle());
               $body .= LF.LF;
               if ( empty($contact_moderator) ) {
                  $body .= $this->_translator->getMessage('SYSTEM_MAIL_REPLY_INFO').LF;
                  $body .= $mod_text;
                  $body .= LF.LF;
               } else {
                  $body .= $this->_translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$current_context->getTitle());
                  $body .= LF.LF;
               }
               $body .= LF.LF;
               $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$current_context->getItemID();

               // send mail to user
               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to($user_item->getEmail());
               $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_context->getTitle()));

               global $symfonyContainer;
               $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
               $mail->set_from_email($emailFrom);

               if (!empty($contact_moderator)) {
                  $mail->set_reply_to_email($contact_moderator->getEmail());
                  $mail->set_reply_to_name($contact_moderator->getFullname());
               }
               $mail->set_subject($subject);
               $mail->set_message($body);
               $mail->send();

               // redirect back
               $this->_redirect_back();
           } else {
              $form->showAccountNotOpen($user_item);
           }
        }
     }

      if (isset($room_user)) {
        $form->showAccountNotOpen($room_user);
      }
      return $this->_show_form($form);
   }
}
?>