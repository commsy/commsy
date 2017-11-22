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

include_once ('classes/cs_auth_mysql.php');

/** class for database connection to the database table "auth"
 * this class implements a database manager for the table "auth"
 * maybe this class should named cs_auth_mysql?
 */
class cs_auth_mysql_commsy extends cs_auth_mysql {

   /**
    * link - containing a class for connecting the (mysql) database
    */
   var $_db_connector;

   /**
    * object - containing the auth item of an account
    */
   var $_item = NULL;
   
   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor
     * the only available constructor, initial values for internal variables
     */
   function __construct() {
      $this->_is_implemented_array = array ();
      $this->_is_implemented_array[] = 'addAccount';
      $this->_is_implemented_array[] = 'changeUserId';
      $this->_is_implemented_array[] = 'deleteAccount';
      $this->_is_implemented_array[] = 'changeUserData';
      $this->_is_implemented_array[] = 'changePassword';
      
      global $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   function setContextID($value) {
      $this->_commsy_id = $value;
   }

   function setCommSyIDLimit($value) {
      $this->_commsy_id = (int) $value;
   }

   function setContextLimit($value) {
      $this->_commsy_id = (int) $value;
   }

   function setDBConnector($value) {
      $this->_db_connector = $value;
   }

   /** get error number
     * this method returns the number of an error, if an error occured
     *
     * @return integer error number
     */
   function getErrorNumber() {
      return $this->_db_connector->getErrno();
   }

   /** get error text
     * this method returns the text of an error, if an error occured
     *
     * @return string error number
     */
   function getErrorMessage() {
      return $this->_db_connector->getError();
   }

   /** get authentication item for a user (user_id), INTERNAL - do not use
     * this method returns a authentication item for a user
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return object cs_item an authentication item
     */
   function _get($user_id) {
      if ( !isset($this->_item)
           or $this->_item->getUserID() != $user_id
           or $this->_item->getPortalID() != $this->_commsy_id
         ) {
         $this->_item = NULL;
         $query = 'SELECT * FROM auth';
         $query .= ' WHERE user_id="' . encode(AS_DB,$user_id) . '"';
         $query .= ' AND commsy_id="' . encode(AS_DB,$this->_commsy_id) . '"';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting authentication from query: "' . $query . '"', E_USER_WARNING);
         }
         elseif ( !empty($result[0]) ) {
            $this->_item = $this->_buildItem($result[0]);
         }
      }
   }

   /** build a authentication item out of an (database) array - internal method, do not use
     * this method returns a authentication item out of a row form the database
     *
     * @param array array array with information about the authentication out of the database table "auth"
     *
     * @return object cs_item a authentication item
     */
   function _buildItem($array) {
      include_once ('classes/cs_auth_item.php');
      $item = new cs_auth_item();
      $item->setUserID($array['user_id']);
      $item->setPasswordMD5($array['password_md5']);
      $item->setFirstname($array['firstname']);
      $item->setLastname($array['lastname']);
      $item->setEMail($array['email']);
      $item->setLanguage($array['language']);
      $item->setPortalID($array['commsy_id']);
      return $item;
   }

   function getNewItem() {
      include_once ('classes/cs_auth_item.php');
      return new cs_auth_item();
   }

