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

include_once('classes/cs_rubric_form.php');
include_once('functions/text_functions.php');

/** class for commsy form: edit an account: set password
 * this class implements an interface for the creation of a form in the commsy style: edit an account: set password
 */
class cs_account_password_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_account_password_form($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data (text and options) for the form
    */
   function _initForm () {
      // if an item is given - first call of the form
      if (!empty($this->_item)) {
         $this->_headline = getMessage('USER_PASSWORD_CHANGE_HEADLINE');
      }

      // if form posts are given - second call of the form
      else {
         $this->_headline = getMessage('USER_PASSWORD_CHANGE_HEADLINE');
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    *
    * @author CommSy Development Group
    */
   function _createForm () {

      // headline and hidden fields
      $this->setHeadline($this->_headline);
      $this->_form->addHidden('iid','');
      $this->_form->addHidden('fullname','');
      $this->_form->addHidden('user_id','');

      // content form fields
      $this->_form->addText('fullname_text',getMessage('USER_NAME'),'');
      $this->_form->addText('user_id_text',getMessage('AUTH_ACCOUNT'),'');
      $this->_form->addPassword('password','',getMessage('USER_PASSWORD'),getMessage('USER_PASSWORD_DESC'),'','',true);
      $this->_form->addPassword('password2','',getMessage('USER_PASSWORD2'),getMessage('USER_PASSWORD2_DESC'),'','',true);

      // buttons
      $this->_form->addButtonBar('option',getMessage('PASSWORD_CHANGE_BUTTON_LONG'),getMessage('ADMIN_CANCEL_BUTTON'));
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    *
    * @author CommSy Development Group
    */
   function _prepareValues () {
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
         $this->_values['fullname_text'] = $this->_values['fullname'];
         $this->_values['user_id_text'] = $this->_values['user_id'];
      } elseif (!empty($this->_item)) {
         $this->_values['iid'] = $this->_item->getItemID();
         $this->_values['fullname'] = $this->_item->getFullname();
         $this->_values['user_id'] = $this->_item->getUserID();
         $this->_values['fullname_text'] = $this->_item->getFullname();
         $this->_values['user_id_text'] = $this->_item->getUserID();
      } else {
         include_once('functions/error_functions.php');trigger_error('lost values',E_USER_WARNING);
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ($this->_form_post['password'] != $this->_form_post['password2']) {
         $this->_error_array[] = getMessage('USER_PASSWORD_ERROR');
         $this->_form->setFailure('password');
         $this->_form->setFailure('password2');
      }
   }
}
?>