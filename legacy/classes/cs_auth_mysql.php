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

/** text functions are needed for create and update sql statements
 */
include_once ('functions/text_functions.php');

include_once ('classes/cs_auth_manager.php');

/** class for database connection to the database table "auth"
 * this class implements a database manager for the table "auth"
 * maybe this class should named cs_auth_mysql?
 */
class cs_auth_mysql extends cs_auth_manager {

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
      global $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   function setAuthSourceItem ($value) {
      parent::setAuthSourceItem($value);
   }

   /** reset limits
     * reset limits of this class: room limit, delete limit
     */
   function resetLimits() {
   }

   function getNewItem() {
      include_once('classes/cs_auth_item.php');
      return new cs_auth_item();
   }

   function _toggleUmlaut($user_id, $pos) {
      $array = array ();
      $array['ä'] = 'ae';
      $array['ö'] = 'oe';
      $array['ü'] = 'ue';
      $array['ß'] = 'ss';
      $array['ae'] = 'ä';
      $array['oe'] = 'ö';
      $array['ue'] = 'ü';
      $array['ss'] = 'ß';

      $retour = array ();

      $counter = 0;
      for ($i = 0; $i < mb_strlen($user_id); $i++) {
         if (in_array($user_id[$i], $array) or (isset ($user_id[$i +1]) and in_array($user_id[$i] . $user_id[$i +1], $array))) {
            $counter++;
            if ($pos == $counter) {
               $position = $i;
            }
         }
      }

      if ($user_id[$position] == 'a' or $user_id[$position] == 'o' or $user_id[$position] == 'u' or $user_id[$position] == 's') {
         $len = 2;
      } else {
         $len = 1;
      }

      $first = mb_substr($user_id, 0, $position);
      $umlaut = mb_substr($user_id, $position, $len);
      $last = mb_substr($user_id, $position + $len);
      $retour[] = $first . $umlaut . $last;
      if ( !empty($array[$umlaut]) ) {
         $retour[] = $first . $array[$umlaut] . $last;
      }

      return $retour;
   }

   function _getMultipleUserIDArray($user_id) {
      $array = array ();
      $array['ä'] = 'ae';
      $array['ö'] = 'oe';
      $array['ü'] = 'ue';
      $array['ß'] = 'ss';
      $array['ae'] = 'ä';
      $array['oe'] = 'ö';
      $array['ue'] = 'ü';
      $array['ss'] = 'ß';
      $retour = array ();
      $counter = 0;
      for ($i = 0; $i < mb_strlen($user_id); $i++) {
         if (in_array($user_id[$i], $array) or (isset ($user_id[$i +1]) and in_array($user_id[$i] . $user_id[$i +1], $array))) {
            $counter++;
         }
      }
      $retour[$user_id] = $user_id;
      for ($i = 1; $i <= $counter; $i++) {
         foreach ($retour as $key => $value) {
            $result = $this->_toggleUmlaut($value, $i);
            foreach ($result as $key2 => $value2) {
               $retour[$value2] = $value2;
            }
         }
      }
      return $retour;
   }

   /** exists an authentication ?
     * this method returns a boolean whether the authentication exists in the database or not
     *
     * @param integer user_id id of the user (not item id)
     *
     * @return boolean true, if authentication already exists
     *                 false, if authentication not exists -> new user
     */
   function exists($user_id) {
      if($this->_translator == null) {
         global $environment;
         $this->_translator = $environment->getTranslationObject();
      }
      
      $exists = false;
      $user_id_old = $user_id;
      $item = '';
      $this->_get($user_id);
      if (isset ($this->_item)) {
         $user_id = $this->_item->getUserID();
      } else {
         $user_id = '';
      }
      if (!empty ($user_id)) {
         $exists = true;
      } else {
         //$this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_NOT_EXIST',$user_id_old);
         //less specific error message to protect from brute force attacks
         $this->_error_array[] = $this->_translator->getMessage('USER_DOES_NOT_EXIST_OR_PASSWORD_WRONG');
      }
      return $exists;
   }

   /** is user_id free
   * this method returns a boolean whether the user_id is free to choose
   * needed because of german umlauts
   *
   * @param string user_id id of the user (not item id)
   *
   * @return boolean true, if user_id is free
   *                 false, if user_id is not free
   */
   function is_free($user_id) {
      if($this->_translator == null) {
         global $environment;
         $this->_translator = $environment->getTranslationObject();
      }
   	
      $retour = true;
      $item = '';
      $user_id_array = $this->_getMultipleUserIDArray($user_id);
      foreach ($user_id_array as $user_id_to_check) {
         $this->_get($user_id_to_check);
         if ( isset($this->_item) ) {
            $user_id_to_check = $this->_item->getUserID();
            if ( !empty($user_id_to_check) ) {
               $retour = false;
               if (cs_strtoupper($user_id_to_check) != cs_strtoupper($user_id)) {
                  $this->_error_array[] = $this->_translator->getMessage('AUTH_ERROR_ACCOUNT_EXIST', $user_id_to_check);
               }
            }
         }
      }
      return $retour;
   }

   /** save an authentication
     * save an authentication into the database table "auth"
     *
     * @param object cs_item item the authentication item
     */
   function save($item) {
      if ($this->exists($item->getUserID())) {
         $this->_update($item);
      } else {
         $this->_create($item);
      }
      unset ($item);
   }
}
?>