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
class cs_home_member_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * string - containing the subtitle of the form
   */
   var $_subtitle = NULL;

   var $_back_button = false;

   var $_description_array = array();

   var $_auth_source_array = array();

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_home_member_form ($params) {
      $this->cs_rubric_form($params);
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

      if (isset($_GET['invitation_auth_source']) && isset($_GET['invitation_auth_code'])) {
          $auth_source_manager = $this->_environment->getAuthSourceManager();
          $auth_source_item = $auth_source_manager->getItem($_GET['invitation_auth_source']);

          global $symfonyContainer;
          $invitationsService = $symfonyContainer->get('commsy.invitations_service');

          if ($invitationsService->confirmInvitationCode($auth_source_item, $_GET['invitation_auth_code'])) {
              $this->_count_auth_source_list_add_account = 1;
              $this->_count_auth_source_list_enabled = 1;
              $this->_default_auth_source_entry = $auth_source_item->getItemId();

              $temp_array = array();
              $temp_array['value'] = $auth_source_item->getItemID();
              $temp_array['text'] = $auth_source_item->getTitle();
              $this->_auth_source_array[0] = $temp_array;

          }
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $this->_form->addHeadline('title',$this->_translator->getMessage('ACCOUNT_FORM_TITLE'));
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
         $this->_form->addTextField('firstname','',$this->_translator->getMessage('USER_FIRSTNAME'),'','',21,true,'','','','left','',13);
         $this->_form->addTextField('lastname','',$this->_translator->getMessage('USER_LASTNAME'),'','',21,true,'','','','left','',13);
         $this->_form->addTextField('email','',$this->_translator->getMessage('USER_EMAIL'),'','',21,true,'','','','left','',13);
         $this->_form->addTextField('email_confirmation','',$this->_translator->getMessage('USER_EMAIL_CONFIRMATION'),'','',21,true,'','','','left','',13);
         $this->_form->addHidden('language','');
         $this->_form->addTextField('user_id','',$this->_translator->getMessage('USER_USER_ID'),'',100,21,true,'','','','left','',13);
         // Hinweis für das bauen des Passwortes
         
         #$link_pw = ahref_curl($this->_environment->getCurrentContextID(), 'home', 'index', array('cs_modus' => 'portalmember'), $this->_translator->getMessage('USER_PASSWORD_GUIDELINE'),'','','','','title="test"');
         
         $auth_source_manager = $this->_environment->getAuthSourceManager();
         $auth_source_item = $auth_source_manager->getItem($this->_auth_source_array[0]['value']);
         
         if($auth_source_item->getPasswordLength() > 0){
         	$password_length = $auth_source_item->getPasswordLength();
         	$output_password = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_LENGTH').': '.$password_length;
         }
         if($auth_source_item->getPasswordSecureBigchar() == 1){
         	$output_password .= ', '.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_BIGCHAR');
         }
         if($auth_source_item->getPasswordSecureSpecialchar() == 1){
         	$output_password .= ', '.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SPECIALCHAR');
         }
         if($auth_source_item->getPasswordSecureNumber() == 1){
         	$output_password .= ', '.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_NUMBER');
         }
         if($auth_source_item->getPasswordSecureSmallchar() == 1){
         	$output_password .= ', '.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SMALLCHAR');
         }
         if(!empty($output_password)){
         	$link_pw = ' (<a href="#" title="'.$output_password.'">'. $this->_translator->getMessage('USER_PASSWORD_GUIDELINE').'</a>)';
         } else {
         	$link_pw = '';
         }
         
         $this->_form->addPassword('password','',$this->_translator->getMessage('USER_PASSWORD').''.$link_pw,'','',21,true,13);
         $this->_form->addPassword('password2','',$this->_translator->getMessage('USER_PASSWORD2'),'','',21,true,13);  
         
         // link which refers to the terms of use
         // Datenschutz
         if ($this->_environment->getCurrentContextItem()->withAGB() and $this->_environment->getCurrentContextItem()->withAGBDatasecurity()){
         	$link = ahref_curl($this->_environment->getCurrentContextID(), 'agb', 'index', '', $this->_translator->getMessage('CONFIGURATION_AGB_FORM_HEADLINE'),'','_new','','','onClick="window.open(href,target,\'toolbar=no, location=no,directories=no,status=no,menubar=yes,scrollbars=yes,resizable=yes,copyhistory=yes,width=600,height=400\');"');
         	#$link = ahref_curl($this->_environment->getCurrentContextID(), 'agb', 'index', '', $this->_translator->getMessage('CONFIGURATION_AGB_FORM_HEADLINE'),'','_new');
         	$this->_form->addCheckbox('terms_of_use', '1', false, '', $this->_translator->getMessage('CONFIGURATION_AGB_ACCEPT').$link);
         }

         // buttons
         $this->_form->addButtonBar('option',$this->_translator->getMessage('ACCOUNT_GET_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,6.5,6.5);
      }
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
         } else {
             $auth_source_manager = $this->_environment->getAuthSourceManager();
             $auth_source_item = $auth_source_manager->getItem($this->_form_post['auth_source']);
             if($auth_source_item->getEmailRegex() != ''){
                 if (!preg_match($auth_source_item->getEmailRegex(), $this->_form_post['email'])) {
                     global $symfonyContainer;
                     $translationService = $symfonyContainer->get('commsy.translation_service');
                     $this->_error_array[] = $translationService->getTranslationByKey($this->_environment->getCurrentPortalId(), 'EMAIL_REGEX_ERROR', $this->_environment->getUserLanguage());
                 }
             }
         }
      }
      if ($this->_environment->getCurrentContextItem()->withAGB() and $this->_environment->getCurrentContextItem()->withAGBDatasecurity()){
      	if (!isset($this->_form_post['terms_of_use'])){
      		$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AGB_ACCEPT_ERROR');
      		$this->_form->setFailure('terms_of_use','');
      	}
      }
      
      
      // password check
      if ($this->_form_post['password'] != $this->_form_post['password2']) {
         $this->_error_array[] = $this->_translator->getMessage('USER_PASSWORD_ERROR');
         $this->_form->setFailure('password','');
         $this->_form->setFailure('password2','');
      }
      if(isset($this->_form_post['auth_source'])) {
	      $auth_source_manager = $this->_environment->getAuthSourceManager();
	      $auth_source_item = $auth_source_manager->getItem($this->_form_post['auth_source']);
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


      // is user id free?
      if ( !empty($this->_form_post['auth_source'])
           and is_numeric($this->_form_post['auth_source'])
         ) {
         $authentication = $this->_environment->getAuthenticationObject();
         if ( !$authentication->is_free($this->_form_post['user_id'],$this->_form_post['auth_source']) ) {
            $error_array = $authentication->getErrorArray();
            if (count($error_array) > 0) {
               $this->_error_array = array_merge($this->_error_array,$error_array);
            } else {
               $this->_error_array[] = $this->_translator->getMessage('USER_USER_ID_ERROR',$this->_form_post['user_id']);
            }
            $this->_form->setFailure('user_id','');
         } elseif ( withUmlaut($this->_form_post['user_id']) ) {
            $this->_error_array[] = $this->_translator->getMessage('USER_USER_ID_ERROR_UMLAUT',$this->_form_post['user_id']);
            $this->_form->setFailure('user_id','');
         }
      } elseif ( !empty($this->_form_post['auth_source']) ) {
         $this->_error_array[] = $this->_translator->getMessage('USER_AUTH_SOURCE_ERROR_NOT_AVAILABLE',$this->_form_post['auth_source']);
      } else {
         $this->_error_array[] = $this->_translator->getMessage('USER_AUTH_SOURCE_ERROR');
      }
   }
}
?>