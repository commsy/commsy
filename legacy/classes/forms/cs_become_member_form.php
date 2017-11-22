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
class cs_become_member_form extends cs_rubric_form {

   private $_with_code = false;

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
      $current_context_item = $this->_environment->getCurrentContextItem();
      if ( $current_context_item->checkNewMembersWithCode() ) {
         $this->_with_code = true;
      }
      unset($current_context_item);
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',$this->_translator->getMessage('CONTEXT_JOIN'));
      if ($this->_with_code) {
         $this->_form->addTextfield('code','',$this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE'),'','',24,true);
      } else {
         $this->_form->addTextArea('description','',$this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON'),'',18,8);
      }
      $context = $this->_environment->getCurrentContextItem();
      $this->_form->addButtonBar('option',$this->_translator->getMessage('USER_BECOME_MEMBER_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,7,6);
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
      if ( !empty($this->_form_post['code']) ) {
         $current_context_item = $this->_environment->getCurrentContextItem();
         if ( $current_context_item->checkNewMembersWithCode()
              and $current_context_item->getCheckNewMemberCode() != $this->_form_post['code']
            ) {
            $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
            $this->_form->setFailure('code','');
         }
      }
   }

   /** In case a lost account was send successfully this page gets displayed.
    */
   function showAccountNotOpen ($user) {
      $this->_form = new cs_form();
      $this->_form->addHeadline('title',$this->_translator->getMessage('CONTEXT_JOIN'));
      if ($user->isRequested()) {
         $this->_form->addText('text','',$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET'));
      } elseif ($user->isRejected()) {
         $this->_form->addText('text','',$this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED'));
      }
      $this->_form->addButtonBar('option',$this->_translator->getMessage('COMMON_FORWARD_BUTTON'));
   }
}
?>