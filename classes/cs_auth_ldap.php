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
class cs_auth_ldap extends cs_auth_manager {

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

   /** constructor: cs_auth_ldap
    * the only available constructor, initial values for internal variables
    *
    * @param string server url to ldap-server
    * @param string baseuser information about baseuser
    */
   function cs_auth_ldap () {
   }

   function setAuthSourceItem ($value) {
      parent::setAuthSourceItem($value);
      $auth_data_array = $value->getAuthData();
      $this->_server = $auth_data_array['HOST'];
      $this->_server_port = $auth_data_array['PORT'];
      $this->_baseuser = $auth_data_array['BASE'];
      if ( !empty($auth_data_array['USER']) ) {
         $this->_rootuser = $auth_data_array['USER'];
      }
      if ( !empty($auth_data_array['PASSWORD']) ) {
         $this->_rootuser_password = $auth_data_array['PASSWORD'];
      }
      if ( !empty($auth_data_array['ENCRYPTION']) ) {
         $this->_encryption = $auth_data_array['ENCRYPTION'];
      }
      if ( !empty($auth_data_array['DBCOLUMNUSERID']) ) {
         $this->_field_userid = $auth_data_array['DBCOLUMNUSERID'];
      }
   }

   /** set user with write access
    * this method sets the user with write access
    *
    * @param string value user id
    */
   function setUser ($value) {
      $this->_user = (string)$value;
   }

   /** set password for user with write access
    * this method sets the password for the user with write access
    *
    * @param string value password
    */
   function setPassword ($value) {
      $this->_password = (string)$value;
   }

   /** is the account granted ?
    * this method returns a boolean, if the account is granted in MySQL.
    *
    * @param string uid user id of the current user
    * @param string password the password of the current user
    *
    * @return boolean true, account is granted in MySQL
    *                 false, account is not granted in MySQL
    */
   function checkAccount ($uid, $password) {

      $granted = false;
      /** check if password is correct */
      if ( empty($password) || strlen($password) == 0 ) {
         $password = microtime();
      }
      $access = $this->_field_userid.'='.$uid.','.$this->_baseuser;
      $connect = @ldap_connect( $this->_server, $this->_server_port );
      if ( !$connect ) {
         include_once('functions/error_functions.php');
         trigger_error('could not connect to server '.$this->_server.', '.$this->_server_port,E_USER_WARNING);
      } else {
         @ldap_set_option($connect,LDAP_OPT_PROTOCOL_VERSION,3);
         $bind = @ldap_bind( $connect, $access, $this->encryptPassword($password) );
         if ( $bind ) {
            $granted = true;
         } elseif ( !empty($this->_rootuser)
                    and !empty($this->_rootuser_password)
                  ) {
            $suchfilter="($this->_field_userid=$uid)";
            if ( strstr($this->_rootuser,',')
                 and strstr($this->_rootuser,'=')
               ) {
               $access = $this->_rootuser;
            } else {
               $access = $this->_field_userid.'='.$this->_rootuser.','.$this->_baseuser;
            }
            $bind = @ldap_bind($connect, $access, $this->encryptPassword($this->_rootuser_password));
            if ( $bind ) {
               $search = @ldap_search($connect,$this->_baseuser,$suchfilter);
               $result = ldap_get_entries($connect,$search);
               $unbind = ldap_unbind($connect);
               if ( $result['count'] != 0 ) {
                  $access = $result[0]['dn'];
                  $connect = @ldap_connect( $this->_server, $this->_server_port );
                  @ldap_set_option($connect,LDAP_OPT_PROTOCOL_VERSION,3);
                  $bind = ldap_bind( $connect, $access, $this->encryptPassword($password) );
                  if ( $bind ) {
                     $granted = true;
                  } else {
                     $this->_error_array[] = getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
                  }
               } else {
                  $this->_error_array[] = getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
               }
            } else {
               $this->_error_array[] = getMessage('AUTH_ERROR_LDAP_ROOTUSER');
            }
         } else {
            $this->_error_array[] = getMessage('AUTH_ERROR_ACCOUNT_OR_PASSWORD',$uid);
         }
         @ldap_close( $connect );
      }
      return $granted;
   }

   /** exists an user_id ? - NOT IMPLEMENTED YET
    * this method returns a boolean whether the user_id exists in the ldap-database or not
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return boolean true, if authentication already exists
    *                 false, if authentication not exists -> new user
    */
  function exists ($user_id) {
     // not implemented yet
     include_once('functions/error_functions.php');
     trigger_error('The methode EXISTS [LDAP] is not implemented!',E_USER_ERROR);
     return true;
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
     trigger_error('The methode SAVE [LDAP] is not implemented!',E_USER_ERROR);
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
     trigger_error('The methode CHANGEPASSWORD [LDAP] is not implemented!',E_USER_ERROR);
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
        trigger_error('The methode DELETE [LDAP] is not implemented!',E_USER_ERROR);
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
     trigger_error('The methode GET [LDAP] is not implemented!',E_USER_ERROR);
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
      trigger_error('The methode getItem [LDAP] is not implemented!',E_USER_ERROR);
   }
}
?>