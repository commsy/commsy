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

/** cs_auth_item is needed to create auth items
 */
include_once('classes/cs_auth_item.php');
include_once('classes/cs_auth_manager.php');

/** class for database connection to a LDAP-server
 * this class implements a manager for LDAP authentication
 */
class cs_auth_typo3 extends cs_auth_manager {

   /**
    * strong - containing to the url of the ldap-server
    */
   var $_server;

   /**
    * integer - containing the port of the ldap-server
    */
   var $_server_port;

   /**
    * string - containing a string with baseuser information
    */
   var $_baseuser;

   /**
    * string - containing a string with baseuser information
    */
   var $_rootuser;

   /**
    * string - containing a string with password information
    */
   var $_rootuser_password;

  /**
   * string - containing LDAP field containing User-ID
   */
   var $_field_userid = 'uid';

   /**
    * string - containing a uid from a user with write access
    */
   var $_user;

   /**
    * string - containing the password of the user above
    */
   var $_password;

   /**
   * string - containing the error text if an error occured
   */
   var $_dberror;

   /**
   * boolean - containing the a flag if accounts can be deleted
   */
   var $_with_delete_accounts = false;

   /**
   * array - containing the error messages
   */
   var $_error_array = array();

   private $_user_dn   = NULL;
   private $_user_data = NULL;

   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor: cs_auth_ldap
    * the only available constructor, initial values for internal variables
    *
    * @param string server url to ldap-server
    * @param string baseuser information about baseuser
    */
   function __construct() {
      global $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   function setAuthSourceItem ($value) {
      parent::setAuthSourceItem($value);
      $auth_data_array = $value->getAuthData();
      $this->_server = $auth_data_array['HOST'];
   }


   /** exists an user_id ? - NOT IMPLEMENTED YET
    * this method returns a boolean whether the user_id exists in the commsy-database or not
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return boolean true, if authentication already exists
    *                 false, if authentication not exists -> new user
    */
  function exists ($user_id) {
     global $environment;
     $user_manager = $environment->getUserManager();
     $user_manager->setPortalIDLimit($environment->getCurrentPortalID());
     $user_manager->setUserIDLimit($user_id);
     $user_manager->select();
     $user_list = $user_manager->get();
     $temp_user = $user_list->getFirst();
     if(!isset($temp_user) or empty($temp_user)){
        return false;
     } else {
        return true;
     }
  }

  /** get error text
    * this method returns the text of an error, if an error occured
    *
    * @return string error number
    *
    * @author CommSy Development Group
    */
  function getErrorMessage () {
     return $this->_dberror;
  }

  /** save an authentication - NOT IMPLEMENTED YET
    * save an authentication into the ldap-database
    *
    * @param object cs_item item the authentication item
    */
  function save ($item) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode SAVE [TYPO3WEB] is not implemented!',E_USER_ERROR);
  }

  /** change password - NOT IMPLEMENTED YET
    * this method changes the user password in the ldap-database
    *
    * @param string user_id the user id of the user
    * @param string password the new password of the user
    */
  function changePassword ($user_id, $password) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode CHANGEPASSWORD [TYPO3WEB] is not implemented!',E_USER_ERROR);
  }

  /** delete an LDAP account - NOT IMPLEMENTED YET
    * this method deletes an LDAP account in the ldap-database
    *
    * @param string user_id the user id of the user
    */
  function delete ($user_id) {
     if ($this->_with_delete_accounts) {
        // not implemented yet
        include_once('functions/error_functions.php');
        trigger_error('The methode DELETE [TYPO3WEB] is not implemented!',E_USER_ERROR);
     }
  }

  /** get authentication item for a user (user_id) - NOT IMPLEMENTED YET
    * this method returns a authentication item for a user
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return object cs_item an authentication item
    */
  function get ($user_id) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode GET [TYPO3WEB] is not implemented!',E_USER_ERROR);
  }

  /** get commsy error text
    * this method returns the text of an error in commsy style, if an error occured
    *
    * @return string error number
    */
   function getErrorArray () {
      return $this->_error_array;
   }

   /** get auth item form the auth_manager - NOT IMPLEMENTED YET
    * this method returns an auth item form the auth_manager
    *
    * @return object auth_item of the user
    */
   function getItem () {
      #return $this->_item;
      include_once('functions/error_functions.php');
      trigger_error('The methode getItem [TYPO3WEB] is not implemented!',E_USER_ERROR);
   }

   /** get user information out of the auth source
    * this method returns an array of informations form the user
    * in the auth source
    *
    * @return array data of the user
    */
   public function get_data_for_new_account ($uid, $password) {
      $user_data_array = array();
      $retour = array( 'firstname' => '',
                       'lastname' => '',
                       'email' => '');
      return $retour;
   }

   public function validateSessionID ( $ses_id ) {
      $retour = array();
      $url = $this->_server.'&cmd=userInfo&ses_id='.$ses_id;
      
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      
      $xml = curl_exec($curl);
      curl_close($curl);
      
      if ( !empty($xml) ) {
         if ( strstr($xml,'sessionId') ) {
            $pos1 = mb_strpos($xml,'<userName>');
            $user_id = mb_substr($xml,$pos1+mb_strlen('<userName>'));
            $retour['user_id'] = trim(mb_substr($user_id,0,mb_strpos($user_id,'</')));
            if ( empty($retour['user_id']) ) {
               include_once('functions/error_functions.php');
               trigger_error('validateSession: can not get user_id from given information.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
            }
            
            $pos1 = mb_strpos($xml,'<firstName>');
            $firstname = mb_substr($xml,$pos1+mb_strlen('<firstName>'));
            $retour['firstname'] = trim(mb_substr($firstname,0,mb_strpos($firstname,'</')));
            if ( empty($retour['firstname']) ) {
               include_once('functions/error_functions.php');
               trigger_error('validateSession: can not get firstname from given information.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
            }
            
            $pos1 = mb_strpos($xml,'<lastName>');
            $lastname = mb_substr($xml,$pos1+mb_strlen('<lastName>'));
            $retour['lastname'] = trim(mb_substr($lastname,0,mb_strpos($lastname,'</')));
            if ( empty($retour['lastname']) ) {
               include_once('functions/error_functions.php');
               trigger_error('validateSession: can not get lastname from given information.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
            }
            
            $pos1 = mb_strpos($xml,'<eMail>');
            $email = mb_substr($xml,$pos1+mb_strlen('<eMail>'));
            $retour['email'] = trim(mb_substr($email,0,mb_strpos($email,'</')));
            if ( empty($retour['email']) ) {
               #include_once('functions/error_functions.php');
               #trigger_error('validateSession: can not get email from given information.'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
            }
         }
      } else {
         include_once('functions/error_functions.php');
         trigger_error('validateSession: can not get xml from typo3 server to authenticate ses_id ['.$ses_id.'].'.BRLF.'URL: '.$url.BRLF,E_USER_ERROR);
      }
      return $retour;
   }
   
   function checkAccount($uid, $password){
      $granted = false;
      $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_TYPO3WEB_NOT_YET_IMPLEMENTED');
      return $granted;
   }
   
   public function sendSessionToTypo3 ($ses_id, $sid) {
      $retour = array();
      $url = $this->_server.'&cmd=setSessionId&ses_id='.$ses_id.'&cses_id='.$sid;
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HEADER, 0);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_exec($curl);
      curl_close($curl);
   }
}
?>