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

$this->includeClass(RUBRIC_FORM);
include_once('functions/text_functions.php');

/** class for commsy form: get an account step 1
 * this class implements an interface for the creation of a form in the commsy style: get an account step 1
 */
class cs_password_change_form extends cs_rubric_form {

   var $_auth_source = null;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      $this->_user = $this->_environment->getCurrentUserItem();
      $portal_item = $this->_environment->getCurrentPortalItem();
      if ( !isset($portal_item) ) {
         $portal_item = $this->_environment->getServerItem();
      }
      $this->_auth_source = $portal_item->getAuthSource($this->_user->getAuthSource());
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',$this->_translator->getMessage('USER_PASSWORD_CHANGE_HEADLINE'));
      if ( isset($this->_auth_source)
           and $this->_auth_source->allowChangePassword()
         ) {
         $session_item = $this->_environment->getSessionItem();
         if ( !$session_item->issetValue('password_forget_ip') ) {
            $this->_form->addPassword('password_old','',$this->_translator->getMessage('USER_PASSWORD_OLD'),'','','21',true);
         }
         $this->_form->addPassword('password','',$this->_translator->getMessage('USER_PASSWORD_NEW'),'','','21',true);
         $this->_form->addPassword('password2','',$this->_translator->getMessage('USER_PASSWORD_NEW2'),'','','21',true);
         if ( !$session_item->issetValue('password_forget_ip') ) {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('PASSWORD_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,5.3,6.2);
         } else {
            $this->_form->addButtonBar('option',$this->_translator->getMessage('PASSWORD_CHANGE_BUTTON'),'','','','','',false,5.3,6.2);
         }
      } else {
         //we mustn't edit pw
         if ( isset($this->_auth_source) ) {
            if ( $this->_auth_source->isCommSyDefault() ) {
               $this->_form->addText('info','',$this->_translator->getMessage('AUTH_NOT_AVAILABLE2'),'');
            } else {
               $this->_form->addText('info','',$this->_translator->getMessage('USER_AUTH_SOURCE_ERROR_NOT_AVAILABLE',$this->_auth_source->getTitle()),'');
            }
         }
         $this->_form->addButtonBar('option','',$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,'',14);
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( !empty($this->_form_post['password_old']) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $authentication = $this->_environment->getAuthenticationObject();
         if ( !$authentication->isAccountGranted($current_user->getUserID(),$this->_form_post['password_old'],$current_user->getAuthSource()) ) {
            $this->_error_array[] = $this->_translator->getMessage('USER_OLD_PASSWORD_ERROR');
            $this->_form->setFailure('password_old');
         }
      }
      if ($this->_form_post['password'] != $this->_form_post['password2']) {
         $this->_error_array[] = $this->_translator->getMessage('USER_PASSWORD_ERROR');
         $this->_form->setFailure('password');
         $this->_form->setFailure('password2');
      }
   }
}
?>