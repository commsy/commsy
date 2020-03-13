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

/** class for commsy forms
 * this class implements an interface for the creation of forms in the commsy style
 */
class cs_configuration_authentication_form extends cs_rubric_form {

  /**
   * string - containing the headline of the form
   */
   var $_headline = NULL;

  /**
   * array - containing the mail texts to choose
   */
   var $_array_auth_source = NULL;

   var $_yes_no_array = array();

   var $_encryption_array = array();

   var $_disable_default = false;

   var $_disable_show = false;

   var $_disable_ims = false;

   var $_commsy_default = false;

   var $_auth_type = '';

   var $_auth_type_array = NULL;

   var $_disable_change_user_id = false;
   var $_disable_change_password = false;
   var $_disable_change_user_data = false;
   var $_disable_add_user = false;
   var $_disable_delete_user = false;
   var $_disable_password_check = false;
   
   private $_current_additional_server = 0;
   private $_add_additional_server = false;
   private $_select_server = array();

  /** constructor
    * the only available constructor
    *
    * @param array params array of parameter
    */
   function __construct($params) {
      cs_rubric_form::__construct($params);
   }
   
   public function setAddOneAdditionalServer () {
   	$this->_add_additional_server = true;
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

   	// auth text choice
      $this->_array_auth_source[0]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT');
      $this->_array_auth_source[0]['value'] = -1;

      // auth sources
      $counter = 1;
      $current_context = $this->_environment->getCurrentContextItem();
      $auth_source_list = $current_context->getAuthSourceList();
      if ( !$auth_source_list->isEmpty() ) {
         $this->_array_auth_source[$counter]['text']  = '----------------------';
         $this->_array_auth_source[$counter]['value'] = 'disabled';
         $counter++;
         $item = $auth_source_list->getFirst();
         while ($item) {
            $this->_array_auth_source[$counter]['text']  = $item->getTitle();
            $this->_array_auth_source[$counter]['value'] = $item->getItemID();
            $counter++;
            $item = $auth_source_list->getNext();
         }
         $count_source = $auth_source_list->getCount();
      }

      if ($count_source == 1) {
         $this->_disable_default = true;
         $this->_disable_show = true;
      }
      if ( isset($this->_item) and $this->_item->getItemID() == $current_context->getAuthDefault() ) {
         $this->_disable_default = true;
      } elseif ( isset($this->_form_post['disable_default']) and $this->_form_post['disable_default'] == 'yes') {
         $this->_disable_default = true;
      }

      // insert new auth source
      $this->_array_auth_source[$counter]['text']  = '----------------------';
      $this->_array_auth_source[$counter]['value'] = 'disabled';
      $counter++;
      $this->_array_auth_source[$counter]['text']  = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_NEW');
      $this->_array_auth_source[$counter]['value'] = 'new';

      // auth type
      if ( isset($this->_form_post['auth_source'])
           and !empty($this->_form_post['auth_source'])
           and $this->_form_post['auth_source'] == 'new' ) {
         $this->_auth_type_array = array();
         $counter = 0;
         $this->_auth_type_array[$counter]['text']  = '*'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT_TYPE');
         $this->_auth_type_array[$counter]['value'] = -1;
         $counter++;
         $this->_auth_type_array[$counter]['text']  = '----------------------';
         $this->_auth_type_array[$counter]['value'] = 'disabled';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'CAS';
         $this->_auth_type_array[$counter]['value'] = 'CAS';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'LDAP';
         $this->_auth_type_array[$counter]['value'] = 'LDAP';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'MySQL: allgemein';
         $this->_auth_type_array[$counter]['value'] = 'MYSQL';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'MySQL: Typo3';
         $this->_auth_type_array[$counter]['value'] = 'Typo3';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'Typo3';
         $this->_auth_type_array[$counter]['value'] = 'Typo3Web';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'Shibboleth';
         $this->_auth_type_array[$counter]['value'] = 'Shibboleth';
         $this->_disable_default = false;
         $this->_disable_show = false;
      }

      // yes no array
      $this->_yes_no_array[0]['text'] = $this->_translator->getMessage('COMMON_YES');
      $this->_yes_no_array[0]['value'] = 1;
      $this->_yes_no_array[1]['text'] = $this->_translator->getMessage('COMMON_NO');
      $this->_yes_no_array[1]['value'] = 2;

      $this->_encryption_array[0]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_NONE');
      $this->_encryption_array[0]['value'] = 'none';
      $this->_encryption_array[1]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_TYPO3_MD5');
      $this->_encryption_array[1]['value'] = 'md5';

      // commsy default
      if ( isset($this->_item) and $this->_item->isCommSyDefault() ) {
         $this->_commsy_default = true;
      } elseif ( isset($this->_form_post['commsy_default']) and $this->_form_post['commsy_default'] == 'yes') {
         $this->_commsy_default = true;
      }

      // auth functions implemented
      if ( !isset($this->_form_post['auth_type']) and !empty($this->_form_post['auth_type_hidden']) ) {
         $this->_form_post['auth_type'] = $this->_form_post['auth_type_hidden'];
      }
      if ( ( isset($this->_form_post['auth_type'])
             and !empty($this->_form_post['auth_type'])
             and $this->_form_post['auth_type'] != -1 )
           or ( isset($this->_item) )
         ) {
         $auth_object = $this->_environment->getAuthenticationObject();
         if ( !isset($this->_item) ) {
            if ( !empty($this->_form_post['auth_source'])
                 and is_int($this->_form_post['auth_source'])
                 and $this->_form_post['auth_source'] != -1
               ) {
               $auth_manager = $auth_object->getAuthManager($this->_form_post['auth_source']);
            } elseif ($this->_form_post['auth_type'] != -1) {
               $auth_manager = $auth_object->getAuthManagerByType($this->_form_post['auth_type']);
            }
            $this->_auth_type = $this->_form_post['auth_type'];
         } else {
            $auth_manager = $auth_object->getAuthManager($this->_item->getItemID());
            $this->_auth_type = $this->_item->getSourceType();
         }
         if ( isset($auth_manager) ) {
            if ( !$auth_manager->isChangeUserIdImplemented() ) {
               $this->_disable_change_user_id = true;
            }
            if ( !$auth_manager->isChangeUserDataImplemented() ) {
               $this->_disable_change_user_data = true;
            }
            if ( !$auth_manager->isChangePasswordImplemented() ) {
               $this->_disable_change_password = true;
            }
            if ( !$auth_manager->isAddAccountImplemented() ) {
               $this->_disable_add_user = true;
            }
            if ( !$auth_manager->isDeleteAccountImplemented() ) {
               $this->_disable_delete_user = true;
            }
         } else {
            $this->_disable_change_user_id = true;
            $this->_disable_change_user_data = true;
            $this->_disable_change_password = true;
            $this->_disable_add_user = true;
            $this->_disable_delete_user = true;
         }
         $param = $this->_environment->getCurrentPostParameterArray();
         if(isset($param['auth_source']) AND $this->_commsy_default){
	         $auth_source_manager = $this->_environment->getAuthSourceManager();
		      $auth_source_item = $auth_source_manager->getItem($param['auth_source']);
		      if(isset($auth_source_item)){
			      if($auth_source_item->isPasswordSecureActivated()){
			      	$this->_disable_password_check = false;
			      } else {
			      	$this->_disable_password_check = true;
			      }
		      }
         }
	      #$this->_disable_password_check = true;
      }
      
      // additonal server
      if ( !empty($this->_item) ) {
         $auth_data_array = $this->_item->getAuthData();
         if ( !empty($auth_data_array['additional_server']) ) {
         	$this->_current_additional_server = count($auth_data_array['additional_server']);
         	if ( $this->_current_additional_server > 0 ) {
         		$counter = 0;
               $this->_select_server[$counter]['text']  = $auth_data_array['HOST'];
               $this->_select_server[$counter]['value'] = $auth_data_array['HOST'];
               foreach ( $auth_data_array['additional_server'] as $value_array ) {
               	if ( !empty($value_array['host']) ) {
                     $counter++;
               		$this->_select_server[$counter]['text']  = $value_array['host'];
               		$this->_select_server[$counter]['value'] = $value_array['host'];            		 
               	}
               }
               unset($counter);
         	}
         }
      } elseif (!empty($param['additional_server_count']) ) {
      	$counter = 0;
         foreach ( $param as $key => $value ) {
         	if ( substr($key,0,4) == 'host'
         		  and $key != 'host_new'
         		  and !empty($value)
         		) {
      		   $this->_select_server[$counter]['text']  = $value;
       		   $this->_select_server[$counter]['value'] = $value;         		
        		   $counter++;
         	}
         }
         unset($counter);
         if ( !empty($this->_select_server)
         	  and count($this->_select_server) == 1 
         	) {
         	$this->_select_server = array();
         }
      	$this->_current_additional_server = $param['additional_server_count'];
      }
   }

