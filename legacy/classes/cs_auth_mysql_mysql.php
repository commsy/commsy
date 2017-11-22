<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** text functions are needed for create and update sql statements
 */
include_once('functions/text_functions.php');

include_once('classes/cs_auth_manager.php');

/** class for database connection to the database table "auth"
 * this class implements a database manager for the table "auth"
 * maybe this class should named cs_auth_mysql?
 */
class cs_auth_mysql_mysql extends cs_auth_manager {

  /**
   * link - containing a link to the (mysql) database
   */
  var $_dblink;

  /**
   * integer - containing the error number if an error occured
   */
  var $_dberrno;

  /**
   * string - containing the error text if an error occured
   */
  var $_dberror;

  /**
   * object - containing the auth item of an account
   */
   var $_item = NULL;

  /**
   * string - containing MySQL database table
   */
   var $_dbtable;

  /**
   * string - containing MySQL database table field containing User-ID
   */
   var $_field_userid;

  /**
   * string - containing MySQL database table field containing password
   */
   var $_field_password;
   
   /*
    * Translation Object
    */
   private $_translator = null;

  /** constructor
    * the only available constructor, initial values for internal variables
    */
  function __construct() {
     $this->_is_implemented_array = array();
#     $this->_dbtable = 'fe_users';
#     $this->_field_userid = 'username';
#     $this->_field_password = 'password';
#     $this->_is_implemented_array[] = 'addAccount';
#     $this->_is_implemented_array[] = 'changeUserId';
#     $this->_is_implemented_array[] = 'deleteAccount';
#     $this->_is_implemented_array[] = 'changeUserData';
#     $this->_is_implemented_array[] = 'changePassword';

      global $environment;
      $this->_translator = $environment->getTranslationObject();
  }

  function setAuthSourceItem ($value) {
     parent::setAuthSourceItem($value);
  }

  function setDBLink ($value) {
     $this->_dblink = $value;
  }

  function _getDBLink () {
     if ( !isset($this->_dblink) ) {
        $auth_data_array = $this->_auth_data_array;
        $this->_dblink = mysql_connect($auth_data_array['HOST'],$auth_data_array['USER'],$auth_data_array['PASSWORD'],true);
        mysql_select_db($auth_data_array['DBNAME'], $this->_dblink);
        unset($auth_data_array);
     }
     return $this->_dblink;
  }

  /** reset limits
    * reset limits of this class: room limit, delete limit
    *
    * @author CommSy Development Group
    */
  function resetLimits () {}

  /** get error number
    * this method returns the number of an error, if an error occured
    *
    * @return integer error number
    *
    * @author CommSy Development Group
    */
  function getErrorNumber () {
     return $this->_dberrno;
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

  /** get authentication item for a user (user_id), INTERNAL - do not use
    * this method returns a authentication item for a user
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return object cs_item an authentication item
    */
  function _get ($user_id) {
     if (!isset($this->_item) or $this->_item->getUserID() != $user_id) {
        $this->_item = NULL;
        $query = 'SELECT * FROM '.$this->_auth_data_array['DBTABLE'];
        $query .= ' WHERE '.$this->_auth_data_array['DBCOLUMNUSERID'].'="'.encode(AS_DB,$user_id).'"';
        $db_link = $this->_getDBLink();
        $result = mysql_query($query,$db_link);
        $this->_dberrno = mysql_errno($db_link);
        $this->_dberror = mysql_error($db_link);
        if (!$result) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting authentication: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
        } else {
           $this->_item = $this->_buildItem(mysql_fetch_assoc($result));
        }
     }
  }

  /** build a authentication item out of an (database) array - internal method, do not use
    * this method returns a authentication item out of a row form the database
    *
    * @param array array array with information about the authentication out of the database table "auth"
    *
    * @return object cs_item a authentication item
    *
    * @author CommSy Development Group
    */
  function _buildItem ($array) {
     $item = new cs_auth_item();
     $item->setUserID($array[$this->_auth_data_array['DBCOLUMNUSERID']]);
     if ( !empty($this->_auth_data_array['ENCRYPTION']) and ($this->_auth_data_array['ENCRYPTION'] == 'md5') ) {
        $item->setPasswordMD5($array[$this->_auth_data_array['DBCOLUMNPASSWD']]);
     } else {
        $item->setPassword($array[$this->_auth_data_array['DBCOLUMNPASSWD']]);
     }
     return $item;
  }

  /** exists an authentication ?
    * this method returns a boolean whether the authentication exists in the database or not
    *
    * @param integer user_id id of the user (not item id)
    *
    * @return boolean true, if authentication already exists
    *                 false, if authentication not exists -> new user
    */
  function exists ($user_id) {
     $exists = false;
     $user_id_old = $user_id;
     $item = '';
     $this->_get($user_id);
     $user_id = $this->_item->getUserID();
     if (!empty($user_id)) {
        $exists = true;
     } else {
        //$this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_NOT_EXIST',$user_id_old);
        //less specific error message to protect from brute force attacks
        $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
     }
     return $exists;
  }

  /** is the account granted ?
    * this method returns a boolean, if the account is granted in MySQL.
    *
    * @param string uid user id of the current user
    * @param string password the password of the current user
    *
    * @return boolean true, account is granted in MySQL
    *                 false, account is not granted in MySQL
    *
    * @author CommSy Development Group
    */
  function checkAccount ($uid, $password) {
     $retour = false;
     if ($this->exists($uid)) {
        $this->_get($uid);
        if ( !empty($this->_auth_data_array['ENCRYPTION']) and ($this->_auth_data_array['ENCRYPTION'] == 'md5') ) {
           $checkpass = md5($password);
           if ($checkpass == $this->_item->getPasswordMD5()) {
              $retour = true;
           } else {
             $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
           }
        } else {
           $checkpass = $password;
           if ($checkpass == $this->_item->getPassword()) {
              $retour = true;
           } else {
              $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
           }
        }

     }
     return $retour;
  }

  /** get auth item form the auth_manager
    * this method returns an auth item form the auth_manager
    *
    * @return object auth_item of the user
    *
    * @author CommSy Development Group
    */
   function getItem ($user_id) {
      $retour = $this->_item;
      if (empty($retour) or $retour->getUserID() != $user_id ) {
         if (!empty($user_id)) {
            $this->_get($user_id);
            $retour = $this->_item;
         }
      }
      return $retour;
   }
}
?>