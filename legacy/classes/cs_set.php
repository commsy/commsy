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

class cs_set {

   var $_data = array();

   var $_type;

   function __construct() {
      $this->_type = 'set';
   }

   /** is the type of the set = $type ?
    * this method returns a boolean expressing if type of the set is $type or not
    *
    * @param string type string to compare with type of set (_type)
    *
    * @return boolean   true - type of this set is $type
    *                   false - type of this set is not $type
    *
    * @author CommSy Development Group
    */
   function isA ($type) {
      return $this->_type == $type;
   }

    /** reset internal variables
    *
    * this method resets the set (emptys it)
    *
    * @version CommSy 2.0
    * @author CommSy Development Group
    */
   function reset () {
      $this->_data = array();
   }

   /** adds data to the set
   *
   * @param int id The id the data is called by
   * @param array data The data to be stored
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */
   function setByID($id, $data) {
      if (!array_key_exists($id,$this->_data)) {
         $this->_data[$id] = $data;
      }
   }

   /** deletes data from the set
   *
   * @param int id the key that will be deleted from the set
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */
   function remove($id) {
      $new_data = array();

      foreach($this->_data as $key => $data) {
         if ($key != $id) {
            $new_data[$key] = $data;
         }
      }
      $this->_data = $new_data;
   }

   /** Updates data in the set (overwrites the old entry)
   *
   * @param int id The id the data is called by
   * @param array data The data to be stored
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */
   function renew($id, $data) {
      $this->remove($id);
      $this->setByID($id,$data);
   }

   /** returns an array of all id's in the set AND in the param array (intersection of the two arrays)
   *
   * @param array Id's
   * @return array Array of Id's in the set and in the parammeter-array
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */

   function getExistingIDArray($id_array) {
      $data_id = array_keys($this->_data);
      return array_intersect($id_array,$data_id);
   }

   /** returns an array of all id's NOT in the set BUT in the param array (difference of the two arrays)
   *
   * @param array Id's
   * @return array Array of Id's not in the set but in the parammeter-array
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */

   function getNonExistingIDArray($id_array) {
      $data_id = array_keys($this->_data);
      return array_diff($id_array,$data_id);
   }


   /** returns the data for the id, or null if the id is not in the set
   *
   * @param int Id of the data
   * @return array If the data is in the set, the function returns the data, if not it returns null
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */
   function getByID($id) {
   	if (array_key_exists($id,$this->_data)) {
         return $this->_data[$id];
      } else {
         return null;
      }
   }


   /** returns an array of datas in the set for the id's, or an empty array if none of the id's is in the set
   *
   * @param array Id's of the data
   * @return array If a data is in the set, the function generates an entry in the return array, if none of the id's is in the set it returns an empty array
   *
   * @version CommSy 2.0
   * @author CommSy Development Group
   */
   function getArray($id_array) {
      $data_array = array();

      foreach ($id_array as $key) {
         if (array_key_exists($key,$this->_data)) {
            $data_array[$key] = $this->_data[$key];
         }
      }
      return $data_array;
   }

}
?>