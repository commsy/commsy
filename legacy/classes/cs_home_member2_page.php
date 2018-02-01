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

include_once('classes/cs_left_page.php');
class cs_home_member2_page extends cs_left_page {

   function cs_home_member2_page ($environment) {
      $this->cs_left_page($environment);
   }

   function execute () {

      $success = false;

      $class_params= array();
      $class_params['environment'] = $this->_environment;
      $form = $this->_class_factory->getClass(HOME_MEMBER2_FORM,$class_params);
      unset($class_params);

      // Load form data from postvars
      if ( !empty($this->_post_vars) ) {
         $form->setFormPost($this->_post_vars);
      }
      if ( !empty($this->_get_vars) ) {
         $form->setFormGet($this->_get_vars);
      }
      $form->prepareForm();
      $form->loadValues();

      // cancel
      if ( !empty($this->_command)
           and isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
         ) {
         $this->_redirect_back();
      }

      // Save item
      if ( !empty($this->_command)
           and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_GET_BUTTON'))
         ) {
         $correct = $form->check();
         if ( $correct ) {
            if ( isset($this->_post_vars['auth_source']) and !empty($this->_post_vars['auth_source']) ) {
               $auth_source = $this->_post_vars['auth_source'];
            } else {
               include_once('functions/error_functions.php');
               trigger_error('lost auth source',E_USER_ERROR);
            }

            $portal_item = $this->_environment->getCurrentPortalItem();
            $auth_source_item = $portal_item->getAuthSource($auth_source);
            $redirect_to_login = true;

            // CAS
            if ( $auth_source_item->getSourceType() == 'CAS' ) {
               $redirect_to_login = false;
            }

            // typo3
            elseif ( $auth_source_item->getSourceType() == 'Typo3' ) {
               $redirect_to_login = false;
            }

            // joomla!
            elseif ( $auth_source_item->getSourceType() == 'Joomla' ) {
               $redirect_to_login = false;
            }

            // mysql allg.
            elseif ( $auth_source_item->getSourceType() == 'MYSQL' ) {
               $redirect_to_login = false;
            }

            // LDAP
            elseif ( $auth_source_item->getSourceType() == 'LDAP' ) {
               $redirect_to_login = false;
            }

            // CommSy default
            elseif ( $auth_source_item->isCommSyDefault() ) {
               $redirect_to_login = false;
            }
            if ( $redirect_to_login ) { // if someting is wrong
               $params = $this->_environment->getCurrentParameterArray();
               unset($params['cs_modus']);
               redirect($this->_environment->getCurrentContextID(),'home','index',$params);
               exit();
            }

            // Create new item
            $authentication = $this->_environment->getAuthenticationObject();
            $new_account = $authentication->getNewItem();
            $new_account->setUserID($this->_post_vars['user_id']);
            $new_account->setFirstname($this->_post_vars['firstname']);
            $new_account->setLastname($this->_post_vars['lastname']);
            $new_account->setLanguage($this->_post_vars['language']);
            $new_account->setEmail($this->_post_vars['email']);
            $new_account->setPortalID($this->_environment->getCurrentPortalID());
            $new_account->setAuthSourceID($auth_source);
            
            $save_only_user = true;
            $authentication->save($new_account,$save_only_user);

            $portal_user = $authentication->getUserItem();
            $error = $authentication->getErrorMessage();

           if (empty($error)) {
              $success = true;

              $portal_item = $this->_environment->getCurrentPortalItem();
              if ($this->_environment->getCurrentContextItem()->withAGB() and $this->_environment->getCurrentContextItem()->withAGBDatasecurity()){
              	if($this->_post_vars['terms_of_use']){
              		$portal_user->setAGBAcceptance();
              	}
              }
              
              if($portal_item->getConfigurationHideMailByDefault()) {
                // hide 
                $portal_user->setDefaultMailNotVisible();
              } else {
                $portal_user->setDefaultMailVisible();
              }

              #if ( $portal_item->checkNewMembersAlways()
              #     or $portal_item->checkNewMembersSometimes()
              #   ) {
              #   // portal: generate and save task
              #   $task_manager = $environment->getTaskManager();
              #   $task_item = $task_manager->getNewItem();
              #   $task_item->setContextID($portal_item->getItemID());
              #   $task_item->getCreatorItem($portal_user);
              #   $task_item->setTitle('TASK_USER_REQUEST');
              #   $task_item->setStatus('REQUEST');
              #   $task_item->setItem($portal_user);
              #   $task_item->save();
              #}

              // portal: send mail to moderators in different languages
              $user_list = $portal_item->getModeratorList();
              $email_addresses = array();
              $user_item = $user_list->getFirst();
              $recipients = '';
              $language = $portal_item->getLanguage();
              while ($user_item) {
                 $want_mail = $user_item->getAccountWantMail();
                 if (!empty($want_mail) and $want_mail == 'yes') {
                    if ($language == 'user'  and $user_item->getLanguage() != 'browser') {
                       $email_addresses[$user_item->getLanguage()][] = $user_item->getEmail();
                    } elseif ($language == 'user' and $user_item->getLanguage() == 'browser') {
                        $email_addresses[$this->_environment->getSelectedLanguage()][] = $user_item->getEmail();
                    } else {
                       $email_addresses[$language][] = $user_item->getEmail();
                    }
                    $recipients .= $user_item->getFullname().LF;
                 }
                 $user_item = $user_list->getNext();
              }
              $save_language = $this->_translator->getSelectedLanguage();
              foreach ($email_addresses as $key => $value) {
                 $this->_translator->setSelectedLanguage($key);
                 if (count($value) > 0) {
                    include_once('classes/cs_mail.php');
                    $mail = new cs_mail();
                    $mail->set_to(implode(',',$value));

                     global $symfonyContainer;
                     $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                     $mail->set_from_email($emailFrom);

                    $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));
                    $mail->set_reply_to_name($portal_user->getFullname());
                    $mail->set_reply_to_email($portal_user->getEmail());
                    $mail->set_subject($this->_translator->getMessage('USER_GET_MAIL_SUBJECT',$portal_user->getFullname()));
                    $body = $this->_translator->getMessage('MAIL_AUTO',$this->_translator->getDateInLang(getCurrentDateTimeInMySQL()),$this->_translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                    $body .= LF.LF;
                    $temp_language = $portal_user->getLanguage();
                    if ($temp_language == 'browser') {
                       $temp_language = $this->_environment->getSelectedLanguage();
                    }
                    $body .= $this->_translator->getMessage('USER_GET_MAIL_BODY',
                                                            $portal_user->getFullname(),
                                                            $portal_user->getUserID(),
                                                            $portal_user->getEmail(),
                                                            $this->_translator->getLanguageLabelTranslated($temp_language)
                                                           );
                    unset($temp_language);
                    $body .= LF.LF;
#                    if ( !$portal_item->checkNewMembersNever()
#                         or $portal_item->checkNewMembersSometimes()
#                       ) {
#                       $check_message = 'YES';
#                    } else {
                        $check_message = 'NO';
#                    }

                     switch ( $check_message )
                     {
                         case 'YES':
                           $body .= $this->_translator->getMessage('USER_GET_MAIL_STATUS_YES');
                           break;
                         case 'NO':
                           $body .= $this->_translator->getMessage('USER_GET_MAIL_STATUS_NO');
                           break;
                         default:
                           break;
                     }

                    $body .= LF.LF;
                    if (!empty($_POST['explanation'])) {
                       $body .= $this->_translator->getMessage('MAIL_COMMENT_BY',$portal_user->getFullname(),'');
                       $body .= LF.LF;
                    }
                    $body .= $this->_translator->getMessage('MAIL_SEND_TO',$recipients);
                    $body .= LF;
                    $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$portal_item->getItemID().'&mod=account&fct=index'.'&selstatus=1';
                    $mail->set_message($body);
                     $mail->send();
                  }
               }
              $this->_translator->setSelectedLanguage($save_language);

              // activate user
              #$login = false;
              #if ($portal_item->checkNewMembersNever()) {
                 $portal_user->makeUser();
                 $portal_user->save();
                 $current_user = $portal_user;
                 $this->_environment->setCurrentUserItem($current_user);
                 #$this->setCurrentUser($this->_environment->getCurrentUserItem());
                 #$login = true;
              #}

              // send email to user
              if ($current_user->isUser()) {

                  if (!$this->_environment->inPortal()) {
                     // change translation context
                     $this->_translator->setContext('portal');
                     $current_portal = $this->_environment->getCurrentPortalItem();
                     $this->_translator->setEmailTextArray($current_portal->getEmailTextArray());
                     $this->_translator->setSelectedLanguage($current_portal->getLanguage());
                  }

                 $mod_text = '';
                  $mod_list = $portal_item->getContactModeratorList();
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

                 $language = getSelectedLanguage();
                 $this->_translator->setSelectedLanguage($language);
                 include_once('classes/cs_mail.php');
                 $mail = new cs_mail();
                 $mail->set_to($current_user->getEmail());
                 $mail->set_from_name($this->_translator->getMessage('SYSTEM_MAIL_MESSAGE',$portal_item->getTitle()));

                  global $symfonyContainer;
                  $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
                  $mail->set_from_email($emailFrom);

                 if (!empty($contact_moderator)) {
                    $mail->set_reply_to_email($contact_moderator->getEmail());
                    $mail->set_reply_to_name($contact_moderator->getFullname());
                 }
                 $mail->set_subject($this->_translator->getMessage('MAIL_SUBJECT_USER_ACCOUNT_FREE',$portal_item->getTitle()));
                 $body = $this->_translator->getMessage('MAIL_AUTO',$this->_translator->getDateInLang(getCurrentDateTimeInMySQL()),$this->_translator->getTimeInLang(getCurrentDateTimeInMySQL()));
                 $body .= LF.LF;
                 $body .= $this->_translator->getEmailMessage('MAIL_BODY_HELLO',$current_user->getFullname());
                 $body .= LF.LF;
                 $body .= $this->_translator->getEmailMessage('MAIL_BODY_USER_STATUS_USER',$portal_user->getUserID(),$portal_item->getTitle());
                 $body .= LF.LF;
                 if ( empty($contact_moderator) ) {
                    $body .= $this->_translator->getMessage('SYSTEM_MAIL_REPLY_INFO').LF;
                    $body .= $mod_text;
                    $body .= LF.LF;
                 } else {
                    $body .= $this->_translator->getEmailMessage('MAIL_BODY_CIAO',$contact_moderator->getFullname(),$portal_item->getTitle());
                    $body .= LF.LF;
                 }
                 $body .= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->_environment->getCurrentContextID();
                 $mail->set_message($body);
                 $mail->send();

                  if (!$this->_environment->inPortal()) {
                     // change translation context back
                     $current_context = $this->_environment->getCurrentContextItem();
                     if ($current_context->isProjectRoom()) {
                        $this->_translator->setContext('project');
                     } else {
                        $this->_translator->setContext('community');
                     }
                     $this->_translator->setEmailTextArray($current_context->getEmailTextArray());
                     $this->_translator->setSelectedLanguage($current_context->getLanguage());
                  }
              }

              // login in user
              #if ($login) {
                 $session = $this->_environment->getSessionItem();
                 #if ($session->issetValue('last_step')) {
                    #$last_step = $session->getValue('last_step');
                    #$session->unsetValue('last_step');
                 #}
                 $cookie = $session->getValue('cookie');
                 include_once('classes/cs_session_item.php');
                 global $session; // for PHP5 and TBD !!!!!!!!!!
                 $session = new cs_session_item();
                 $session->createSessionID($_POST['user_id']);
                 if ($cookie == '1') {
                    $session->setValue('cookie',2);
                 } else {
                    $session->setValue('cookie',0);
                 }

               // save portal id in session to be sure, that user didn't
               // switch between portals
               $session->setValue('commsy_id',$this->_environment->getCurrentPortalID());

               // auth_source
               if ( empty($auth_source) ) {
                  $auth_source = $authentication->getAuthSourceItemID();
               }
               $session->setValue('auth_source',$auth_source);
               $this->_environment->setSessionItem($session);
            }
         }
      }
      if (!$success) {
         return $this->_show_form($form);
      } else {
         $this->_redirect_back();
      }
   }
}
?>