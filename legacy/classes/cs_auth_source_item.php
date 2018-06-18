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

/** upper class of the auth source item
 */
include_once('classes/cs_item.php');

/** class for a auth source
 * this class implements a auth source item
 */
class cs_auth_source_item extends cs_item {

   /** constructor: cs_auth_source_item
    * the only available constructor, initial values for internal variables
    */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = CS_AUTH_SOURCE_TYPE;
   }

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    */
   function _setItemData($data_array) {
      // not yet implemented
      $this->_data = $data_array;
   }

   /** get title of an auth source
    * this method returns the title of the auth source
    *
    * @return string title of an auth source
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set title of an auth source
    * this method sets the title of the auth source
    *
    * @param string value title of the auth source
    */
   function setTitle ($value) {
      $this->_setValue('title', $value);
   }

   function setSourceType ($value) {
      $this->_addExtra('SOURCE',$value);
   }

   function getSourceType () {
      $retour = 'MYSQL';
      $value = $this->_getExtra('SOURCE');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   function setAuthData ($value) {
      $this->_addExtra('DATA',$value);
   }

   function getAuthData () {
      $retour = array();
      $value = $this->_getExtra('DATA');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   function setAuthConfiguration ($value) {
      $this->_addExtra('CONFIGURATION',$value);
   }

   function getAuthConfiguration () {
      $retour = array();
      $value = $this->_getExtra('CONFIGURATION');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   function _getAuthConfigurationElement ($element) {
      $retour = '';
      $temp = $this->getAuthConfiguration();
      if ( !empty($element) ) {
         if ( !empty($temp[$element]) ) {
            $retour = $temp[$element];
         }
      }
      return $retour;
   }

   function _setAuthConfigurationElement ($element, $value) {
      $temp = $this->getAuthConfiguration();
      if ( !empty($element) ) {
         if ( !empty($value) ) {
            $temp[$element] = $value;
         } else {
            unset($temp[$element]);
         }
      }
      $this->setAuthConfiguration($temp);
   }

   function setAllowAddAccount () {
      $this->_setAuthConfigurationElement('ADD_ACCOUNT',1);
   }

   function unsetAllowAddAccount () {
      $this->_setAuthConfigurationElement('ADD_ACCOUNT',-1);
   }

   function _getAllowAddAccount () {
      return $this->_getAuthConfigurationElement('ADD_ACCOUNT');
   }

   function allowAddAccount () {
       $retour = false;
       $value = $this->_getAllowAddAccount();
       if ($value == 1) {
           $retour = true;
       }
       return $retour;
   }

   function setAllowAddAccountInvitation () {
      $this->_setAuthConfigurationElement('ADD_ACCOUNT_INVITATION',1);
   }

   function unsetAllowAddAccountInvitation () {
       $this->_setAuthConfigurationElement('ADD_ACCOUNT_INVITATION',-1);
   }

   function _getAllowAddAccountInvitation () {
      return $this->_getAuthConfigurationElement('ADD_ACCOUNT_INVITATION');
   }

   function allowAddAccountInvitation () {
       $retour = false;
       $value = $this->_getAllowAddAccountInvitation();
       if ($value == 1) {
           $retour = true;
       }
       return $retour;
   }

   function setAllowChangeUserID () {
      $this->_setAuthConfigurationElement('CHANGE_USERID',1);
   }

   function unsetAllowChangeUserID () {
      $this->_setAuthConfigurationElement('CHANGE_USERID',-1);
   }

   function _getAllowChangeUserID () {
      return $this->_getAuthConfigurationElement('CHANGE_USERID');
   }

   function allowChangeUserID () {
      $retour = false;
      $value = $this->_getAllowChangeUserID();
      if ($value == 1) {
          $retour = true;
      }
      return $retour;
   }

   function setAllowDeleteAccount () {
      $this->_setAuthConfigurationElement('DELETE_ACCOUNT',1);
   }

   function unsetAllowDeleteAccount () {
      $this->_setAuthConfigurationElement('DELETE_ACCOUNT',-1);
   }

   function _getAllowDeleteAccount () {
      return $this->_getAuthConfigurationElement('DELETE_ACCOUNT');
   }

   function allowDeleteAccount () {
      $retour = false;
      $value = $this->_getAllowDeleteAccount();
      if ($value == 1) {
          $retour = true;
      }
      return $retour;
   }

   function setAllowChangeUserData () {
      $this->_setAuthConfigurationElement('CHANGE_USERDATA',1);
   }

   function unsetAllowChangeUserData () {
      $this->_setAuthConfigurationElement('CHANGE_USERDATA',-1);
   }

   function _getAllowChangeUserData () {
      return $this->_getAuthConfigurationElement('CHANGE_USERDATA');
   }

   function allowChangeUserData () {
      $retour = false;
      $value = $this->_getAllowChangeUserData();
      if ($value == 1) {
          $retour = true;
      }
      return $retour;
   }

   function setAllowChangePassword () {
      $this->_setAuthConfigurationElement('CHANGE_PASSWORD',1);
   }

   function unsetAllowChangePassword () {
      $this->_setAuthConfigurationElement('CHANGE_PASSWORD',-1);
   }

   function _getAllowChangePassword () {
      return $this->_getAuthConfigurationElement('CHANGE_PASSWORD');
   }

   function allowChangePassword () {
      $retour = false;
      $value = $this->_getAllowChangePassword();
      if ($value == 1) {
          $retour = true;
      }
      return $retour;
   }

   function setShow () {
      $this->_addExtra('SHOW',1);
   }

   function unsetShow () {
      $this->_addExtra('SHOW',-1);
   }

   function _getShow () {
      return $this->_getExtra('SHOW');
   }

   function show () {
      $retour = false;
      $value = $this->_getShow();
      if ($value == 1) {
          $retour = true;
      }
      return $retour;
   }

   function setCommSyDefault () {
      $this->_addExtra('COMMSY_DEFAULT',1);
      $this->setSourceType('MYSQL');
   }

   function unsetCommSyDefault () {
      $this->_addExtra('COMMSY_DEFAULT',-1);
   }

   function _getCommSyDefault () {
      return $this->_getExtra('COMMSY_DEFAULT');
   }

   function isCommSyDefault () {
      $retour = false;
      $value = $this->_getCommSyDefault();
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   public function setPasswordChangeLink ($value) {
      $this->_addExtra('PASSWORD_CHANGE_LINK',$value);
   }

   function getPasswordChangeLink () {
      $retour = '';
      $value = $this->_getExtra('PASSWORD_CHANGE_LINK');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setContactEMail ($value) {
      $this->_addExtra('CONTACT_EMAIL',$value);
   }

   function getContactEMail () {
      $retour = '';
      $value = $this->_getExtra('CONTACT_EMAIL');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setContactFon ($value) {
      $this->_addExtra('CONTACT_FON',$value);
   }

   function getContactFon () {
      $retour = '';
      $value = $this->_getExtra('CONTACT_FON');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   function save() {
      $manager = $this->_environment->getManager($this->_type);
      $this->_save($manager);
   }

   function delete() {
      $manager = $this->_environment->getManager($this->_type);
      $this->_delete($manager);
   }

   public function getAuthConnection () {
      $authentication = $this->_environment->getAuthenticationObject();
      return $authentication->getAuthManagerByAuthSourceItem($this);
   }

   public function getPasswordLength() {
   	$retour = '';
      $value = $this->_getExtra('PASSWORD_LENGTH');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setPasswordLength($value) {
   	$this->_addExtra('PASSWORD_LENGTH',$value);
   }

   public function getPasswordSecureBigchar() {
   	$retour = '';
      $value = $this->_getExtra('PASSWORD_BIGCHAR');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setPasswordSecureBigchar($value) {
   	$this->_addExtra('PASSWORD_BIGCHAR',$value);
   }

   public function getPasswordSecureSpecialchar() {
   	$retour = '';
      $value = $this->_getExtra('PASSWORD_SPECIALCHAR');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setPasswordSecureSpecialchar($value) {
   	$this->_addExtra('PASSWORD_SPECIALCHAR',$value);
   }
   
   public function getPasswordSecureNumber() {
   	$retour = '';
   	$value = $this->_getExtra('PASSWORD_NUMBER');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   public function setPasswordSecureNumber($value) {
   	$this->_addExtra('PASSWORD_NUMBER',$value);
   }
   
   public function getPasswordSecureSmallchar() {
   	$retour = '';
   	$value = $this->_getExtra('PASSWORD_SMALLCHAR');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   public function setPasswordSecureSmallchar($value) {
   	$this->_addExtra('PASSWORD_SMALLCHAR',$value);
   }


   public function getPasswordSecureCheck() {
   	$retour = '';
      $value = $this->_getExtra('PASSWORD_SECURE_CHECK');
      if ( !empty($value) ) {
          $retour = $value;
      }
      return $retour;
   }

   public function setPasswordSecureCheck($value) {
   	$this->_addExtra('PASSWORD_SECURE_CHECK',$value);
   }

   public function isPasswordSecureActivated(){
   	if(($this->getPasswordSecureCheck() == 1)){
   	   	return true;
   	   } else {
   	   	return false;
   	   }
   }

    public function getEmailRegex() {
        $retour = '';
        $value = $this->_getExtra('EMAIL_REGEX');
        if ( !empty($value) ) {
            $retour = $value;
        }
        return $retour;
    }

    public function setEmailRegex($value) {
        $this->_addExtra('EMAIL_REGEX',$value);
    }


    public function getUserAllowedToCreateContext() {
        $retour = '';
        $value = $this->_getExtra('USER_IS_ALLOWED_TO_CREATE_CONTEXT');
        if ( !empty($value) ) {
            $retour = $value;
        }
        return $retour;
    }

    public function setUserIsAllowedToCreateContext($value) {
        $this->_addExtra('USER_IS_ALLOWED_TO_CREATE_CONTEXT',$value);
    }

    public function isUserAllowedToCreateContext(){
        if(($this->getUserAllowedToCreateContext() == -1)){
            return false;
        } else {
            return true;
        }
    }

   /**
    * Get Shibboleth direct login configuration
    * 
    * @return boolean
    */
   public function getShibbolethDirectLogin() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_DIRECT_LOGIN');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /**
    * Set Shibboleth direct login configuration
    */
   public function setShibbolethDirectLogin($value) {
   	$this->_addExtra('SHIB_DIRECT_LOGIN',$value);
   }
   
   /**
    * Get Shibboleth email configuration
    *
    * @return string
    */
   public function getShibbolethEmail() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_EMAIL');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
    
   /**
    * Set Shibboleth email configuration
    */
   public function setShibbolethEmail($value) {
   	$this->_addExtra('SHIB_EMAIL',$value);
   }
   
   /**
    * Get Shibboleth firstname configuration
    *
    * @return string
    */
   public function getShibbolethFirstname() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_FIRSTNAME');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /**
    * Set Shibboleth firstname configuration
    */
   public function setShibbolethFirstname($value) {
   	$this->_addExtra('SHIB_FIRSTNAME',$value);
   }
   
   /**
    * Get Shibboleth lastname configuration
    *
    * @return string
    */
   public function getShibbolethLastname() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_LASTNAME');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
    
   /**
    * Set Shibboleth lastname configuration
    */
   public function setShibbolethLastname($value) {
   	$this->_addExtra('SHIB_LASTNAME',$value);
   }
   
   /**
    * Get Shibboleth password change url configuration
    *
    * @return string
    */
   public function getShibbolethPasswordChange() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_PASSWORD_CHANGE');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /**
    * Set Shibboleth password change url configuration
    */
   public function setShibbolethPasswordChange($value) {
   	$this->_addExtra('SHIB_PASSWORD_CHANGE',$value);
   }
   
   /**
    * Get Shibboleth session initiator url configuration
    *
    * @return string
    */
   public function getShibbolethSessionInitiator() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_SESSION_INITIATOR');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
    
   /**
    * Set Shibboleth Session Initiator url configuration
    */
   public function setShibbolethSessionInitiator($value) {
   	$this->_addExtra('SHIB_SESSION_INITIATOR',$value);
   }
   
   /**
    * Get Shibboleth session logout url configuration
    *
    * @return string
    */
   public function getShibbolethSessionLogout() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_SESSION_LOGOUT');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /**
    * Set Shibboleth Session logout url configuration
    */
   public function setShibbolethSessionLogout($value) {
   	$this->_addExtra('SHIB_SESSION_LOGOUT',$value);
   }
   
   /**
    * Get Shibboleth update data configuration
    *
    * @return boolean
    */
   public function getShibbolethUpdateData() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_UPDATE_DATA');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
    
   /**
    * Set Shibboleth update data configuration
    */
   public function setShibbolethUpdateData($value) {
   	$this->_addExtra('SHIB_UPDATE_DATA',$value);
   }
   
   /**
    * Get Shibboleth username configuration
    *
    * @return string
    */
   public function getShibbolethUsername() {
   	$retour = '';
   	$value = $this->_getExtra('SHIB_USERNAME');
   	if ( !empty($value) ) {
   		$retour = $value;
   	}
   	return $retour;
   }
   
   /**
    * Set Shibboleth username configuration
    */
   public function setShibbolethUsername($value) {
   	$this->_addExtra('SHIB_USERNAME',$value);
   }
   
   public function getContextId () {
       return $this->_data['context_id'];
   }
}
?>