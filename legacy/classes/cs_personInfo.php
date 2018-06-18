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

class cs_personInfo {
		var $_stine_id;
		var $_user_id;
		var $_family_name; 
		var $_given_name;
		var $_email;
		var $_fullname;
		var $_portal_id;
		var $_source;
		var $_password;
		var $_password_encryption_method;
		
		function __construct($stine_id, $user_id, $fullname, $family_name, $given_name, $email, $portal_id, $source, $password, $password_encryption_method) {
			$this->_stine_id = $stine_id;
			$this->_user_id = $user_id;
			$this->_fullname = $fullname;
			$this->_family_name = $family_name;
			$this->_given_name = $given_name;
			$this->_email = $email;
			$this->_portal_id = $portal_id;
			$this->_source = $source;
			$this->_password = $password;
			$this->_password_encryption_method = $password_encryption_method;
		}
		
		function getPassword() {
			return $this->_password;
		}
		
		function getPasswordEncryptionMethod() {
			return $this->_password_encryption_method;
		}
		
		function getSourceSystem() {
			return $this->_source;
		}
		
		function getPortalId() {
			return $this->_portal_id;
		}
		
		function getStineId() {
		   return $this->_stine_id;
		}
		
		function getUserId() {
		   return $this->_user_id;
		}
		
		function getFullname() {
		   return $this->_fullname;
		}
		
		function getFamilyName() {
		   return $this->_family_name;
		}
		
		function getGivenName() {
		   return $this->_given_name;
		}
		
		function getEmail() {		
		   return $this->_email;
		}
		
	}
?>