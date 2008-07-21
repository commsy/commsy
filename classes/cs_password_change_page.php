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
class cs_password_change_page extends cs_left_page {

   function cs_password_change_page ($environment) {
      $this->cs_left_page($environment);
   }

   function execute () {
      $success = false;

      include_once('classes/cs_password_change_form.php');
      $form = new cs_password_change_form($this->_environment);
      // Load form data from postvars
      if ( !empty($this->_post_vars) ) {
         $form->setFormPost($this->_post_vars);
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
           and isOption($this->_command, $this->_translator->getMessage('PASSWORD_CHANGE_BUTTON'))
         ) {
         $correct = $form->check();
         if ( $correct ) {
            $current_user = $this->_environment->getCurrentUserItem();
            $authentication = $this->_environment->getAuthenticationObject();
            if ( !$current_user->isRoot() ) {
               $session_item = $this->_environment->getSessionItem();
               $auth_manager = $authentication->getAuthManager($current_user->getAuthSource());
            } else {
               $server_item = $this->_environment->getServerItem();
               $auth_manager = $authentication->getAuthManagerByAuthSourceItem($server_item->getDefaultAuthSourceItem());
            }
            $auth_manager->changePassword($current_user->getUserID(),$this->_post_vars['password']);
            $error_number = $auth_manager->getErrorNumber();
            if (empty($error_number)) {
               $success = true;
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