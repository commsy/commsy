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

$done = false;
$authentication = $environment->getAuthenticationObject();
$current_module = $environment->getCurrentModule();

// option contains the name of the submit button, if this
// script is called as result of a form post
if (!empty($_POST['option'])) {
   $command = $_POST['option'];
} else {
   $command = '';
}

if (!isOption($command,'error')) {

   $context_item = $environment->getCurrentContextItem();
   /* setup the form */

   // Construct the form
   $form = $class_factory->getClass(ACCOUNT_PASSWORD_FORM,array('environment' => $environment));

   /* we are not called as a result of a form post, so just display the form */
   if ( empty($command) ) {
      $form->prepareForm();
      $form->loadValues();
   }
   
   /* if we hit the abort button in the form */
   elseif ( isOption($command,$translator->getMessage('ADMIN_CANCEL_BUTTON')) ) {
      $params = $environment->getCurrentParameterArray();
      if(	!empty($params['cs_modus']) and
      	 	$params['cs_modus'] == 'password_change') {
      	 unset($params['cs_modus']);
      }
   	  redirect($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params);
   }

   /* we called ourself as result of a form post */
   elseif ( isOption($command,$translator->getMessage('COMMON_CHANGE_BUTTON')) ) {
      $error_string = '';
      $form->setFormPost($_POST);
      $form->prepareForm();
      $form->loadValues();
      
      if ( $form->check() ) {
         // change password and email
         if (empty($error_string)) {
            $auth_manager = $authentication->getAuthManager($_POST['auth_source_id']);
            $auth_manager->changePassword($_POST['user_id'],$_POST['password']);
            
            //set new expire date
            $user_manager = $environment->getUserManager();
            $user = $user_manager->getItem($_POST['iid']);
            $portal_item = $environment->getCurrentPortalItem();
            $user->setPasswordExpireDate($portal_item->getPasswordExpiration());
            $user->save();
            
            unset($user_manager);
            unset($portal_manager);
            
            $error_number = $auth_manager->getErrorNumber();
            if($environment->getCurrentUserItem()->isRoot()){
	            $user_item = $environment->getCurrentUserItem();
	            $user_item->setEmail($_POST['email']);
	            $user_item->save();
            }
            if (empty($error_number)) {
               $session_item = $environment->getSessionItem();
               if ($session->issetValue('password_forget_time')) {
                  $session->unsetValue('password_forget_time');
               }
               if ($session->issetValue('password_forget_ip')) {
                  $session->unsetValue('password_forget_ip');
               }
               if ( !empty($_GET['cs_modus']) ) {
                  unset($_GET['cs_modus']);
               }
               $environment->setCurrentParameter('cs_modus','');
               $done = true;
            } else {
               $error_string .= $translator->getMessage('COMMON_ERROR_DATABASE').$error_number.'<br />';
            }
         }
      }
   }

   if ( !$done) {
      $class_params = array();
      $class_params['environment'] = $environment;
      $class_params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(FORM_OVERLAY_VIEW,$class_params);
      unset($class_params);
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$environment->getCurrentParameterArray()));
      $params = $environment->getCurrentParameterArray();
      if ( !empty($params['cs_modus'])
           and $params['cs_modus'] == 'password_change'
         ) {
         unset($params['cs_modus']);
      }
      $form_view->setBackLink(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$params));
      $form_view->setForm($form);
      $page->addOverlay($form_view);
   }
}
?>