<?PHP
// 
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
//    along with CommSy

class cs_roomInfo {
		var $_stine_id;
		var $_description_short;
		var $_description_long; 
		var $_portal_id;
		var $_institution;
		var $_faculty;
		var $_source;
		
		function __construct($stine_id, $description_short, $description_long, $portal_id, $institution, $faculty, $source) {
			$this->_stine_id = $stine_id;
			$this->_description_short = $description_short;
			$this->_description_long = $description_long;
			$this->_portal_id = $portal_id;
			$this->_institution = $institution;
			$this->_faculty = $faculty;
			$this->_source = $source;
		}
		
		function getSourceSystem() {
			return $this->_source;
		}
		
		function getStineId() {
		   return $this->_stine_id;
		}
		
		function getDescriptionShort() {
		   return $this->_description_short;
		}
		
		function getDescriptionFull() {
		   return $this->_description_long;
		}
		
		function getPortalId() {
		   return $this->_portal_id;
		}
		
		function getInstitution() {
		   return $this->_institution;
		}
		
		function getFaculty() {		
		   return $this->_faculty;
		}
	}
?>
