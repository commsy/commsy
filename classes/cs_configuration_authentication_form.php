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

  /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function cs_configuration_authentication_form ($environment) {
      $this->cs_rubric_form($environment);
   }


   /** init data for form, INTERNAL
    * this methods init the data for the form, for example groups
    */
   function _initForm () {

      // auth text choice
      $this->_array_auth_source[0]['text']  = '*'.getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT');
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
      $this->_array_auth_source[$counter]['text']  = getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_NEW');
      $this->_array_auth_source[$counter]['value'] = 'new';

      // auth type
      if ( isset($this->_form_post['auth_source'])
           and !empty($this->_form_post['auth_source'])
           and $this->_form_post['auth_source'] == 'new' ) {
         $this->_auth_type_array = array();
         $counter = 0;
         $this->_auth_type_array[$counter]['text']  = '*'.getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_CHOOSE_TEXT_TYPE');
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
         $this->_auth_type_array[$counter]['text'] = 'MySQL: Joomla!';
         $this->_auth_type_array[$counter]['value'] = 'Joomla';
         $counter++;
         $this->_auth_type_array[$counter]['text'] = 'MySQL: Typo3';
         $this->_auth_type_array[$counter]['value'] = 'Typo3';
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
            } else {
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
                               getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_SOURCE'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               getMessage('COMMON_CHOOSE_BUTTON'),
                               'option');
      $context_item = $this->_environment->getCurrentContextItem();

      $this->_form->addEmptyLine();
      if ( !isset($this->_auth_type_array) ) {
         $this->_form->addText('auth_type',$this->_translator->getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE'),$this->_auth_type);
         $this->_form->addHidden('auth_type_hidden',$this->_auth_type);
      } else {
         $this->_form->addSelect( 'auth_type',
                                  $this->_auth_type_array,
                                  '',
                                  getMessage('CONFIGURATION_AUTHENTICATION_FORM_CHOOSE_AUTH_TYPE'),
                                  '',
                                  '',
                                  '',
                                  '',
                                  true,
                                  getMessage('COMMON_CHOOSE_BUTTON'),
                                  'option');
      }
      $this->_form->addTextfield('title','',getMessage('COMMON_TITLE'),'',50,20,true,'','','','','','',$disabled);
      if ( $this->_disable_default ) {
         $this->_form->addHidden('disable_default','yes');
         $this->_form->addHidden('default',1);
      }
      if ( $this->_disable_show ) {
         $this->_form->addHidden('show',1);
      }
      $this->_form->addRadioGroup('default',$translator->getMessage('COMMON_DEFAULT'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_default);
      $this->_form->addRadioGroup('ims',$translator->getMessage('COMMON_IMS'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_ims);
      $this->_form->addRadioGroup('show',$translator->getMessage('COMMON_ACTIVATED'),'',$this->_yes_no_array,'','',true,'','',$disabled or $this->_disable_show);
      $this->_form->addEmptyLine();

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
      // Joomla
      elseif ( $this->_auth_type == 'Joomla' ) {
         $this->_form->addTextfield('dbname','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBNAME'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('dbtable','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBTABLE'),'','',21,true,'','','','','','',false,$translator->getMessage('CONFIGURATION_AUTHENTICATION_DBTABLE_HINT_JOOMLA'));
         $this->_form->addTextfield('host','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_HOST'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('port','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PORT'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('userid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('password','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW'),'','',21,true,'','','','','','',false,'');
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
         $this->_form->addTextfield('port','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PORT'),'','',21,true,'','','','','','',false,'');
         $this->_form->addTextfield('dbcolumnuserid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_DBCOLUMNUSERID'),'','',21,true,'','','','','','',false,'');
         $this->_form->combine();
         $this->_form->addText('dbcolumnuserid_text','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_DBCOLUMNUSERID_DESC'));
         $this->_form->addTextfield('base','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_SUBTREE'),'','',21,true,'','','','','','',false,'');
         $this->_form->addText('choice','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_SUBTREE_OR'));
         $this->_form->addTextfield('userid','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_USER'),'','',21,false,'','','','','','',false,'');
         $this->_form->addPassword('password','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_PW'),'','',22,false,'','','','','','',false,'');
         $this->_form->addRadioGroup('encryption',$translator->getMessage('CONFIGURATION_AUTHENTICATION_TYPO3_ENCRYPTION'),'',$this->_encryption_array,'',true,true,'','','');
         $this->_form->combine();
         $this->_form->addText('encryption_text','',$translator->getMessage('CONFIGURATION_AUTHENTICATION_LDAP_ENCRYPTION'));

         $this->_form->addEmptyLine();
      }

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
         $this->_form->addRadioGroup('addAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_ADD_ACCOUNT_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
      }
      if ( $this->_disable_delete_user ) {
         $this->_form->addhidden('deleteAccount',2);
         $this->_form->addText('textdeleteAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DELETE_ACCOUNT_TITLE'),$translator->getMessage('CONFIGURATION_AUTHENTICATION_NOT_IMPLEMENTED'));
      } else {
         $this->_form->addRadioGroup('deleteAccount',$translator->getMessage('CONFIGURATION_AUTHENTICATION_DELETE_ACCOUNT_TITLE'),'',$this->_yes_no_array,'','',true,'','',$disabled);
      }

      // specific options
      if ( !$this->_commsy_default ) {
         $this->_form->addEmptyLine();
      } else {
         $this->_form->addHidden('commsy_default','yes');
      }

      // buttons
      $this->_form->addButtonBar('option',$translator->getMessage('PREFERENCES_SAVE_BUTTON'),'','','','','',$disabled);
   }

   /** loads the selected and given values to the form
    * this methods loads the selected and given values to the form from the material item or the form_post data
    */
   function _prepareValues () {
      $this->_values = array();
      if ( !empty($this->_form_post) ) {
         $this->_values = $this->_form_post;
         if ( strlen($this->_values['auth_source']) == 2 and $this->_values['auth_source'] != -1) {
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
              and strtolower($this->_values['auth_type']) == 'typo3'
           ) {
            $this->_values['encryption'] = 'md5';
         }
         if ( !isset($this->_values['port'])
              and isset($this->_values['auth_type'])
            ) {
            if ( strtolower($this->_values['auth_type']) == 'typo3' ) {
               $this->_values['port'] = '3306';
            } elseif ( strtolower($this->_values['auth_type']) == 'ldap' ) {
               $this->_values['port'] = '389';
            }
         }

      } elseif ( !empty($this->_item) ) {
         $this->_values['auth_source'] = $this->_item->getItemID();
         $this->_values['title'] = $this->_item->getTitle();
         $current_context = $this->_environment->getCurrentContextItem();
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
            $this->_values['addAccount'] = 2;
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

         // Joomla!
         elseif ( $this->_values['auth_type'] == 'Joomla' ) {
            $auth_data_array = $this->_item->getAuthData();
            if ( !empty($auth_data_array['DBNAME']) ) {
               $this->_values['dbname'] = $auth_data_array['DBNAME'];
            }
            if ( !empty($auth_data_array['DBTABLE']) ) {
               $this->_values['dbtable'] = $auth_data_array['DBTABLE'];
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
         }
      } else {
         $this->_values['auth_source'] = -1;
      }
   }

   /** specific check the values of the form
    * this methods check the entered values
    */
   function _checkValues () {
      // check choosen auth source
      if (strlen($this->_form_post['auth_source']) == 2 and $this->_form_post['auth_source'] != -1) {
         $this->_error_array[] = getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_ERROR');
         $this->_form->setFailure('auth_source','');
      }
      if ( strlen($this->_form_post['auth_source']) == 2 and
           $this->_form_post['auth_source'] == -1 and
           isset($this->_form_post['option']) and
           isOption($this->_form_post['option'], getMessage('COMMON_SAVE_BUTTON'))
         ) {
         $this->_error_array[] = getMessage('CONFIGURATION_AUTHENTICATION_CHOICE_ERROR');
         $this->_form->setFailure('auth_source','');
      }

      // CAS
      if ( !empty($this->_form_post['auth_type'])
           and strtoupper($this->_form_post['auth_type']) == 'CAS'
           and !( strstr($this->_form_post['host'],'https://')
                  or strstr($this->_form_post['host'],'http://')
                )
         ) {
         $this->_error_array[] = getMessage('CONFIGURATION_AUTHENTICATION_HOST_ERROR');
         $this->_form->setFailure('host','');
      }
   }
}
?>