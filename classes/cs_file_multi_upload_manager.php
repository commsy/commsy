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

/** upper class of the log manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "file_multi_upload"
 * this class implements a database manager for the table "file_multi_upload"
 * to store informations about the upload files temporary
 */
class cs_file_multi_upload_manager extends cs_manager {

   private $_limit_session_id = NULL;

   /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function cs_file_multi_upload_manager ( $environment ) {
      $this->cs_manager($environment);
      $this->_db_table = 'file_multi_upload';
   }

   /** reset limits
    * reset limits of this class: room limit, delete limit
    */
   function resetLimits () {
      $this->_limit_session_id = NULL;
   }

   function setSessionIDLimit ($value) {
      $this->_limit_session_id = $value;
   }

   // get only the file array, not the whole database informations
   function select () {
      $result = $this->_performQuery('select');
      $array = array();
      foreach ($result as $row) {
         if ( isset($row['file_array']) and !empty($row['file_array']) ) {
            include_once('functions/text_functions.php');
            $array[] = cs_unserialize($row['file_array']);
         }
      }
      return $array;
   }

   function count () {
      $retour = 0;
      $result = $this->_performQuery('count');
      $retour = $result[0]['count'];
      return $retour;
   }

   function delete () {
      return $this->_performQuery('delete');
   }

   // special method to add a file array
   function addFileArray ($session_id, $file_array) {
      $query = 'INSERT INTO file_multi_upload SET session_id="'.encode(AS_DB,$session_id).'", file_array="'.encode(AS_DB,serialize($file_array)).'";';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   function _performQuery ( $mode = 'select') {
      if ($mode == 'select') {
         $query = 'SELECT * FROM '.$this->_db_table;
      } elseif ($mode == 'delete') {
         $query = 'DELETE FROM '.$this->_db_table;
      } elseif ($mode == 'count') {
         $query = 'SELECT count(id) AS count FROM '.$this->_db_table;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('lost perform mode',E_USER_ERROR);
      }

      $query .= ' WHERE 1';

      if (isset($this->_limit_session_id) and $this->_limit_session_id !=0 ) {
         $query .= ' AND session_id = "'.encode(AS_DB,$this->_limit_session_id).'"';
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }
}
?>