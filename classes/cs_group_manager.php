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

/** upper class of the label manager
 */
include_once('classes/cs_labels_manager.php');

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_group_manager extends cs_labels_manager {

	/** constructor
	* the only available constructor, initial values for internal variables
	*
	* @param object cs_environment the environment
	*/
	public function __construct ($environment) {
		$this->cs_labels_manager($environment);
	}

	/** resetLimits
	*  reset limits of this manager
	*/
	public function resetLimits () {
		parent::resetLimits();
		$this->_type_limit = CS_GROUP_TYPE;
		$this->_context_limit = $this->_environment->getCurrentContextID();
	}
	
  /** get an empty group item
    *  get an empty label (group) item
    *
    *  @return cs_label_item a group label
    */
   public function getNewItem () {
      include_once('classes/cs_group_item.php');
      $item = new cs_group_item($this->_environment);
      return $item;
   }
}
?>