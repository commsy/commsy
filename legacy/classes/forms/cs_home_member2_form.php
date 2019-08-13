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
class cs_home_member2_form extends cs_rubric_form {

   private $_get_vars = array();
   private $_auth_source = '';
   private $_user_id = '';

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   function setFormGet ( $value ) {
      $this->_get_vars = $value;
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      if ( isset($this->_get_vars['auth_source']) and !empty($this->_get_vars['auth_source']) ) {
         $this->_auth_source = $this->_get_vars['auth_source'];
      }
      if ( isset($this->_get_vars['user_id']) and !empty($this->_get_vars['user_id']) ) {
         $this->_user_id = urldecode($this->_get_vars['user_id']);
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',$this->_translator->getMessage('ACCOUNT_FORM_TITLE2'));
      $this->_form->addText('desc',$this->_translator->getMessage('ACCOUNT_FORM_TITLE2_DESC'),'');
      $this->_form->addHidden('user_id',$this->_user_id);
      $this->_form->addHidden('auth_source',$this->_auth_source);
      $this->_form->addTextField('firstname','',$this->_translator->getMessage('USER_FIRSTNAME'),'','',21,true,'','','','left','',13);
      $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','',21,true,'','','','left','',13);
      $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','',21,true,'','','','left','',13);
      $this->_form->addTextField('email_confirmation','',$this->_translator->getMessage('USER_EMAIL_CONFIRMATION'),'','',21,true,'','','','left','',13);
      $this->_form->addHidden('language','');
      
      // Datenschutz
      if ($this->_environment->getCurrentContextItem()->withAGB()) {
      	$link = ahref_curl($this->_environment->getCurrentContextID(), 'agb', 'index', '', $this->_translator->getMessage('CONFIGURATION_AGB_FORM_HEADLINE'),'','_new');
      	$this->_form->addCheckbox('terms_of_use', '1', false, '', $this->_translator->getMessage('CONFIGURATION_AGB_ACCEPT').$link);
      }

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('ACCOUNT_GET_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,6.5,6.5);
   }


   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
      } else {
         $this->_values['language'] = 'browser';
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
   	
   	if ($this->_environment->getCurrentContextItem()->withAGB()) {
   		if (!isset($this->_form_post['terms_of_use'])){
   			$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AGB_ACCEPT_ERROR');
   			$this->_form->setFailure('terms_of_use','');
   		}
   	}
      // check email adresses for equality
      if ($this->_form_post['email'] != $this->_form_post['email_confirmation']) {
         $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_ERROR');
         $this->_form->setFailure('email','');
         $this->_form->setFailure('email_confirmation','');
      } else {
         //check emails for validity
         if (isEmailValid($this->_form_post['email']) == false) {
            $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_VALID_ERROR');
            $this->_form->setFailure('email','');
            $this->_form->setFailure('email_confirmation','');
         }
      }
   }
}
?>