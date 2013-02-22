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

/** upper class of the log ads manager
 */
include_once('classes/cs_manager.php');

/** class for database connection to the database table "reader"
 * this class implements a database manager for the table "reader". Read items
 */
class cs_log_ads_manager extends cs_manager {

   var $_table_name = 'log_ads';

   /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function cs_log_ads_manager ( $environment ) {
      $this->cs_manager($environment);
   }

   /** reset limits
    * reset limits of this class: room limit, delete limit
    */
   function resetLimits () {
   }

   function select () {
      $result = $this->_performQuery('select');
      $array = array();
      foreach ($result as $row) {
         $array[] = $row;
      }
      return $array;
   }

   function delete () {
      return $this->_performQuery('delete');
   }

   function _performQuery ( $mode = 'select') {
      if ($mode == 'select') {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_table_name);
      } elseif ($mode == 'delete') {
         $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_table_name);
      } else {
         include_once('functions/error_functions.php');trigger_error('lost perform mode',E_USER_ERROR);
      }

      $query .= ' WHERE 1';

      $query .= ' ORDER BY timestamp ASC';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems '.$this->_table_name.' from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   function save ($data) {
      if ( !is_array($data) ) {
         include_once('functions/error_functions.php');
         trigger_error('need array',E_USER_ERROR);
         $success = false;
      } else {
         $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_table_name).' SET '.
                  'cid="'.encode(AS_DB,$data['cid']).'", '.
                  'aim="'.encode(AS_DB,$data['aim']).'", '.
                  'timestamp=NOW()';

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');trigger_error('Problems '.$this->_table_name.' from query:<br />"'.$query.'"',E_USER_WARNING);
            $success = false;
         } else {
            $success = true;
         }
      }
      return $success;
   }
}
?>