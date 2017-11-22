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

include_once('classes/cs_left_page.php');
class cs_account_forget_page extends cs_left_page {

   function __construct($environment) {
      cs_left_page::__construct($environment);
   }

   function execute () {
      $success = false;

      $form = $this->_class_factory->getClass(ACCOUNT_FORGET_FORM,array('environment' => $this->_environment));
      // Load form data from postvars
      if ( !empty($this->_post_vars) ) {
         $form->setFormPost($this->_post_vars);
      }
      $form->prepareForm();
      $form->loadValues();

      // cancel
      if ( !empty($this->_command)
           and ( isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
                 or isOption($this->_command, $this->_translator->getMessage('COMMON_FORWARD_BUTTON'))
               )
         ) {
         $this->_redirect_back();
      }

      // get accounts
      if ( !empty($this->_command)
           and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_SEND_BUTTON'))
         ) {
         $correct = $form->check();
         if ( $correct ) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
            /*
             * email must match(not only be like) database entry to avoid finding to much identifications
             */
            //$user_manager->setSearchLimit($this->_post_vars['email']);
            $user_manager->setEmailLimit($this->_post_vars['email']);
            $user_manager->select();
            $user_list = $user_manager->get();
            
            $account_text = '';
            $user_fullname = ' ';

            $portal_item = $this->_environment->getCurrentPortalItem();
            $user_item = $user_list->getFirst();
            $show_auth_source = false;
            while ($user_item) {
               if ( isset($auth_source_id)
                    and $auth_source_id != $user_item->getAuthSource()
                  ) {
                  $show_auth_source = true;
                  break;
               } else {
                  $auth_source_id = $user_item->getAuthSource();
               }
               $user_item = $user_list->getNext();
            }

            $first = true;
            $user_item = $user_list->getFirst();
            while ($user_item) {
               if ($first) {
                  $first = false;
               } else {
                  $account_text .= LF;
               }
               $account_text .= $user_item->getUserID();
               if ( $show_auth_source ) {
                  $auth_souce_item = $portal_item->getAuthSource($user_item->getAuthSource());
                  $account_text .= ' ('.$auth_souce_item->getTitle().')';
               }
               $user_fullname = $user_item->getFullname();
               $user_item = $user_list->getNext();
            }

            $user_email = $this->_post_vars['email'];

            // send email
            $context_item = $this->_environment->getCurrentPortalItem();
            $mod_text = '';
            $mod_list = $context_item->getContactModeratorList();
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

            $translator = $this->_environment->getTranslationObject();
            include_once('classes/cs_mail.php');
            $mail = new cs_mail();
            $mail->set_to($user_email);
            $server_item = $this->_environment->getServerItem();
            $default_sender_address = $server_item->getDefaultSenderAddress();
            if (!empty($default_sender_address)) {
               $mail->set_from_email($default_sender_address);
            } else {
               $mail->set_from_email('@');
            }
            if (isset($contact_moderator)) {
               $mail->set_reply_to_email($contact_moderator->getEmail());
               $mail->set_reply_to_name($contact_moderator->getFullname());
            }
            $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$context_item->getTitle()));
            $mail->set_subject($translator->getMessage('USER_ACCOUNT_FORGET_MAIL_SUBJECT',$context_item->getTitle()));
            $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_fullname);
            $body .= LF.LF;
            $body .= $this->_translator->getMessage('USER_ACCOUNT_FORGET_MAIL_BODY',$context_item->getTitle(),$account_text);
            $body .= LF.LF;
            if ( empty($contact_moderator) ) {
               $body .= $translator->getMessage('SYSTEM_MAIL_REPLY_INFO').LF;
               $body .= $mod_text;
               $body .= LF.LF;
            } else {
               $body .= $translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$context_item->getTitle());
               $body .= LF.LF;
            }
            $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();
            $mail->set_message($body);
            if ($mail->send()) {
            // show little status page that mail was sent successful
               $form->showMailSent($user_email);
            } else {
            // show little status page that mail was not sent successful
               $form->showMailFailure();
            }
         }
      }
      return $this->_show_form($form);
   }
}
?>