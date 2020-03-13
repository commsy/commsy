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


/** upper class for authentication manager of commsy
 * this class implements an upper class of authentication manager in commsy
 *
 * @author CommSy Development Group
 */
class cs_auth_manager {

   /**
    * string - containing the encryption of the user password in LDAP
    */
   var $_encryption = 'none';

   /**
    * array - containing the error messages
    */
   public $_error_array = array();

   /**
   * Array containing all names of implemented functions. E.g. 'addAccount'
   */
   public $_is_implemented_array = array();

   /**
   * Array containing function names, if a function is enabled (-> can be used)
   */
   private $_is_enabled_array = array();

   public $_auth_source_item = NULL;

   public $_auth_data_array = array();

   public function setAuthSourceItem ($item) {
      $this->_auth_source_item = $item;
      $this->_setAllowedFunctionArray($item);
      $this->_auth_data_array = $item->getAuthData();
   }

   private function _setAllowedFunctionArray ($item) {
      if ( $item->allowAddAccount() ) {
         $this->_is_enabled_array[] = 'addAccount';
      }
      if ( $item->allowChangeUserID() ) {
         $this->_is_enabled_array[] = 'changeUserId';
      }
      if ( $item->allowDeleteAccount() ) {
         $this->_is_enabled_array[] = 'deleteAccount';
      }
      if ( $item->allowChangeUserData() ) {
         $this->_is_enabled_array[] = 'changeUserData';
      }
      if ( $item->allowChangePassword() ) {
         $this->_is_enabled_array[] = 'changePassword';
      }
   }


   //TBD: Argumente?
   public function addAcount () {
      include_once('functions/error_functions.php');
      trigger_error('Must be overwritten in subclass if used!',E_USER_ERROR);
   }

   //TBD: Argumente?
   public function changeUserId () {
      include_once('functions/error_functions.php');
      trigger_error('Must be overwritten in subclass if used!',E_USER_ERROR);
   }

   //TBD: Argumente
   public function deleteAccount () {
      include_once('functions/error_functions.php');
      trigger_error('Must be overwritten in subclass if used!',E_USER_ERROR);
   }

   //TBD: Argumente
   public function changeUserData () {
      include_once('functions/error_functions.php');
      trigger_error('Must be overwritten in subclass if used!',E_USER_ERROR);
   }

   //TBD: ARGUMENTE
   public function changePassword ($user_id, $password) {
      include_once('functions/error_functions.php');
      trigger_error('Must be overwritten in subclass if used!',E_USER_ERROR);
   }

   public function isFunctionImplemented ($function_name) {
      if ( isset($this->_is_implemented_array)
           and in_array($function_name,$this->_is_implemented_array)) {
         return true;
      } else {
         return false;
      }
   }

   public function isFunctionEnabled ($function_name) {
     if (in_array($function_name,$this->_is_enabled_array)) {
        return true;
     } else {
        return false;
     }
   }

   public function enableAll () {
      $this->_is_enabled_array[] = 'addAccount';
      $this->_is_enabled_array[] = 'changeUserId';
      $this->_is_enabled_array[] = 'deleteAccount';
      $this->_is_enabled_array[] = 'changeUserData';
      $this->_is_enabled_array[] = 'changePassword';
   }

   //check for enabled functions
   public function isAddAccountEnabled() {
      return $this->isFunctionEnabled('addAccount');
   }

   public function isChangeUserIdEnabled() {
      return $this->isFunctionEnabled('changeUserId');
   }

   public function isDeleteAccountEnabled() {
      return $this->isFunctionEnabled('deleteAccount');
   }

   public function isChangeUserDataEnabled() {
      return $this->isFunctionEnabled('changeUserData');
   }

   public function isChangePasswordEnabled() {
      return $this->isFunctionEnabled('changePassword');
   }

   //Check for function implementations
   public function isAddAccountImplemented() {
      return $this->isFunctionImplemented('addAccount');
   }

   public function isChangeUserIdImplemented() {
      return $this->isFunctionImplemented('changeUserId');
   }

   public function isDeleteAccountImplemented() {
      return $this->isFunctionImplemented('deleteAccount');
   }

   public function isChangeUserDataImplemented() {
      return $this->isFunctionImplemented('changeUserData');
   }

   public function isChangePasswordImplemented() {
      return $this->isFunctionImplemented('changePassword');
   }

   public function getAuthSourceItemID () {
      $retour = NULL;
      if ( isset($this->_auth_source_item) and !empty($this->_auth_source_item) ) {
         $retour = $this->_auth_source_item->getItemID();
      }
      return $retour;
   }

   /** get commsy error text
    * this method returns the text of an error in commsy style, if an error occured
    *
    * @return string error number
    */
   public function getErrorArray () {
      return $this->_error_array;
   }

   public function getSourceType () {
      if ($this->_auth_source_item) {
         $retour = $this->_auth_source_item->getSourceType();
      } else {
         if ( get_class($this) == 'cs_auth_mysql' ) {
            $retour = 'MYSQL';
         } elseif ( get_class($this) == 'cs_auth_ldap' ) {
            $retour = 'LDAP';
         } elseif ( get_class($this) == 'cs_auth_mysql_typo3' ) {
            $retour = 'TYPO3';
         } elseif ( get_class($this) == 'cs_auth_typo3' ) {
            $retour = 'TYPO3WEB';
         } elseif ( get_class($this) == 'cs_auth_shibboleth' ) {
         	$retour = 'SHIBBOLETH';
         }
      }
      return $retour;
   }

   public function isCommSyDefault () {
      return $this->_auth_source_item->isCommSyDefault();
   }

   public function encryptPassword($password)
   {
       if ($this->_encryption === 'md5') {
           return md5($password);
       }

       return $password;
   }

   public function get_data_for_new_account($uid, $password){
      return null;
   }
}