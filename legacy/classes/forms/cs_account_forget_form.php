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
class cs_account_forget_form extends cs_rubric_form {

   var $_auth_source_array = array();

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // auth source
      $current_portal = $this->_environment->getCurrentPortalItem();
      $auth_source_list = $current_portal->getAuthSourceListEnabled();
      if ( isset($auth_source_list) and !$auth_source_list->isEmpty() ) {
         $auth_source_item = $auth_source_list->getFirst();
         while ($auth_source_item) {
            $temp_array = array();
            $temp_array['value'] = $auth_source_item->getItemID();
            $temp_array['text'] = $auth_source_item->getTitle();
            $this->_auth_source_array[] = $temp_array;
            unset($temp_array);
            $auth_source_item = $auth_source_list->getNext();
         }
      }
      $this->_default_auth_source_entry = $current_portal->getAuthDefault();
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',$this->_translator->getMessage('USER_ACCOUNT_FORGET_HEADLINE'));
      $this->_form->addText('text','',$this->_translator->getMessage('USER_ACCOUNT_FORGET_TEXT'));
      // auth source
      if ( count($this->_auth_source_array) == 1 ) {
         $this->_form->addHidden('auth_source',$this->_auth_source_array[0]['value']);
      }
      $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','',21,true);
      $this->_form->addButtonBar('option',$this->_translator->getMessage('ACCOUNT_SEND_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,5.5,6);
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
      if (!empty($this->_form_post['email'])) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($this->_environment->getCurrentPortalID());
         $user_manager->setUserLimit();
         $user_manager->setSearchLimit($this->_form_post['email']);
         $user_manager->select();
         $user_list = $user_manager->get();

         // check email adresses for equality
         if ($user_list->isEmpty() or $user_list->getCount() < 1) {
            $this->_error_array[] = $this->_translator->getMessage('ERROR_EMAIL_DOES_NOT_EXIST');
            $this->_form->setFailure('email','');
         }

         if (isEmailValid($this->_form_post['email']) == false) {
            $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_VALID_ERROR');
            $this->_form->setFailure('email','');
         }
      }
   }

   /** In case a lost account was send successfully this page gets displayed.
    */
   function showMailSent($emailAddress) {
      $this->_form = new cs_form();
      $context = $this->_environment->getCurrentContextItem();
      $this->_form->addText('text',$this->_translator->getMessage('COMMON_HINTS'),$this->_translator->getMessage('USER_ACCOUNT_FORGET_SUCCESS_TEXT',$emailAddress));
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_FORWARD_BUTTON'));
   }

   /** In case of mail server error the following page gets displayed.
     */
   function showMailFailure() {
      $this->_form = new cs_form();
      $moderation_link = ahref_curl($this->_environment->getCurrentPortalID(),'mail','to_moderator','',$this->_translator->getMessage('CONTEXT_MODERATOR'));
      $this->_form->addText('text',$this->_translator->getMessage('COMMON_HINTS'),$this->_translator->getMessage('ERROR_MAIL_SERVER',$moderation_link));
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_FORWARD_BUTTON'));
   }
}
?>