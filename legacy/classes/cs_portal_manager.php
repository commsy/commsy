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

/** upper class of the project manager
 */
include_once('classes/cs_context_manager.php');

/** class for database connection to the database table "project"
 * this class implements a database manager for the table "project"
 */
class cs_portal_manager extends cs_context_manager {

  private $_url_limit = NULL;

  /** constructor: cs_server_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     $this->_db_table = 'portal';
     $this->_room_type = CS_PORTAL_TYPE;
     cs_context_manager::__construct($environment);
  }

   /** reset limits
    * reset limits of this class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_url_limit = NULL;
   }

   /** set url limit
    */
   function setUrlLimit($limit) {
      $this->_url_limit = (string)$limit;
   }

   /** select communities limited by limits
    * this method returns a list (cs_list) of communities within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
   function _performQuery ($mode = 'select') {
      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) AS count';
      } elseif ($mode == 'id_array') {
          $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      }

      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE 1';

      // insert limits into the select statement
      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      }
      if (isset($this->_status_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
      }
      if (isset($this->_url_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.url LIKE "%'.encode(AS_DB,$this->_url_limit).'%"';
      }

      if (isset($this->_order)) {
         if ($this->_order == 'date') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
         } elseif ($this->_order == 'creation_date') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
         } elseif ($this->_order == 'activity_rev') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
         } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
         }
      } else {
         $query .= ' ORDER BY title, modification_date DESC';
      }

      if ($mode == 'select') {
         if (isset($this->_interval_limit) and isset($this->_from_limit)) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',E_USER_ERROR);
      } else {
         return $result;
      }
   }
}
?>