   /** create the form, INTERNAL
    * this methods creates the form with the form definitions
    */
   function _createForm () {
      $translator = $this->_environment->getTranslationObject();
      if ( isset($this->_form_post['auth_source'])
           and $this->_form_post['auth_source'] != -1
           and ( $this->_form_post['auth_source'] != 'new'
                 or ( isset($this->_form_post['auth_type'])
                      and $this->_form_post['auth_type'] != -1
                    )
               )
         ) {
         $disabled = false;
      } elseif ( !empty($this->_item) ) {
         $disabled = false;
      } else {
         $disabled = true;
      }

      $this->setHeadline($this->_headline);

      $this->_form->addSelect( 'auth_source',
                               $this->_array_auth_source,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_SOURCE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option');
      $context_item = $this->_environment->getCurrentContextItem();
      
      
      $this->_form->addEmptyLine();
      if ( !isset($this->_auth_type_array) ) {
      	if($disabled){
      		$this->_form->addText('auth_type',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE_SETTING'));
      	} else {
      		$this->_form->addText('auth_type',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE'),$this->_auth_type);
      		$this->_form->addHidden('auth_type_hidden',$this->_auth_type);
      	}
         
      } else {
         $this->_form->addSelect( 'auth_type',
                                  $this->_auth_type_array,
                                  '',
                                  $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE'),
                                  '',
                                  '',
                                  '',
                                  '',
                                  true,
                                  $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
                                  'option');
      }
      if(!$disabled){
      	$this->_form->addTextfield('title','',$this->_translator->getMessage('COMMON_TITLE'),'',50,20,true,'','','','','','',$disabled);
      }
      
      if ( $this->_disable_default ) {
         $this->_form->addHidden('disable_default','yes');
         $this->_form->addHidden('default',1);
      }
      if ( $this->_disable_show ) {
         $this->_form->addHidden('show',1);
      }
      if(!$disabled){
	      $this->_form->addRadioGroup('default',$translator->getMessage('COMMON_DEFAULT'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_default);
	      $this->_form->addRadioGroup('ims',$translator->getMessage('COMMON_IMS'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_ims);
	      $this->_form->addRadioGroup('show',$translator->getMessage('COMMON_ACTIVATED'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_show);
	      $this->_form->addEmptyLine();
      }

      // CAS
      if ( $this->_auth_type == 'CAS' ) {
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,$translator->getMessage('CONFIGURATION_AUTHENTICATION_PATH_DESC'));
         $this->_form->addTextfield('path','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PATH'),'','',21,true,'','','','','https://www.cas-server.de','',false,'/login');
         $this->_form->addEmptyLine();
      }

      // typo3
      elseif ( $this->_auth_type == 'Typo3' ) {

         $this->_form->addTextfield('dbname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBNAME'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('port','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PORT'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('userid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER'),'','',21,true,'','','','','','',false,'');
         $this->_form->addPassword('password','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW'),'','',21,true,'','','','','','',false,'');
         $this->_form->addRadioGroup('encryption',$translator->getMessage('CONFIGURATION_AUTHENTICATION_TYPO3_ENCRYPTION'),'',$this->_encryption_array,'','',true,'','',$disabled or $this->_disable_show);
         $this->_form->addEmptyLine();
      }
      // MySQL (allgmein)
      elseif ( $this->_auth_type == 'MYSQL' and !$this->_commsy_default) {
         $this->_form->addTextfield('dbname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBNAME'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('dbtable','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBTABLE'),'','',21,true,'','','','','','',false,'');
         // Die folgenden beiden Zeilen spezifizieren die Tabellenspalten, in denen sich der zu authentifizierende Benutzername und Passwort befinden
         $this->_form->addTextfield('dbcolumnuserid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBCOLUMNUSERID'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('dbcolumnpasswd','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBCOLUMNPASSWD'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('port','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PORT'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('userid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('password','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW'),'','',21,true,'','','','','','',false,'');
         $this->_form->addRadioGroup('encryption',$translator->getMessage('CONFIGURATION_AUTHENTICATION_TYPO3_ENCRYPTION'),'',$this->_encryption_array,'','',true,'','',$disabled or $this->_disable_show);
         $this->_form->addEmptyLine();
      }
      // LDAP
      elseif ( $this->_auth_type == 'LDAP' ) {
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,'');
         
         $this->_form->addTextfield('dbsearchuserid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_DBSEARCHUSERID'),'','',21,true,'','','','','','',false,'');
         $this->_form->combine();
         $this->_form->addText('dbsearchuserid_text','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_DBSEARCHUSERID_DESC'));
         $this->_form->addTextfield('base','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_SUBTREE'),'','',21,true,'','','','','','',false,'');
         $this->_form->addText('choice','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_SUBTREE_OR'));
         $this->_form->addTextfield('userid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER'),'','',21,true,'','','','','','',false,'');
         $this->_form->addPassword('password','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW'),'','',22,true,'','','','','','',false,'');
         $this->_form->addRadioGroup('encryption',$translator->getMessage('CONFIGURATION_AUTHENTICATION_TYPO3_ENCRYPTION'),'',$this->_encryption_array,'',true,true,'','','');
         $this->_form->combine();
         $this->_form->addText('encryption_text','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_ENCRYPTION'));

         $this->_form->addEmptyLine();
      }
      // Typo3Web
      elseif ( $this->_auth_type == 'Typo3Web' ) {
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,'');

         $this->_form->addEmptyLine();
      }
      
