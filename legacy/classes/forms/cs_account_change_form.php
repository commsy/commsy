<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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
class cs_account_change_form extends cs_rubric_form {
  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * string - containing the subtitle of the form
   */
   var $_subtitle = NULL;

   var $_back_button = false;

  /**
   * array - containing the options for a choice of languages
   */
   var $_language_options = array();

   var $_description_array = array();

   var $_email_account = false;

   var $_email_room = false;
   var $_account_change = true;
   var $_account_data_change = true;

   var $_auth_source = null;
   var $_user = null;

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
      // language options
      if ( $this->_environment->inCommunityRoom()
           or $this->_environment->inProjectRoom()
           or $this->_environment->inPrivateRoom()
         ) {
         $this->_user = $this->_environment->getPortalUserItem();
      } else {
         $this->_user = $this->_environment->getCurrentUserItem();
      }
      if ( !isset($this->_user) ) {
         include_once('functions/error_functions.php');
         trigger_error('lost current user item. - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
      } elseif ( $this->_user->isRoot()) {
         $this->_email_room = false;
         $this->_email_account = false;
         $this->_account_change = false;
      } elseif ($this->_user->isModerator()) {
         $i=0;
         $options = array();
         $options[$i]['value'] = 'browser';
         $options[$i]['text'] = $this->_translator->getMessage('USER_BROWSER_LANGUAGE');
         $i++;
         $options[$i]['value'] = 'disabled';
         $options[$i]['text'] = '------------------';
         $i++;
         $languages = $this->_environment->getAvailableLanguageArray();
         foreach ($languages as $language) {
            $options[$i]['value'] = $language;
            $options[$i]['text'] = $this->_translator->getLanguageLabelOriginally($language);
            $i++;
         }
         $this->_language_options = $options;

         $this->_email_room = true;
         $this->_email_account = true;
      }
      $portal_item = $this->_environment->getCurrentPortalItem();
      $this->_auth_source = $portal_item->getAuthSource($this->_user->getAuthSource());
      if (!$this->_auth_source->allowChangeUserID()) {
         $this->_account_change = false;
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      // text and options
      $this->_form->addHeadline('merge_account',$this->_translator->getMessage('ACCOUNT_CHANGE'));
      if ($this->_account_change) {
         $this->_form->addTextField('user_id','',$this->_translator->getMessage('USER_USER_ID'),'',100,21,true);
      } else {
         $this->_form->addText('user_id_text',$this->_translator->getMessage('USER_USER_ID'),'&nbsp;'.$this->_user->getUserID(),'');
      }

      if (!empty($this->_language_options)) {
         $this->_form->addSelect('language',$this->_language_options,'',$this->_translator->getMessage('USER_LANGUAGE'),'','','',true,'','','','','','12.6');
      }
      if ($this->_email_account) {
         $this->_form->addCheckbox('email_account_want','1',false,$this->_translator->getMessage('USER_EMAIL'),$this->_translator->getMessage('USER_MAIL_GET_ACCOUNT'),'','','','','');
      }
      if ($this->_email_room) {
         $this->_form->addCheckbox('email_room_want','1',false,$this->_translator->getMessage('USER_EMAIL'),$this->_translator->getMessage('USER_MAIL_OPEN_ROOM_PO'),'','','','','');
      }

      // buttons
      $this->_form->addButtonBar('option',$this->_translator->getMessage('ACCOUNT_CHANGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,5.3,6.2);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      if (!empty($this->_form_post)) {
         $this->_values = $this->_form_post;
      } else {
         $this->_user = $this->_environment->getPortalUserItem();
         $this->_values['user_id'] = $this->_user->getUserID();
         if ( $this->_user->isRoot() ) {
            $this->_values['user_id_text'] = $this->_user->getUserID();
         }
         $this->_values['firstname'] = $this->_user->getFirstname();
         $this->_values['lastname'] = $this->_user->getLastname();
         $this->_values['email'] = $this->_user->getEMail();
         $this->_values['language'] = $this->_user->getLanguage();
         if ($this->_user->getAccountWantMail() == 'yes') {
            $this->_values['email_account_want'] = 1;
         }
         if ($this->_user->getOpenRoomWantMail() == 'yes') {
            $this->_values['email_room_want'] = 1;
         }
         $this->_values['auth_source'] = $this->_user->getAuthSource();
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      if ( !empty($this->_form_post['email'])
           and !isEmailValid($this->_form_post['email'])
         ) {
         $this->_error_array[] = $this->_translator->getMessage('USER_EMAIL_VALID_ERROR');
         $this->_form->setFailure('email','');
      }

      // exists user id?
      if ( !empty($this->_form_post['user_id']) ) {
         $current_user = $this->_environment->getCurrentUserItem();
         $auth_source = $current_user->getAuthSource();
         if ( !empty($auth_source) ) {
            $authentication = $this->_environment->getAuthenticationObject();
            $this->_user = $this->_environment->getPortalUserItem();
            if ($this->_user->getUserID() != $this->_form_post['user_id'] and !$authentication->is_free($this->_form_post['user_id'],$auth_source)) {
               $this->_error_array[] = $this->_translator->getMessage('USER_USER_ID_ERROR',$this->_form_post['user_id']);
               $this->_form->setFailure('user_id','');
            } elseif ( withUmlaut($this->_form_post['user_id']) ) {
               $this->_error_array[] = $this->_translator->getMessage('USER_USER_ID_ERROR_UMLAUT',$this->_form_post['user_id']);
               $this->_form->setFailure('user_id','');
            }
         } else {
            $this->_error_array[] = $this->_translator->getMessage('USER_AUTH_SOURCE_ERROR');
         }
      }
   }
}
?>