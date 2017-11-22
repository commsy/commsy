<?php
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

// include upper class
include_once('classes/cs_manager.php');

/**
 * Class for database connection to the database table "item_backup".
 * This class implements a database manager for the table "item_backup":
 */
class cs_backupitem_manager extends cs_manager {
	////////////////////////////////////////////////////////
	/// constructors
	////////////////////////////////////////////////////////
	
	/**
	 * constructor
	 * 
	 * @param object cs_environment the environment
	 */
	function __construct($environment) {
	   cs_manager::__construct($environment);
	   $this->_db_table = CS_ITEMBACKUP_TYPE;
	}
	
	////////////////////////////////////////////////////////
	/// methods
	////////////////////////////////////////////////////////
	
	/**
	 * Deletes all Entries in table "item_backup" older than $days
	 * 
	 * @param $days number of days for which entries are allowed to stay in table
	 * @return boolean success
	 */
	public function deleteOlderThan($days) {
	   $retour = false;
	   $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
	   $query = '
	      DELETE FROM
	         ' . $this->addDatabasePrefix($this->_db_table) . '
	      WHERE
	         backup_date < "' . $timestamp . '"';
	   $result = $this->_db_connector->performQuery($query);
	   if(!isset($result) || !$result) {
	      include_once('functions/error_functions.php');
	      trigger_error('Problem deleting items.', E_USER_ERROR);
	   } else {
	   	  unset($result);
	   	  $retour = true;
	   }
	   
	   return $retour;
	}
}
?>