      // Shibboleth - Add configuration form for Shibboleth
      elseif ( $this->_auth_type == 'Shibboleth'){
      	$this->_form->addCheckbox('direct_login', true, false, $translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_DIRECT_LOGIN'),'');
      	$this->_form->addTextfield('session_initiator_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_SESSION_INITIATOR'),'','',21,true,'','','','','','',false,'');
      	$this->_form->addTextfield('session_logout_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_SESSION_LOGOUT'),'','',21,true,'','','','','','',false,'');
      	$this->_form->addTextfield('password_change_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_PASSWORD_CHANGE'),'','',21,false,'','','','','','',false,'');
      	$this->_form->addTextfield('username','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_USERNAME'),'','',21,true,'','','','','','',false,'');
      	$this->_form->addTextfield('firstname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_FIRSTNAME'),'','',21,false,'','','','','','',false,'');
      	$this->_form->addTextfield('lastname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_LASTNAME'),'','',21,false,'','','','','','',false,'');
      	$this->_form->addTextfield('email','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_EMAIL'),'','',21,true,'','','','','','',false,'');
      	$this->_form->addCheckbox('update_user_data', true, false, $translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_UPDATE_DATA'),'');
      	$this->_form->addEmptyline();
      }

      if ( !$this->_commsy_default and !empty($this->_auth_type) ) {
         $this->_form->addTextfield('contact_fon','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CONACT_FON_TITLE'),'',255,40,false,'','','','','','',$disabled);
         $this->_form->addTextfield('contact_mail','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CONACT_MAIL_TITLE'),'',255,40,false,'','','','','','',$disabled);
         $this->_form->addTextfield('change_password_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_PASSWORD_URL'),'',255,40,false,'','','','','','',$disabled);
         $this->_form->addEmptyLine();
      }
      
      if(!$disabled){
	
	      if ( $this->_disable_change_user_id ) {
	         $this->_form->addhidden('changeUserID',2);
	         $this->_form->addText('textchangeUserID',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_USERID_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
	      } else {
	         $this->_form->addRadioGroup('changeUserID',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_USERID_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      }
	
	      if ( $this->_disable_change_user_data ) {
	         $this->_form->addhidden('changeUserData',2);
	         $this->_form->addText('textchangeUserData',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_USERDATA_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
	      } else {
	         $this->_form->addRadioGroup('changeUserData',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_USERDATA_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      }
	
	      if ( $this->_disable_change_password ) {
	         $this->_form->addhidden('changePassword',2);
	         $this->_form->addText('textchangePassword',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_PASSWORD_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
	      } else {
	         $this->_form->addRadioGroup('changePassword',$translator->getMessage('CONFIGURATION_AUTHENTICATION_CHANGE_PASSWORD_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      }
	
	      if ( $this->_disable_add_user ) {
	         $this->_form->addhidden('addAccount',2);
	         $this->_form->addText('textaddAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_ADD_ACCOUNT_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
	      } else {
             $disable_add_user_options[0]['text'] = $this->_translator->getMessage('COMMON_YES');
             $disable_add_user_options[0]['value'] = 1;
             $disable_add_user_options[1]['text'] = $this->_translator->getMessage('COMMON_NO');
             $disable_add_user_options[1]['value'] = 2;
             $disable_add_user_options[2]['text'] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_INVITATION');
             $disable_add_user_options[2]['value'] = 3;
	         $this->_form->addRadioGroup('addAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_ADD_ACCOUNT_TITLE'),'',$disable_add_user_options,'','',true,'','',$disabled);
	      }
	      if ( $this->_disable_delete_user) {
	         $this->_form->addhidden('deleteAccount',2);
	         $this->_form->addText('textdeleteAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DELETE_ACCOUNT_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
	      } else {
	         $this->_form->addRadioGroup('deleteAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DELETE_ACCOUNT_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      }
      }
      
      if($this->_commsy_default){
      	$this->_form->addEmptyLine();
      	$this->_form->addRadioGroup('password_secure_check',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_CONTROL'),'',$this->_yes_no_array,'','',true,'','',$disabled);	      
	      $this->_form->addRadioGroup('password_bigchar',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_BIGCHAR'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      $this->_form->addRadioGroup('password_number',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_NUMBER'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      $this->_form->addRadioGroup('password_smallchar',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SMALLCHAR'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      $this->_form->addRadioGroup('password_specialchar',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SPECIALCHAR'),'',$this->_yes_no_array,'','',true,'','',$disabled);
	      $this->_form->addTextfield('password_length','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_LENGTH'),'',2,10,false,'','','','','','',$disabled);
	      $this->_form->addTextfield('password_expiration','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_EXPIRATION'),'',3,10,false,'','','','','','',$disabled);
	      $this->_form->addTextfield('days_before_expiring_sendmail','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_SEND_MAIL'),'',2,10,false,'','','','','','',$disabled);
	      $this->_form->addTextfield('password_generation','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_GENERATION'),'',2,10,false,'','','','','','',$disabled);
	      $this->_form->combine();
	      $this->_form->addText('Info', $translator->getMessage('CONFIGURATION_AUTHENTICATION_GENERATION'), $translator->getMessage('CONFIGURATION_AUTHENTICATION_GENERATION_INFO'));
      }

       if($this->_commsy_default){
           $this->_form->addEmptyLine();
           $this->_form->addTextfield('email_regex','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_REGEX'),'',250,30,false,'','','','','','',$disabled);
           $this->_form->combine();
           $this->_form->addText('Info E-Mail Regex', $translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_REGEX_INFO'), $translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_REGEX_INFO'));

           $this->_form->addEmptyLine();
           $this->_form->addText('', $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_ERROR_MESSAGE_LINK'), '<a href="/portal/'.$this->_environment->getCurrentPortalId().'/translations">'.$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_ERROR_MESSAGE_LINK_INFO').'</a>');
       }

       if(!$disabled) {
           $this->_form->addEmptyLine();
           $this->_form->addRadioGroup('user_is_allowed_to_create_context', $translator->getMessage('CONFIGURATION_AUTHENTICATION_USER_IS_ALLOWED_TO_CREATE_CONTEXT'), '', $this->_yes_no_array, '', '', true, '', '', $disabled);
       }

      if(!$disabled){
      	$this->_form->addEmptyLine();
      }
      //Datenschutz
      if ( empty($this->_auth_type)
      	  and !isset($this->_auth_type_array)     		
      	) {
         $this->_form->addRadioGroup('temporary_lock', $translator->getMessage('CONFIGURATION_AUTHENTICATION_USER_LOCK'),'',$this->_yes_no_array,'','',true,'','',false);
         $this->_form->addTextfield('seconds_interval','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER_LOCK_INTERVAL'),'',3,10,false,'','','','','','',false);
         $this->_form->addTextfield('try_until_lock','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_TRY_UNTIL_LOCK'),'',2,10,false,'','','','','','',false);
         $this->_form->addTextfield('temporary_minutes','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER_LOCK_TIME'),'',2,10,false,'','','','','','',false);
         #$this->_form->addRadioGroup('expired_password', 'Intervall Passwortänderung','',$this->_yes_no_array,'','',true,'','',$disabled);
      }

      // specific options
      if ( !$this->_commsy_default ) {
         $this->_form->addEmptyLine();
      } else {
         $this->_form->addHidden('commsy_default','yes');
      }

      // buttons
      $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','',false);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( !empty($this->_values['auth_source']) and mb_strlen($this->_values['auth_source']) == 2 and $this->_values['auth_source'] != -1) {
            $this->_values['auth_source'] = -1;
         }
         if ( isset($this->_values['auth_type_hidden']) and !empty($this->_values['auth_type_hidden']) ) {
            $this->_values['auth_type'] = $this->_values['auth_type_hidden'];
         }
         if ( !isset($this->_values['ims']) )  {
            $this->_values['ims'] = 2;
         }
         if ( !isset($this->_values['default']) ) {
            $this->_values['default'] = 2;
         }
         if ( !isset($this->_values['show']) ) {
            $this->_values['show'] = 1;
         }
         if ( !isset($this->_values['encryption'])
              and isset($this->_values['auth_type'])
              and mb_strtolower($this->_values['auth_type'], 'UTF-8') == 'typo3'
           ) {
            $this->_values['encryption'] = 'md5';
         }
         if ( !isset($this->_values['port'])
              and isset($this->_values['auth_type'])
            ) {
            if ( mb_strtolower($this->_values['auth_type'], 'UTF-8') == 'typo3' ) {
               $this->_values['port'] = '3306';
            } elseif ( mb_strtolower($this->_values['auth_type'], 'UTF-8') == 'ldap' ) {
               $this->_values['port'] = '389';
            }
         }

      } elseif ( !empty($this->_item) ) {
         $this->_values['auth_source'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $this->_values['change_password_url'] = $this->_item->getPasswordChangeLink();
         $this->_values['contact_mail'] = $this->_item->getContactEMail();
         $this->_values['contact_fon'] = $this->_item->getContactFon();
         $this->_values['password_secure_check'] = $this->_item->getPasswordSecureCheck();
         $this->_values['password_bigchar'] = $this->_item->getPasswordSecureBigchar();
         $this->_values['password_specialchar'] = $this->_item->getPasswordSecureSpecialchar();
         $this->_values['password_length'] = $this->_item->getPasswordLength();
         $this->_values['password_smallchar'] = $this->_item->getPasswordSecureSmallchar();
         $this->_values['password_number'] = $this->_item->getPasswordSecureNumber();

         $this->_values['email_regex'] = $this->_item->getEmailRegex();

         if ($this->_item->isUserAllowedToCreateContext()) {
             $this->_values['user_is_allowed_to_create_context'] = 1;
         } else {
             $this->_values['user_is_allowed_to_create_context'] = 2;
         }

         $current_context = $this->_environment->getCurrentContextItem();
         
         // Datenschutz
         $this->_values['temporary_lock'] = $current_context->getTemporaryLock();
         $this->_values['seconds_interval'] = $current_context->getLockTimeInterval();
         $this->_values['temporary_minutes'] = $current_context->getLockTime();
         $this->_values['password_generation'] = $current_context->getPasswordGeneration();
         $this->_values['password_expiration'] = $current_context->getPasswordExpiration();
         $this->_values['try_until_lock'] = $current_context->getTryUntilLock();
         $this->_values['days_before_expiring_sendmail'] = $current_context->getDaysBeforeExpiringPasswordSendMail();

         // Shibboleth
         $this->_values['direct_login'] = $this->_item->getShibbolethDirectLogin();
         $this->_values['session_initiator_url'] = $this->_item->getShibbolethSessionInitiator();
         $this->_values['session_logout_url'] = $this->_item->getShibbolethSessionLogout();
         $this->_values['password_change_url'] = $this->_item->getShibbolethPasswordChange();
         $this->_values['username'] = $this->_item->getShibbolethUsername();
         $this->_values['firstname'] = $this->_item->getShibbolethFirstname();
         $this->_values['lastname'] = $this->_item->getShibbolethLastname();
         $this->_values['email'] = $this->_item->getShibbolethEmail();
         $this->_values['update_user_data'] = $this->_item->getShibbolethUpdateData();
         
         if( empty($this->_values['password_secure_check'])){
         	$this->_values['password_secure_check'] = 2;
         	$this->_disable_password_check = true;
         }
         if($this->_values['password_secure_check'] == 2){
         	$this->_disable_password_check = true;
         } else {
         	$this->_disable_password_check = false;
         }

         if( empty($this->_values['password_length'])){
         	$this->_values['password_length'] = 0;
         }
         if( empty($this->_values['password_bigchar'])){
         	$this->_values['password_bigchar'] = 2;
         }
         if( empty($this->_values['password_specialchar'])){
         	$this->_values['password_specialchar'] = 2;
         }
         if( empty($this->_values['password_number'])){
         	$this->_values['password_number'] = 2;
         }
         if( empty($this->_values['password_smallchar'])){
         	$this->_values['password_smallchar'] = 2;
         }
         
         // Datenschutz
         if( empty($this->_values['temporary_lock'])){
         	$this->_values['temporary_lock'] = 2;
         }
         if( empty($this->_values['seconds_interval'])){
         	$this->_values['seconds_interval'] = 0;
         }
         if( empty($this->_values['temporary_minutes'])) {
         	$this->_values['temporary_minutes'] = 0;
         }
         if( empty($this->_values['password_generation'])) {
         	$this->_values['password_generation'] = 0;
         }
         if( empty($this->_values['password_expiration'])) {
         	$this->_values['password_expiration'] = 0;
         }
         if( empty($this->_values['try_until_lock'])) {
         	$this->_values['try_until_lock'] = 0;
         }

         if($this->_item->isUserAllowedToCreateContext()){
             $this->_values['user_is_allowed_to_create_context'] = 1;
         } else {
             $this->_values['user_is_allowed_to_create_context'] = 2;
         }
         
//          if($this->_values['temporary_lock'] == 2){
//          	$this->
//          } else {
         	
//          }
         

         if ( $this->_item->getItemID() == $current_context->getAuthDefault() ) {
            $this->_values['default'] = 1;
         } else {
            $this->_values['default'] = 2;
         }
         if ( $this->_item->getItemID() == $current_context->getAuthIMS() ) {
            $this->_values['ims'] = 1;
         } else {
            $this->_values['ims'] = 2;
         }

         if ( $this->_item->Show() ) {
            $this->_values['show'] = 1;
         } else {
            $this->_values['show'] = 2;
         }
         if ( $this->_item->allowChangeUserID() ) {
            $this->_values['changeUserID'] = 1;
         } else {
            $this->_values['changeUserID'] = 2;
         }
         if ( $this->_item->allowChangeUserData() ) {
            $this->_values['changeUserData'] = 1;
         } else {
            $this->_values['changeUserData'] = 2;
         }
         if ( $this->_item->allowChangePassword() ) {
            $this->_values['changePassword'] = 1;
         } else {
            $this->_values['changePassword'] = 2;
         }
         if ( $this->_item->allowAddAccount() ) {
            $this->_values['addAccount'] = 1;
         } else {
             if (!$this->_item->allowAddAccountInvitation()) {
                 $this->_values['addAccount'] = 2;
             } else {
                 $this->_values['addAccount'] = 3;
             }
         }
         if ( $this->_item->allowDeleteAccount() ) {
            $this->_values['deleteAccount'] = 1;
         } else {
            $this->_values['deleteAccount'] = 2;
         }
         if ( $this->_item->isCommSyDefault() ) {
            $this->_values['auth_type'] = 'CommSy';
            $this->_values['auth_type_hidden'] = 'CommSy';
         } else {
            $this->_values['auth_type'] = $this->_item->getSourceType();
            $this->_values['auth_type_hidden'] = $this->_item->getSourceType();
         }

         // CAS
         if ( $this->_values['auth_type'] == 'CAS' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['HOST']) ) {
               $this->_values['host'] = $auth_data_array['HOST'];
            }
            if ( !empty($auth_data_array['PATH']) ) {
               $this->_values['path'] = $auth_data_array['PATH'];
            }
         }

         // typo3
         elseif ( $this->_values['auth_type'] == 'Typo3' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['DBNAME']) ) {
               $this->_values['dbname'] = $auth_data_array['DBNAME'];
            }
            if ( !empty($auth_data_array['HOST']) ) {
               $this->_values['host'] = $auth_data_array['HOST'];
            }
            if ( !empty($auth_data_array['PORT']) ) {
               $this->_values['port'] = $auth_data_array['PORT'];
            }
            if ( !empty($auth_data_array['USER']) ) {
               $this->_values['userid'] = $auth_data_array['USER'];
            }
            if ( !empty($auth_data_array['PASSWORD']) ) {
               $this->_values['password'] = $auth_data_array['PASSWORD'];
            }
            if ( !empty($auth_data_array['ENCRYPTION']) ) {
               $this->_values['encryption'] = $auth_data_array['ENCRYPTION'];
            }
         }

         // MySQL (beliebiges DB-Format)
         elseif ( $this->_values['auth_type'] == 'MYSQL' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['DBNAME']) ) {
               $this->_values['dbname'] = $auth_data_array['DBNAME'];
            }
            if ( !empty($auth_data_array['DBTABLE']) ) {
               $this->_values['dbtable'] = $auth_data_array['DBTABLE'];
            }
            if ( !empty($auth_data_array['DBCOLUMNUSERID']) ) {
               $this->_values['dbcolumnuserid'] = $auth_data_array['DBCOLUMNUSERID'];
            }
            if ( !empty($auth_data_array['DBCOLUMNPASSWD']) ) {
               $this->_values['dbcolumnpasswd'] = $auth_data_array['DBCOLUMNPASSWD'];
            }
            if ( !empty($auth_data_array['HOST']) ) {
               $this->_values['host'] = $auth_data_array['HOST'];
            }
            if ( !empty($auth_data_array['PORT']) ) {
               $this->_values['port'] = $auth_data_array['PORT'];
            }
            if ( !empty($auth_data_array['USER']) ) {
               $this->_values['userid'] = $auth_data_array['USER'];
            }
            if ( !empty($auth_data_array['PASSWORD']) ) {
               $this->_values['password'] = $auth_data_array['PASSWORD'];
            }
            if ( !empty($auth_data_array['ENCRYPTION']) ) {
               $this->_values['encryption'] = $auth_data_array['ENCRYPTION'];
            }
         }

         // LDAP
         elseif ( $this->_values['auth_type'] == 'LDAP' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['HOST']) ) {
               $this->_values['host'] = $auth_data_array['HOST'];
            }
            if ( !empty($auth_data_array['PORT']) ) {
               $this->_values['port'] = $auth_data_array['PORT'];
            }
            if ( !empty($auth_data_array['USER']) ) {
               $this->_values['userid'] = $auth_data_array['USER'];
            }
            if ( !empty($auth_data_array['PASSWORD']) ) {
               $this->_values['password'] = $auth_data_array['PASSWORD'];
            }
            if( !empty($auth_data_array['BASE'])) {
               $this->_values['base'] = $auth_data_array['BASE'];
            }
            if ( !empty($auth_data_array['ENCRYPTION']) ) {
               $this->_values['encryption'] = $auth_data_array['ENCRYPTION'];
            }
            if ( !empty($auth_data_array['DBCOLUMNUSERID']) ) {
               $this->_values['dbcolumnuserid'] = $auth_data_array['DBCOLUMNUSERID'];
            }
            if ( !empty($auth_data_array['DBSEARCHUSERID']) ) {
               $this->_values['dbsearchuserid'] = $auth_data_array['DBSEARCHUSERID'];
            }
            
            if ( !empty($auth_data_array['additional_server']) ) {
               $count = 0;
               foreach ( $auth_data_array['additional_server'] as $value_array ) {
            	   $count++;
            	   $this->_values['host'.$count] = $value_array['host'];
            	   $this->_values['port'.$count] = $value_array['port'];
               }
            }
            if ( !empty($auth_data_array['select_server']) ) {
               $this->_values['select_server'] = $auth_data_array['select_server'];
            }
         }
         
         // Typo3Web
         elseif ( $this->_values['auth_type'] == 'Typo3Web' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['HOST']) ) {
               $this->_values['host'] = $auth_data_array['HOST'];
            }
            #if ( !empty($auth_data_array['PORT']) ) {
            #   $this->_values['port'] = $auth_data_array['PORT'];
            #}
            #if ( !empty($auth_data_array['USER']) ) {
            #   $this->_values['userid'] = $auth_data_array['USER'];
            #}
            #if ( !empty($auth_data_array['PASSWORD']) ) {
            #   $this->_values['password'] = $auth_data_array['PASSWORD'];
            #}
            #if( !empty($auth_data_array['BASE'])) {
            #   $this->_values['base'] = $auth_data_array['BASE'];
            #}
            #if ( !empty($auth_data_array['ENCRYPTION']) ) {
            #   $this->_values['encryption'] = $auth_data_array['ENCRYPTION'];
            #}
            #if ( !empty($auth_data_array['DBCOLUMNUSERID']) ) {
            #   $this->_values['dbcolumnuserid'] = $auth_data_array['DBCOLUMNUSERID'];
            #}
         }
         
         // Shibboleth
         elseif ($this->_values['auth_type'] == 'Shibboleth'){
         	$auth_data_array = $this->_item->getAuthData();
         	if( !empty($auth_data_array['direct_login'])){
         		$this->_values['direct_login'] = $auth_data_array['direct_login'];
         	}
         	    	
//          	$this->_form->addCheckbox('direct_login', true, false, $translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_DIRECT_LOGIN'));
//          	$this->_form->addTextfield('session_initiator_url','test',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_SESSION_INITIATOR'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('session_logout_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_SESSION_LOGOUT'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('password_change_url','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_PASSWORD_CHANGE'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('username','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_USERNAME'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('firstname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_FIRSTNAME'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('lastname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_LASTNAME'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addTextfield('email','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_EMAIL'),'','',21,true,'','','','','','',false,'');
//          	$this->_form->addCheckbox('update_user_data', true, false, $translator->getMessage('CONFIGURATION_AUTHENTICATION_SHIBBOLETH_UPDATE_DATA'));
         }
        
      } else {
      	$current_context = $this->_environment->getCurrentContextItem();
      	
      	// Datenschutz
      	$this->_values['temporary_lock'] = $current_context->getTemporaryLock();
      	$this->_values['seconds_interval'] = $current_context->getLockTimeInterval();
      	$this->_values['temporary_minutes'] = $current_context->getLockTime();
      	$this->_values['try_until_lock'] = $current_context->getTryUntilLock();
      	
      	if( empty($this->_values['temporary_lock'])){
      		$this->_values['temporary_lock'] = 2;
      	}
      	if( empty($this->_values['seconds_interval'])){
      		$this->_values['seconds_interval'] = 0;
      	}
      	if( empty($this->_values['temporary_minutes'])) {
      		$this->_values['temporary_minutes'] = 0;
      	}
      	if( empty($this->_values['try_until_lock'])) {
      		$this->_values['try_until_lock'] = 0;
      	}
      	
         $this->_values['auth_source'] = -1;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      // check choosen auth source
      if (mb_strlen($this->_form_post['auth_source']) == 2 and $this->_form_post['auth_source'] != -1) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_ERROR');
         $this->_form->setFailure('auth_source','');
      }
      if ( mb_strlen($this->_form_post['auth_source']) == 2 and
           $this->_form_post['auth_source'] == -1 and
           isset($this->_form_post['option']) and
           isOption($this->_form_post['option'], $this->_translator->getMessage('COMMON_SAVE_BUTTON'))
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_ERROR');
         $this->_form->setFailure('auth_source','');
      }

      // CAS
      if ( !empty($this->_form_post['auth_type'])
           and mb_strtoupper($this->_form_post['auth_type'], 'UTF-8') == 'CAS'
           and !( strstr($this->_form_post['host'],'https://')
                  or strstr($this->_form_post['host'],'http://')
                )
         ) {
         $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST_ERROR');
         $this->_form->setFailure('host','');
      }
      
      // password_length
      if ( !empty($this->_form_post['password_length'])
      	  and !is_numeric($this->_form_post['password_length'])
      	) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PW_LENGTH'));
      	$this->_form->setFailure('password_length','');
      }
      
      // login locking
      if ( !empty($this->_form_post['try_until_lock'])
      	  and !is_numeric($this->_form_post['try_until_lock'])
      	) {
      	$this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_PASSWORD_LENGTH_ERROR',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_TRY_UNTIL_LOCK'));
      	$this->_form->setFailure('try_until_lock','');
      }

      // email regex
      if ( !empty($this->_form_post['email_regex'])
         and (@preg_match($this->_form_post['email_regex'], null) === false)
        ) {
           $this->_error_array[] = $this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_REGEX_WRONG_REGEX_ERROR',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_EMAIL_REGEX_WRONG_REGEX_ERROR'));
           $this->_form->setFailure('email_regex','');
      }
   }
}
?>
