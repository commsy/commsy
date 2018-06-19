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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** cs_dates_item is needed to create dates items
 */
include_once('classes/cs_dates_item.php');
include_once('functions/text_functions.php');
include_once('functions/date_functions.php');


/** class for database connection to the database table "dates"
 * this class implements a database manager for the table "dates"
 */
class cs_external_id_manager extends cs_manager {

   /**
   * string - containing the source-system as a limit
   */
   var $_source_limit = NULL;

   /**
   * integer - containing an external id
   */
   var $_external_id_limit = NULL;

   /**
   * integer - containing a commsy id
   */
   var $_commsy_id_limit = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'external2commsy_id';
   }

    /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $_source_limit = NULL;
      $_external_id_limit = NULL;
      $_commsy_id_limit = NULL ;
   }

   /** set source limit
    * this method sets a source limit
    *
    * @param string limit source
    */
   public function setSourceLimit ($limit) {
      $this->_source_limit = (string)$limit;
   }

   /** set system limit
    * this method sets a system limit
    *
    * @param string limit system
    */
   public function setSystemLimit ($limit) {
      $this->_source_limit = (string)$limit;
   }

   function setCommSyIdLimit ($limit) {
      $this->_commsy_id_limit = (int)$limit;
   }

   function setExternalIdLimit ($limit) {
      $this->_external_id_limit = (int)$limit;
   }

   function _performQuery($mode = 'select') {
      if ($mode == 'count') {
         $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.commsy_id) as count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.commsy_id';
      } elseif ($mode == 'distinct') {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
      } else {
         $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
      }

      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table).'';

      $query .= ' WHERE 1';

      // fifth, insert limits into the select statement
      if ( isset($this->_commsy_id_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB,$this->_commsy_id_limit).'"';
      }
      if (isset($this->_source_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB,$this->_source_limit).'"';
      }
      if (isset($this->_external_id_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB,$this->_external_id_limit).'"';
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
          include_once('functions/error_functions.php');
          trigger_error('Problems selecting from '.$this->_db_table.'.',E_USER_WARNING);
      } else {
          return $result;
      }
   }

   public function addIDsToDB($source,$external_id,$commsy_id) {
      $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' VALUES ("'.encode(AS_DB,$external_id).'","'.encode(AS_DB,$source).'","'.encode(AS_DB,$commsy_id).'")';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting from '.$this->_db_table.'.',E_USER_WARNING);
      }
   }

   public function getCommSyId ($source,$external_id) {
      $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.commsy_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB,$source).'" AND '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB,$external_id).'"';
      $this->_last_query = $query;
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting from '.$this->_db_table.'.',E_USER_WARNING);
      } elseif ( !empty($result[0]['commsy_id']) ) {
         return $result[0]['commsy_id'];
      } else {
         return null;
      }
   }

   public function getExternalId ($source,$commsy_id) {
      $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.external_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.source_system LIKE "'.encode(AS_DB,$source).'" AND '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB,$commsy_id).'"';
      $this->_last_query = $query;
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting from '.$this->_db_table.'.',E_USER_WARNING);
      } elseif ( !empty($result[0]['external_id']) ) {
         return $result[0]['external_id'];
      } else {
         return null;
      }
   }

   function deleteByExternalId($external_id,$source_system) {
      $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.external_id = "'.encode(AS_DB,$external_id).'" AND '.$this->addDatabasePrefix($this->_db_table).'.source_system = "'.encode(AS_DB,$source_system).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result) {
         include_once('functions/error_functions.php');trigger_error('Problems deleting from '.$this->_db_table.'.',E_USER_WARNING);
      }
   }

   function deleteByCommSyId($iid) {
      $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.commsy_id = "'.encode(AS_DB,$iid).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result) {
         include_once('functions/error_functions.php');trigger_error('Problems deleting from '.$this->_db_table.'.',E_USER_WARNING);
      }
   }
}
?>