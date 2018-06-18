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
class cs_account_merge_form extends cs_rubric_form {

   var $_show_form = true;

   private $_show_auth_source = true;

   var $_auth_source_list = NULL;

   var $_array_sources_allow_delete = array();

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
      if ( $this->_environment->inCommunityRoom()
           or $this->_environment->inProjectRoom() ) {
         $current_user = $this->_environment->getPortalUserItem();
      } else {
         $current_user = $this->_environment->getCurrentUserItem();
      }
      if ( isset($current_user)
           and $current_user->isRoot()
         ) {
         $this->_show_form = false;
      }

      // auth source
      $current_portal = $this->_environment->getCurrentPortalItem();
      #$this->_show_auth_source = $current_portal->showAuthAtLogin();
      # muss angezeigt werden, sonst koennen mit der aktuellen Programmierung
      # keine Acounts mit gleichen Kennungen aber unterschiedlichen Quellen
      # zusammengelegt werden
      $this->_show_auth_source = true;
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
      if ($this->_show_form) {
         $delete_source_number = count($this->_array_sources_allow_delete);
         $current_portal = $this->_environment->getCurrentPortalItem();
         $current_user = $this->_environment->getCurrentUserItem();

         // text and options
         // auth source
         $this->_form->addHeadline('title',$this->_translator->getMessage('ACCOUNT_MERGE'));
         if ( count($this->_auth_source_array) == 1 ) {
            $this->_form->addHidden('auth_source',$this->_auth_source_array[0]['value']);
         } elseif( $this->_show_auth_source ) {
            $this->_form->addSelect('auth_source', $this->_auth_source_array, $this->_default_auth_source_entry, $this->_translator->getMessage('USER_AUTH_SOURCE'), '', 1 , false, false, false, '', '', '', '', 12);
         }
         $this->_form->addTextfield('user_id','',$this->_translator->getMessage('COMMON_ACCOUNT'),'','',21,true);
         $this->_form->addPassword('password','',$this->_translator->getMessage('USER_PASSWORD'),'','',21,true);
         $this->_form->addButtonBar('option',$this->_translator->getMessage('ACCOUNT_MERGE_BUTTON'),$this->_translator->getMessage('COMMON_CANCEL_BUTTON'),'','','','',false,5.8,5.7);
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
      global $c_annonymous_account_array;
      $current_user = $this->_environment->getCurrentUserItem();
      if ( !empty($c_annonymous_account_array[mb_strtolower($current_user->getUserID(), 'UTF-8').'_'.$current_user->getAuthSource()])
           and $current_user->isOnlyReadUser()
         ) {
         $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_MERGE_ERROR_ANNONYMOUS',$current_user->getUserID());
      } elseif ( !empty($c_annonymous_account_array[mb_strtolower($this->_form_post['user_id'], 'UTF-8').'_'.$this->_form_post['auth_source']])
                 and !empty($c_read_account_array[mb_strtolower($this->_form_post['user_id'], 'UTF-8').'_'.$this->_form_post['auth_source']])
               ) {
         $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_MERGE_ERROR_ANNONYMOUS',$this->_form_post['user_id']);
      } elseif ( !empty($this->_form_post['user_id'])
           and !empty($this->_form_post['password'])
         ) {
         if ( $current_user->getUserID() == $this->_form_post['user_id']
              and ( empty($this->_form_post['auth_source'])
                    or ( $current_user->getAuthSource() == $this->_form_post['auth_source'] )
                  )
            ) {
            $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_MERGE_ERROR_USER_ID',$this->_form_post['user_id']);
            $this->_form->setFailure('user_id','');
         } elseif ( !empty($this->_form_post['auth_source']) ) {
            $authentication = $this->_environment->getAuthenticationObject();
            $auth_manager = $authentication->getAuthManager($this->_form_post['auth_source']);
            if ( !$auth_manager->checkAccount($this->_form_post['user_id'],$this->_form_post['password']) ) {
               $this->_error_array = array_merge($this->_error_array,$auth_manager->getErrorArray());
               $this->_form->setFailure('user_id','');
               $this->_form->setFailure('password','');
            }
         } else {
            $authentication = $this->_environment->getAuthenticationObject();
            if ( !$authentication->checkAccount($this->_form_post['user_id'],$this->_form_post['password']) ) {
               $this->_error_array = array_merge($this->_error_array,$authentication->getErrorArray());
               $this->_form->setFailure('user_id','');
               $this->_form->setFailure('password','');
            }
         }
      } else {
         $this->_error_array[] = $this->_translator->getMessage('ACCOUNT_MERGE_ERROR');
      }
   }
}
?>