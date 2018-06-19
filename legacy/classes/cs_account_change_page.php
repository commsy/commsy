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
class cs_account_change_page extends cs_left_page {

   function __construct($environment) {
      cs_left_page::__construct($environment);
   }

   function execute () {

      $success = false;

      $form = $this->_class_factory->getClass(ACCOUNT_CHANGE_FORM,array('environment' => $this->_environment));
      // Load form data from postvars
      if ( !empty($this->_post_vars) and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_CHANGE_BUTTON')) ) {
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
           and isOption($this->_command, $this->_translator->getMessage('ACCOUNT_CHANGE_BUTTON'))
         ) {
         $correct = $form->check();
         if ( $correct ) {
            $authentication = $this->_environment->getAuthenticationObject();
            if ( !$this->_environment->inPortal() ) {
               $portal_user = $this->_environment->getPortalUserItem();
            } else {
               $portal_user = $this->_environment->getCurrentUserItem();
            }

            $success_1 = false;
            $success_2 = false;
            $success_3 = false;
            if ( !empty($this->_post_vars['user_id'])
                 and $this->_post_vars['user_id'] != $portal_user->getUserID()) {
               if ($authentication->changeUserID($this->_post_vars['user_id'],$portal_user)) {
                  $session = $this->_environment->getSessionItem();
                  $session_id_old = $session->getSessionID();
                  $session_manager = $this->_environment->getSessionManager();
                  $session_manager->delete($session_id_old,true);
                  unset($session_manager);
                  $session->createSessionID($this->_post_vars['user_id']);
                  $cookie = $session->getValue('cookie');
                  if ( $cookie == 1 ) {
                     $session->setValue('cookie',2);
                  }

                  // save portal id in session to be sure, that user didn't
                  // switch between portals
                  $success_1 = true;
                  $portal_user->setUserID($this->_post_vars['user_id']);
               }
            } else {
               $success_1 = true;
            }
            $save = false;
            if (!empty($this->_post_vars['language']) and $this->_post_vars['language'] != $portal_user->getLanguage()) {
               $portal_user->setLanguage($this->_post_vars['language']);
               $save = true;
            }
            if (!empty($this->_post_vars['email_account_want'])) {
               if ($portal_user->getAccountWantMail() == 'no') {
                  $portal_user->setAccountWantMail('yes');
                  $save = true;
               }
            } else {
               if ($portal_user->getAccountWantMail() == 'yes') {
                  $portal_user->setAccountWantMail('no');
                  $save = true;
               }
            }
            if (!empty($this->_post_vars['email_room_want'])) {
               if ($portal_user->getOpenRoomWantMail() == 'no') {
                  $portal_user->setOpenRoomWantMail('yes');
                  $save = true;
               }
            } else {
               if ($portal_user->getOpenRoomWantMail() == 'yes') {
                  $portal_user->setOpenRoomWantMail('no');
                  $save = true;
               }
            }

            if ($save) {
               $portal_user->save();
            } else {
               $success_2 = true;
            }
            $success = $success_1 and $success_2;
         }
      }

      if (!$success) {
         return $this->_show_form($form,'f2');
      } else {
         $this->_redirect_back();
      }
   }
}
?>