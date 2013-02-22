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
class cs_institution_manager extends cs_labels_manager {

	/** constructor
	* the only available constructor, initial values for internal variables
	*
	* @param object cs_environment the environment
	*/
	function cs_institution_manager ($environment) {
		$this->cs_labels_manager($environment);
	}

	/** resetLimits
	*  reset limits of this manager
	*/
	function resetLimits () {
		parent::resetLimits();
		$this->_type_limit = CS_INSTITUTION_TYPE;
		$this->_context_limit = $this->_environment->getCurrentContextID();
	}
	
	public function updateIndexedSearch($item) {
		$indexer = $this->_environment->getSearchIndexer();
		$query = '
			SELECT
				labels.item_id AS item_id,
				labels.item_id AS index_id,
				NULL AS version_id,
				labels.modification_date,
				CONCAT(labels.name, " ", labels.description, " ", user.firstname, " ", user.lastname) AS search_data
			FROM
				labels
			LEFT JOIN
				user
			ON
				user.item_id = labels.creator_id
			WHERE
				labels.type = "institution" AND
				labels.deletion_date IS NULL AND
				labels.item_id = ' . $item->getItemID() . '
		';
		$indexer->add(CS_INSTITUTION_TYPE, $query);
	}
}
?>
