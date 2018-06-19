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

/** class for authentication items
 * this class implements authentication items
 */
class cs_auth_item {

   /**
    * string - containing the type of the item
    */
   var $_type = 'auth';

   /**
    * string - containing the user id (account name - not item id)
    */
   var $_user_id = NULL;

   /**
    * string - containing the password
    */
   var $_password = NULL;

   /**
    * string - containing the database password in md5
    */
   var $_password_md5 = NULL;

   /**
    * string - containing the firstname
    */
   var $_firstname = NULL;

   /**
    * string - containing the lastname
    */
   var $_lastname = NULL;

   /**
    * string - containing the email
    */
   var $_email = NULL;

   /**
    * string - containing the explanation if the user
    */
   var $_explanation = NULL;

   /**
    * string - containing the language selected by the user
    */
   var $_language = NULL;

   /**
    * integer - containing the portal-id
    */
   var $_portal_id = NULL;

   /**
    * integer - containing the item-id of the auth source
    */
   var $_auth_source_id = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    */
   function __construct() {
   }

   /** is the type of the item = $type ?
    * this method returns a boolean expressing if type of the item is $type or not
    *
    * @param string type string to compare with type of the item (_type)
    *
    * @return boolean   true - type of this item is $type
    *                   false - type of this item is not $type
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** get user id
    * this method returns the user id
    *
    * @return string user id
    */
   function getUserID () {
      return $this->_user_id;
   }

   /** set user id
    * this method sets the user id
    *
    * @param string value user id
    */
   function setUserID ($value) {
      $this->_user_id = (string)$value;
   }

   /** get commsy id
    * this method returns the commsy id
    *
    * @return integer commsy id
    */
   function getPortalID () {
      return $this->_portal_id;
   }

   /** set commsy id
    * this method sets the commsy id
    *
    * @param integer value commsy id
    */
   function setPortalID ($value) {
      $this->_portal_id = (int)$value;
   }

   /** get auth source id
    * this method returns the auth source id
    *
    * @return integer auth source id
    */
   function getAuthSourceID () {
      return $this->_auth_source_id;
   }

   /** set auth source id
    * this method sets the auth source id
    *
    * @param integer value auth source id
    */
   function setAuthSourceID ($value) {
      $this->_auth_source_id = (int)$value;
   }

   /** get password
    * this method returns the password
    *
    * @return string password
    */
   function getPassword () {
      return $this->_password;
   }

   /** set password
    * this method sets the password
    *
    * @param string value password
    */
   function setPassword ($value) {
      $this->_password = (string)$value;
   }

   /** get database password in md5
    * this method returns the password stored in the database table "auth" in md5
    *
    * @return string data base password in md5
    */
   function getPasswordMD5 () {
      return $this->_password_md5;
   }

   /** set database password in md5
    * this method sets the database password in md5
    *
    * @param string value database password in md5
    */
   function setPasswordMD5 ($value) {
      $this->_password_md5 = (string)$value;
   }

   /** get firstname
    * this method returns the firstname
    *
    * @return string firstname
    */
   function getFirstname () {
      return $this->_firstname;
   }

   /** set firstname
    * this method sets the firstname
    *
    * @param string value firstname
    */
   function setFirstname ($value) {
      $this->_firstname = (string)$value;
   }

   /** get lastname
    * this method returns the lastname
    *
    * @return string lastname
    */
   function getLastname () {
      return $this->_lastname;
   }

   /** set lastname
    * this method sets the lastname
    *
    * @param string value lastname
    */
   function setLastname ($value) {
      $this->_lastname = (string)$value;
   }

   /** get fullname of the auth item
    * this method returns the fullname (firstname + lastname) of the auth item
    *
    * @return string fullname of the auth item
    */
   function getFullName () {
      return ltrim($this->getFirstname().' '.$this->getLastname());
   }

   /** get email
    * this method returns the email address
    *
    * @return string email address
    */
   function getEmail () {
      return $this->_email;
   }

   /** set email
    * this method sets the email address
    *
    * @param string value email address
    */
   function setEmail ($value) {
      $this->_email = (string)$value;
   }

   /** get explanation
    * this method returns the users explanation
    *
    * @return string explanation
    */
   function getExplanation () {
      return $this->_explanation;
   }

   /** set explanation
    * this method sets the users explanation
    *
    * @param string value explanation
    */
   function setExplanation ($value) {
      $this->_explanation = (string)$value;
   }

   /** get language
    * this method returns the users language
    *
    * @return string language
    */
   function getLanguage () {
      return $this->_language;
   }

   /** set language
    * this method sets the users language
    *
    * @param string value language
    */
   function setLanguage ($value) {
      $this->_language = (string)$value;
   }
}
?>