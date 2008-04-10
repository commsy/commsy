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
class cs_password_forget_page extends cs_left_page {

   function cs_password_forget_page ($environment) {
      $this->cs_left_page($environment);
   }

   function execute () {
      $success = false;

      include_once('classes/cs_password_forget_form.php');
      $form = new cs_password_forget_form($this->_environment);
      // Load form data from postvars
      if ( !empty($this->_post_vars) ) {
         $form->setFormPost($this->_post_vars);
      }
      $form->prepareForm();
      $form->loadValues();

      // cancel
      if ( !empty($this->_command)
	  and ( isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
	        or isOption($this->_command, $this->_translator->getMessage('COMMON_FORWARD_BUTTON')))
	) {
         $this->_redirect_back();
      }

      // Save item
      if ( !empty($this->_command)
	  and isOption($this->_command, $this->_translator->getMessage('PASSWORD_GENERATE_BUTTON'))
	) {
	$correct = $form->check();
         if ( $correct ) {
            // generate password
            srand((double)microtime()*1000000);
            $password = "";
            for ($i=0; $i<8; $i++) {
               $choice = array();
               $choice[0] = chr(rand(65,90));
               $choice[1] = chr(rand(97,122));
               $password .= $choice[rand(0,1)];
            }

            // save password
            $authentication_item = $this->_environment->getAuthenticationObject();
            $auth_manager = $authentication_item->getAuthManager($this->_post_vars['auth_source']);
            $auth_manager->changePassword($this->_post_vars['user_id'],$password);
            $error_number = $auth_manager->getErrorNumber();

            $user_manager = $this->_environment->getUserManager();
            $user_manager->setUserIDLimit($this->_post_vars['user_id']);
            $user_manager->setAuthSourceLimit($this->_post_vars['auth_source']);
            $user_manager->select();
            $user_list = $user_manager->get();
	   if ($user_list->isNotEmpty()) {
               $user = $user_list->getFirst();
               $user_email = $user->getEmail();
               $user_fullname = $user->getFullname();
	   } else {
	      $auth_item = $auth_manager->getItem($this->_post_vars['user_id']);
	      if (isset($auth_item)) {
                  $user_email = $auth_item->getEmail();
                  $user_fullname = $auth_item->getFullname();
		 if ( empty($user_email) ) {
		    include_once('functions/error_functions.php');trigger_error('no email adress found for userid "'.$this->_post_vars['user_id'].'"',E_USER_ERROR);
		 }
	      } else {
		 include_once('functions/error_functions.php');trigger_error('no email adress found for userid "'.$this->_post_vars['user_id'].'"',E_USER_ERROR);
	      }
	   }

	   // send email
            $context_item = $this->_environment->getCurrentPortalItem();
            $mod_text = '';
            $mod_list = $context_item->getModeratorList();
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
            if (!empty($contact_moderator)) {
               $mail->set_reply_to_email($contact_moderator->getEmail());
               $mail->set_reply_to_name($contact_moderator->getFullname());
            }
            $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$context_item->getTitle()));
            $mail->set_subject($translator->getMessage('USER_PASSWORD_MAIL_SUBJECT',$context_item->getTitle()));
            $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
            $body .= LF.LF;
            $body .= $translator->getEmailMessage('MAIL_BODY_HELLO',$user_fullname);
            $body .= LF.LF;
            $body .= $translator->getMessage('USER_PASSWORD_MAIL_BODY',$context_item->getTitle(),$password);
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