   /** create an authentication - internal, do not use -> use method save
     * this method creates an authentication
     *
     * @param object cs_item item the authentication item
     */
   function _create($item) {
      $query = 'INSERT INTO auth SET ';
      $query .= ' commsy_id="' . encode(AS_DB,$item->getPortalID()) . '",';
      $query .= ' user_id="' . encode(AS_DB, $item->getUserID()) . '",';
      $password = $item->getPassword();
      $password_md5 = $item->getPasswordMD5();
      if (!empty ($password)) {
         $query .= ' password_md5="' . md5($item->getPassword()) . '",';
      }
      elseif (!empty ($password_md5)) {
         $query .= ' password_md5="' . $item->getPasswordMD5() . '",';
      }
      $query .= ' firstname="' . encode(AS_DB, $item->getFirstname()) . '",';
      $query .= ' lastname="' . encode(AS_DB, $item->getLastname()) . '",';
      $query .= ' email="' . encode(AS_DB, $item->getEMail()) . '",';
      $query .= ' language="' . encode(AS_DB, $item->getLanguage()) . '"';
      $result = $this->_db_connector->performQuery($query);
      if (!isset ($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating authentication from query: "' . $query . '"', E_USER_ERROR);
      }
      unset ($item);
   }

   /** updates an authentication - internal, do not use -> use method save
     * this method updates an authentication
     *
     * @param object cs_item item the authentication item
     */
   function _update($item) {
      $option_array = array ();
      $password = $item->getPassword();
      $password_md5 = $item->getPasswordMD5();
      if (!empty ($password)) {
         $option_array[] = ' password_md5="' . md5($item->getPassword()) . '"';
      }
      elseif (!empty ($password_md5)) {
         $option_array[] = ' password_md5="' . $item->getPasswordMD5() . '"';
      }
      $firstname = $item->getFirstname();
      if (!empty ($firstname)) {
         $option_array[] = ' firstname="' . encode(AS_DB, $item->getFirstname()) . '"';
      }
      $lastname = $item->getLastname();
      if (!empty ($lastname)) {
         $option_array[] = ' lastname="' . encode(AS_DB, $item->getLastname()) . '"';
      }
      $email = $item->getEMail();
      if (!empty ($email)) {
         $option_array[] = ' email="' . encode(AS_DB, $item->getEMail()) . '"';
      }
      $language = $item->getLanguage();
      if (!empty ($language)) {
         $option_array[] = ' language="' . encode(AS_DB, $item->getLanguage()) . '"';
      }

      if (count($option_array) > 0) {
         $query = 'UPDATE auth SET ';
         $query .= implode(',', $option_array);
         $query .= ' WHERE user_id="' . encode(AS_DB, $item->getUserID()) . '"';
         $query .= ' AND commsy_id="' . encode(AS_DB,$item->getPortalID()) . '"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset ($result) or !$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems updating authentication from query: "' . $query . '"', E_USER_ERROR);
         }
      }
   }

   /** delete an authentication
     * this method deletes an authentication
     *
     * @param integer user_id id of the user (not item id)
     */
   function delete($user_id) {
      if ($this->isDeleteAccountEnabled()) {
         $query = 'DELETE FROM auth WHERE ' .
         'user_id="' . encode(AS_DB,$user_id) . '"' .
         ' AND commsy_id = "' . encode(AS_DB,$this->_commsy_id) . '"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset ($result) or !$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems deleting authentication from query: "' . $query . '"', E_USER_ERROR);
         }
      }
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
   function checkAccount($uid, $password) {      
      $retour = false;
      if ($this->exists($uid)) {
         $this->_get($uid);
         if ( md5($password) == $this->_item->getPasswordMD5()
              or md5(utf8_decode($password)) == $this->_item->getPasswordMD5()
            ) {
            $retour = true;
         } else {
            //$this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_PASSWORD_WRONG',$uid);
            //less specific error message to protect from brute force attacks
            $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
         }
      }
      return $retour;
   }

   /** change password
     * this method changes the user password in the mysql-database
     *
     * @param string user_id the user id of the user
     * @param string password the new password of the user
     */
   function changePassword($user_id = null, $password = null) {
      if ($user_id == 'root') {
         $this->_commsy_id = 99;
      }
      $query = 'UPDATE auth SET';
      $query .= ' password_md5="' . md5($password) . '"';
      $query .= ' WHERE user_id="' . encode(AS_DB, $user_id) . '"';
      $query .= ' AND commsy_id="' . encode(AS_DB,$this->_commsy_id) . '";';
      $result = $this->_db_connector->performQuery($query);
      if (!isset ($result) or !$result) {
         include_once('functions/error_functions.php');
         trigger_error('Problems at changing password: "' . $this->_dberror . '" from query: "' . $query . '"', E_USER_ERROR);
      }
   }

   /** get auth item form the auth_manager
     * this method returns an auth item form the auth_manager
     *
     * @return object auth_item of the user
     */
   function getItem($user_id) {
      $retour = $this->_item;
      if (empty ($retour) or $retour->getUserID() != $user_id or $retour->getPortalID() != $this->_commsy_id) {
         if (!empty ($user_id)) {
            $this->_get($user_id);
            $retour = $this->_item;
         }
      }
      return $retour;
   }

   function changeUserID($new = null, $old = null) {
      $update = "UPDATE auth SET ";
      $update .= " user_id = '" . encode(AS_DB, $new) . "'";
      $update .= " WHERE user_id = '" . encode(AS_DB, $old) . "'";
      $update .= " AND commsy_id='" . encode(AS_DB,$this->_commsy_id) . "'";
      $result = $this->_db_connector->performQuery($update);
      if ( !isset ($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems changing user id from query: "' . $update . '"', E_USER_WARNING);
         return false;
      } else {
         return true;
      }
   }
}
?>