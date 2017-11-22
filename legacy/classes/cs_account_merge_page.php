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
class cs_account_merge_page extends cs_left_page {

   function __construct($environment) {
      cs_left_page::__construct($environment);
   }

   function execute () {
      $form = $this->_class_factory->getClass(ACCOUNT_MERGE_FORM,array('environment' => $this->_environment));
      // Load form data from postvars
      if ( !empty($this->_post_vars) and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_MERGE_BUTTON')) ) {
         $form->setFormPost($this->_post_vars);
      }
      $form->prepareForm();
      $form->loadValues();

      // cancel
      if ( !empty($this->_command) and
           isOption($this->_command, $this->_translator->getMessage('COMMON_CANCEL_BUTTON'))
         ) {
         $this->_redirect_back();
      }

      // Save item
      if ( !empty($this->_command)
           and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_MERGE_BUTTON'))
         ) {
         $correct = $form->check();
         if ( $correct ) {
            $authentication = $this->_environment->getAuthenticationObject();
            $current_user = $this->_environment->getCurrentUserItem();
            if ( isset($this->_post_vars['auth_source']) and !empty($this->_post_vars['auth_source']) ) {
               $auth_source_old = $this->_post_vars['auth_source'];
            } else {
               $current_context = $this->_environment->getCurrentContextItem();
               $auth_source_old = $current_context->getAuthDefault();
            }
            $authentication->mergeAccount($current_user->getUserID(),$current_user->getAuthSource(),$this->_post_vars['user_id'],$auth_source_old);
            $this->_redirect_back();
         }
      }
      return $this->_show_form($form);
   }
}
?>