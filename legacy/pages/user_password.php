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

$class_factory->includeClass(FORM);
include_once('functions/text_functions.php');

$authentication = $environment->getAuthenticationObject();
$current_module = $environment->getCurrentModule();

// Get the translator object
$translator = $environment->getTranslationObject();

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}
if (!empty($_GET['iid'])) {
   $iid = $_GET['iid'];
} elseif (!empty($_POST['iid'])) {
   $iid = $_POST['iid'];
} else {
   include_once('functions/error_functions.php');
   trigger_error('No user selected!',E_USER_ERROR);
}

$user_manager = $environment->getUserManager();
$user = $user_manager->getItem($iid);

// Check access rights
if ($user->getItemID() != $current_user->getItemID() and !$current_user->isModerator()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $error_string = $translator->getMessage('LOGIN_NOT_ALLOWED').'<br />';
   $errorbox->setText($error_string);
   $page->add($errorbox);
   $command = 'error';
}

// cancel button
if ( isOption($command,$translator->getMessage('ADMIN_CANCEL_BUTTON')) or isOption($command,$translator->getMessage('COMMON_BACK_BUTTON'))) {
   $params = array();
   $params['iid'] = $iid;
   redirect($environment->getCurrentContextID(), $current_module, 'detail', $params);

} elseif (!isOption($command,'error')) {

   $context_item = $environment->getCurrentContextItem();
   /* setup the form */

   // Construct the form
   $form = $class_factory->getClass(ACCOUNT_PASSWORD_ADMIN_FORM,array('environment' => $environment));

      /* we are not called as a result of a form post, so just display the form */
      if ( empty($command) and !empty($_GET['iid']) ) {
         $form->setItem($user);
         $form->prepareForm();
         $form->loadValues();
      }

      /* we called ourself as result of a form post */
      elseif ( isOption($command,$translator->getMessage('PASSWORD_CHANGE_BUTTON_LONG')) ) {
         $error_string = '';
         $form->setFormPost($_POST);
         $form->prepareForm();
         $form->loadValues();
         if ( $form->check() ) {
            // change password
            if (empty($error_string)) {
               $auth_manager = $authentication->getAuthManager($user->getAuthSource());
               $auth_manager->changePassword($_POST['user_id'],$_POST['password']);
               // set new expire date
               $portal_manager = $environment->getPortalManager();
               $portal_item = $portal_manager->getItem($user->getContextID());
               $user->setPasswordExpireDate($portal_item->getPasswordExpiration());

               $user->save();
               unset($portal_manager);
               $error_number = $auth_manager->getErrorNumber();
               if (empty($error_number)) {
                  $params = array();
                  $params['iid'] = $iid;

                  include_once('classes/cs_mail_obj.php');
                  $mail_obj = new cs_mail_obj();
                  $mail_obj->setMailFormHeadLine($translator->getMessage('USER_PASSWORD_CHANGE_HEADLINE'));

                  $mail_subject  = $translator->getMessage('MAIL_SUBJECT_USER_PASSWORD_CHANGE',$context_item->getTitle());
                  $mail_body  = $translator->getEmailMessage('MAIL_BODY_HELLO',$user->getFullname());
                  $mail_body .= LF.LF;
                  $mail_body .= $translator->getEmailMessage('MAIL_BODY_USER_PASSWORD_CHANGE',$user->getUserID(),$context_item->getTitle(),$_POST['password']);
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
                  $params = array();
                  $params['iid'] = $iid;
                  $mail_obj->setBackLink($environment->getCurrentContextID(),
                                         'account',
                                         'detail',
                                         $params);
                  unset($params);
                  $mail_obj->toSession();
                  redirect($environment->getCurrentContextID(),'mail','process','');
               } else {
                  $error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
               }
            }
         }
      }

   $class_params = array();
   $class_params['environment'] = $environment;
   $class_params['with_modifying_actions'] = true;
   $form_view = $class_factory->getClass(FORM_VIEW,$class_params);
   unset($class_params);
   $form_view->setAction(curl($environment->getCurrentContextID(),$current_module,'password',''));
   $form_view->setForm($form);
   if ($environment->inServer() or $environment->inPortal() ) {
      $page->addForm($form_view);
   } else {
      $page->add($form_view);
   }
}
?>