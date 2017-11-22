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

/** class for commsy form: edit an account: set password
 * this class implements an interface for the creation of a form in the commsy style: edit an account: set password
 */
class cs_account_password_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

   private $_modus = 'normal';

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data (text and options) for the form
    */
   function _initForm () {
      //$this->_headline = $this->_translator->getMessage('USER_PASSWORD_CHANGE_HEADLINE');
      $this->_headline = $this->_translator->getMessage('COMMON_PROFILE_EDIT');

      $session = $this->_environment->getSessionItem();
      if ( isset($session)
           and $session->issetValue('password_forget_ip')
         ) {
         $this->_modus = 'forget';
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {

      // headline and hidden fields
      $this->setHeadline($this->_headline);
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('fullname','');
      $this->_form->addHidden('user_id','');
      $this->_form->addHidden('auth_source_id','');

      // content form fields
      $this->_form->addText('fullname_text',$this->_translator->getMessage('USER_NAME'),'');
      $this->_form->addText('user_id_text',$this->_translator->getMessage('AUTH_ACCOUNT'),'');
      $this->_form->addPassword('password','',$this->_translator->getMessage('USER_PASSWORD'),$this->_translator->getMessage('USER_PASSWORD_DESC'),'','',true);
      $this->_form->addPassword('password2','',$this->_translator->getMessage('USER_PASSWORD2'),$this->_translator->getMessage('USER_PASSWORD2_DESC'),'','',true);

      if($this->_environment->getCurrentUserItem()->isRoot()){
         $this->_form->addTextfield('email','',$this->_translator->getMessage('USER_EMAIL'), '');
         $this->_form->addTextfield('email2','',$this->_translator->getMessage('USER_EMAIL_CONFIRMATION'),'');
      }

      // buttons
      if ( !empty($this->_modus)
           and $this->_modus == 'forget'
         ) {
         //$this->_form->addButton('option',$this->_translator->getMessage('PASSWORD_CHANGE_BUTTON_LONG'));
         $this->_form->addButton('option',$this->_translator->getMessage('COMMON_CHANGE_BUTTON'));
      } else {
         //$this->_form->addButtonBar('option',$this->_translator->getMessage('PASSWORD_CHANGE_BUTTON_LONG'),$this->_translator->getMessage('ADMIN_CANCEL_BUTTON'));
         $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_CHANGE_BUTTON'),$this->_translator->getMessage('ADMIN_CANCEL_BUTTON'));
      }
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if ( empty($this->_form_post)
           and empty($this->_item)
           and !empty($this->_modus)
           and $this->_modus = 'forget'
         ) {
         $session_item = $this->_environment->getSessionItem();
         if ( $session_item->issetValue('user_id')
              and $session_item->issetValue('auth_source')
            ) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
            $user_manager->setUserIDLimit($session_item->getValue('user_id'));
            $user_manager->setAuthSourceLimit($session_item->getValue('auth_source'));
            $user_manager->select();
            $user_list = $user_manager->get();
            unset($user_manager);

            /*
             * Fix: if user is root, user_manager result is empty, because
             * commsy_id(server id) does not fit context_id(portal_id)
             */
            if(   isset($user_list)
                  and $user_list->isEmpty()
                  and $this->_environment->getCurrentUserItem()->isRoot()
               ) {
                  $user_manager = $this->_environment->getUserManager();
                  $this->_item = $user_manager->getRootUser();
                  unset($user_manager);
            }
            // ~Fix

            if ( !empty($user_list)
                 and $user_list->isNotEmpty()
                 and $user_list->getCount() == 1
               ) {
               $this->_item = $user_list->getFirst();
            }
            unset($user_list);
         }
      }

      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
         $this->_values['fullname_text'] = $this->_values['fullname'];
         $this->_values['user_id_text'] = $this->_values['user_id'];
      } elseif (!empty($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['fullname'] = $this->_item->getFullname();
         $this->_values['user_id'] = $this->_item->getUserID();
         $this->_values['auth_source_id'] = $this->_item->getAuthSource();
         $this->_values['fullname_text'] = $this->_item->getFullname();
         $this->_values['user_id_text'] = $this->_item->getUserID();
         if($this->_environment->getCurrentUserItem()->isRoot()){
            $this->_values['email'] = $this->_item->getEmail();
         }
      } else {
         // if $this->_form_post is empty and $this->_item is empty
         include_once('functions/error_functions.php');trigger_error('lost values',E_USER_WARNING);
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ($this->_form_post['password'] != $this->_form_post['password2']) {
         $this->_error_array[] = $this->_translator->getMessage('USER_PASSWORD_ERROR');
         $this->_form->setFailure('password');
         $this->_form->setFailure('password2');
      }
      if(isset($this->_form_post['auth_source_id'])) {
      	$auth_source_manager = $this->_environment->getAuthSourceManager();
      	$auth_source_item = $auth_source_manager->getItem($this->_form_post['auth_source_id']);
      	if($auth_source_item->getPasswordLength() > 0){
      		if(strlen($this->_form_post['password']) < $auth_source_item->getPasswordLength()) {
      			$this->_error_array[] = $this->_translator->getMessage('USER_NEW_PASSWORD_LENGTH_ERROR', $auth_source_item->getPasswordLength());
      		}
      	}
      	if($auth_source_item->getPasswordSecureBigchar() == 1){
      		if(!preg_match('~[A-Z]+~u', $this->_form_post['password'])) {
      			$this->_error_array[] = $this->_translator->getMessage('USER_NEW_PASSWORD_BIGCHAR_ERROR');
      		}
      	}
      	if($auth_source_item->getPasswordSecureSpecialchar() == 1){
      		if(!preg_match('~[^a-zA-Z0-9]+~u',$this->_form_post['password'])){
      			$this->_error_array[] = $this->_translator->getMessage('USER_NEW_PASSWORD_SPECIALCHAR_ERROR');
      		}
      	}
      	if($auth_source_item->getPasswordSecureNumber() == 1){
      		if(!preg_match('~[0-9]+~u',$this->_form_post['password'])){
      			$this->_error_array[] = $this->_translator->getMessage('USER_NEW_PASSWORD_NUMBER_ERROR');
      		}
      	}
      	if($auth_source_item->getPasswordSecureSmallchar() == 1){
      		if(!preg_match('~[a-z]+~u',$this->_form_post['password'])){
      			$this->_error_array[] = $this->_translator->getMessage('USER_NEW_PASSWORD_SMALLCHAR_ERROR');
      		}
      	}
      	unset($auth_source_manager);
      }
      	
      if($this->_environment->getCurrentUserItem()->isRoot()){
	      if(!isEmailValid($this->_form_post['email'])) {
	         $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_VALID_ERROR');
	         $this->_form->setFailure('email');
	      }
	      if($this->_form_post['email'] != $this->_form_post['email2']) {
	          $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_ERROR');
	          $this->_form->setFailure('email');
	          $this->_form->setFailure('email2');
	      }
      }
   }
}
?>