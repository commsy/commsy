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

/** class for database connection to the database table "log_error"
 * this class implements a database manager for the table "log_error".
 */
class cs_log_error_manager extends cs_manager {

   /** constructor: cs_log_manager
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
   function __construct($environment ) {
      cs_manager::__construct($environment);
	  $this->_db_table = CS_LOG_ERROR_TYPE;
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

   function count () {
      $retour = 0;
      $result = $this->_performQuery('count');
      $row = $result[0];
      $retour = $row['count'];
      return $retour;
   }

   function delete () {
      return $this->_performQuery('delete');
   }

   function deleteByArray ($array) {
      $id_string = '';
      $first = true;
      foreach ($array as $row) {
         if ( !empty($row['id']) ) {
            if ($first) {
               $first = false;
            } else {
               $id_string .= ',';
            }
            $id_string .= $row['id'];
         }
      }

      $query  = '';
      $query .= 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE id IN ('.$id_string.')';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems at logs from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   function _performQuery ( $mode = 'select') {
      if ($mode == 'select') {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
      } elseif ($mode == 'delete') {
         $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table);
      } elseif ($mode == 'count') {
         $query = 'SELECT count(id) AS count FROM '.$this->addDatabasePrefix($this->_db_table);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('lost perform mode',E_USER_ERROR);
      }

      $query .= ' WHERE 1';

      $query .= ' ORDER BY datetime ASC';

      if (isset($this->_limit_from) and isset($this->_limit_range)) {
         if ( empty($this->_limit_form) ) {
            $query .= ' LIMIT 0,'.encode(AS_DB,$this->_limit_range);
         } else {
            $query .= ' LIMIT '.encode(AS_DB,$this->_limit_from).','.encode(AS_DB,$this->_limit_range);
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems log from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result;
      }
   }

   public function saveArray ( $array ) {
      $retour = false;
      if ( !isset($array['number']) ) {
         $array['number'] = '';
      }
      if ( !isset($array['type']) ) {
         $array['type'] = '';
      }
      if ( !isset($array['message']) ) {
         $array['message'] = '';
      }
      if ( !isset($array['file']) ) {
         $array['file'] = '';
      }
      if ( !isset($array['line']) ) {
         $array['line'] = '';
      }
      if ( !isset($array['context']) ) {
         $array['context'] = '';
      }
      if ( !isset($array['module']) ) {
         $array['module'] = '';
      }
      if ( !isset($array['function']) ) {
         $array['function'] = '';
      }
      if ( !isset($array['user']) ) {
         $array['user'] = '';
      }

      // mysql - replication
      $delayed = ' DELAYED ';
      $db_replication = $this->_environment->getConfiguration('db_replication');
      if ( !empty($db_replication)
           and $db_replication
         ) {
         $delayed = ' ';
      }
      $query = 'INSERT'.$delayed.'INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'datetime=NOW(), '.
               'number="'.  encode(AS_DB,$array['number']).'", '.
               'type="'.    encode(AS_DB,$array['type']).'", '.
               'message="'. encode(AS_DB,$array['message']).'", '.
               'file="'.    encode(AS_DB,$array['file']).'", '.
               'line="'.    encode(AS_DB,$array['line']).'", '.
               'context="'. encode(AS_DB,$array['context']).'", '.
               'module="'.  encode(AS_DB,$array['module']).'", '.
               'function="'.encode(AS_DB,$array['function']).'", '.
               'user="'.    encode(AS_DB,$array['user']).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) ) {
         $retour = true;
      } else {
         include_once('functions/error_functions.php');
         trigger_error('Problems save '.$this->_db_table.' with query: "'.$query.'"',E_USER_WARNING);
      }
      return $retour;
   }
}
?>