<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

/** class for commsy form: get an account step 1
 * this class implements an interface for the creation of a form in the commsy style: get an account step 1
 */
class cs_password_forget_form extends cs_rubric_form {

   var $_auth_source_array = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_password_forget_form ($environment) {
      $this->cs_rubric_form($environment);
   }

   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {
      // auth source
      $current_portal = $this->_environment->getCurrentPortalItem();
      $auth_source_list = $current_portal->getAuthSourceListEnabled();
      $this->_count_auth_source_list_add_account = 0;
      if ( isset($auth_source_list) and !$auth_source_list->isEmpty() ) {
         $this->_count_auth_source_list_enabled = $auth_source_list->getCount();
         $auth_source_item = $auth_source_list->getFirst();
         while ($auth_source_item) {
            $temp_array = array();
            if ( $auth_source_item->allowAddAccount() ) {
               $temp_array['value'] = $auth_source_item->getItemID();
               $this->_count_auth_source_list_add_account++;
            } else {
               $temp_array['value'] = 'disabled';
            }
            $temp_array['text'] = $auth_source_item->getTitle();
            $this->_auth_source_array[] = $temp_array;
            unset($temp_array);
            $auth_source_item = $auth_source_list->getNext();
         }
         $this->_count_auth_source_list_add_account;
      } else {
         $this->_count_auth_source_list_enabled = 0;
      }
      if ($this->_count_auth_source_list_add_account == 1) {
         $this->_default_auth_source_entry = $this->_auth_source_array[0]['value'];
      } else {
         $this->_default_auth_source_entry = $current_portal->getAuthDefault();
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',getMessage('USER_PASSWORD_FORGET_HEADLINE'));
      $this->_form->addText('text','',getMessage('USER_PASSWORD_FORGET_TEXT'));

      // auth source
      if ( $this->_count_auth_source_list_enabled == 1
           and $this->_count_auth_source_list_add_account == 1 ) {
         $this->_form->addHidden('auth_source',$this->_auth_source_array[0]['value']);
      } elseif ( $this->_count_auth_source_list_enabled > 1 ) {
         $this->_form->addSelect('auth_source', $this->_auth_source_array, $this->_default_auth_source_entry, $this->_translator->getMessage('USER_AUTH_SOURCE'), '', 1 , false, false, false, '', '', '', '', 13.4);
      }
      if ( $this->_count_auth_source_list_enabled == 1
           and $this->_count_auth_source_list_add_account == 0 ) {
         $this->_form->addText('auth_not_available',$this->_translator->getMessage('AUTH_NOT_AVAILABLE2'),'');
         // buttons
         $this->_form->addButtonBar('option','',$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,'',13);
      } elseif ($this->_count_auth_source_list_add_account == 0) {
         $this->_form->addText('auth_not_available',$this->_translator->getMessage('AUTH_NOT_AVAILABLE'),'');
         // buttons
         $this->_form->addButtonBar('option','',$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,'',13);
      } else {
         $this->_form->addTextField('user_id','',getMessage('USER_USER_ID'),'','',24,true);
         $this->_form->addButtonBar('option',getMessage('PASSWORD_GENERATE_BUTTON'),getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,6.4,6.3);
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
      if ( !empty($this->_form_post['user_id'])
           and !empty($this->_form_post['auth_source'])
           and is_numeric($this->_form_post['auth_source'])
         ) {
         $authentication_item = $this->_environment->getAuthenticationObject();
         $auth_manager = $authentication_item->getAuthManager($this->_form_post['auth_source']);
         if (!$auth_manager->exists($this->_form_post['user_id'])) {
            $this->_error_array[] = getMessage('USER_USER_ID_NOT_EXIST',$this->_form_post['user_id']);
            $this->_form->setFailure('user_id');
         }
      } elseif ( !empty($this->_form_post['user_id'])
                 and !empty($this->_form_post['auth_source'])
               ) {
         $this->_error_array[] = getMessage('USER_AUTH_SOURCE_ERROR_NOT_AVAILABLE',$this->_form_post['auth_source']);
      } else {
         $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_MERGE_ERROR_AUTH_SOURCE');
      }
   }

   /** In case a lost password was regenerated successfully this page gets displayed.
    */
   function showMailSent($emailAddress) {
      $this->_form = new cs_form();
      $context = $this->_environment->getCurrentContextItem();
      $this->_form->addText('text',getMessage('COMMON_HINTS'),getMessage('USER_PASSWORD_FORGET_SUCCESS_TEXT',$context->getTitle(),$emailAddress));
      $this->_form->addButtonBar('option',getMessage('COMMON_FORWARD_BUTTON'));
   }

   /** In case of mail server error the following page gets displayed.
    */
   function showMailFailure() {
      $this->_form = new cs_form();
      $moderation_link = ahref_curl($this->_environment->getCurrentPortalID(),'mail','to_moderator','',getMessage('CONTEXT_MODERATOR'));
      $this->_form->addText('text',getMessage('COMMON_HINTS'),getMessage('ERROR_MAIL_SERVER',$moderation_link));
      $this->_form->addButtonBar('option',getMessage('COMMON_FORWARD_BUTTON'));
   }
}
?>