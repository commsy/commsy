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

/** upper class of the assessments item
 */
include_once('classes/cs_item.php');

/** class for assessments
 * this class implements an assessments object
 */
class cs_assessments_item extends cs_item {

   /**
    * string - containing the type of the list resp. the type of the elements
    */
   var $_type = NULL;

   /**
    * array - containing the elements of the list
    */
   var $_data = array();

   /** constructor
    * the only available constructor, initial values for internal variables
    */
   function __construct ($environment) {
	  $this->_environment = $environment;
      $this->_type = CS_ASSESSMENT_TYPE;
   }

   /** is the type of the list = $type ?
    * this method returns a boolean expressing if type of the list is $type or not
    *
    * @param string type string to compare with type of list (_type)
    *
    * @return boolean   true - type of this list is $type
    *                   false - type of this list is not $type
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** return the type of the object = link
    * this method returns the type of the object = link
    *
    * @return string type of the object link
    *
    * @author CommSy Development Group
    */
   function getType () {
      return $this->_type;
   }
   
   /**
    * sets item id of item
    * 
    * @param integer item_it
    */
   function setItemID($value) {
   	 $this->_data['item_id'] = (int) $value;
   }
   
   /**
    * sets context id of item
    * 
    * @param integer context_id
    */
   function setContextID($value) {
   	 $this->_data['context_id'] = (int) $value;
   }
   
   /**
    * returns context id of item
    * 
    * @return integer context_id
    */
   function getContextID() {
   	 return $this->_getValue('context_id');
   }
   
   /**
    * sets creator id of item
    * 
    * @param integer creator_id
    */
   function setCreatorID($value) {
   	 $this->_data['creator_id'] = (int) $value;
   }
   
   /**
    * returns creator id of item
    * 
    * @return integer creator_id
    */
   function getCreatorID() {
   	 return $this->_getValue('creator_id');
   }
   
   /**
    * sets deleter id of item
    * 
    * @param integer deleter_id
    */
   function setDeleterID($value) {
   	 $this->_data['deleter_id'] = (int) $value;
   }
   
   /**
    * returns deleter id of item
    * 
    * @return integer deleter_id
    */
   function getDeleterID() {
   	 return $this->_getValue('deleter_id');
   }
   
   /**
    * sets creation date of item
    * 
    * @param date creation_date
    */
   function setCreationDate($value) {
     $this->_data['creation_date'] = $value;
   }
   
   /**
    * returns creation date of item
    * 
    * @return date creation_date
    */
   function getCreationDate() {
   	 return $this->_getValue('creation_date');
   }
   
   /**
    * sets deletion date of item
    * 
    * @param date deletion_date
    */
   function setDeletionDate($value) {
   	 $this->_data['deletion_date'] = $value;
   }
   
   /**
    * returns deletion date of item
    * 
    * @return date deletion_date
    */
   function getDeletionDate() {
   	 return $this->_getValue('deletion_date');
   }
   
   /**
    * sets id of the linked item
    * 
    * @param integer item_link_id
    */
   function setItemLinkID($value) {
   	 $this->_data['item_link_id'] = (int) $value;
   }
   
   /**
    * returns id of the linked item
    * 
    * @return integer item_link_id
    */
   function getItemLinkID() {
   	 return $this->_getValue('item_link_id');
   }
   
   /**
    * sets assessment of item
    * 
    * @param integer assessment
    */
   function setAssessment($value) {
   	 $this->_data['assessment'] = (int)	$value;
   }
   
   /**
    * returns assessment of item
    */
   function getAssessment() {
   	 return $this->_getValue('assessment');
   }
   
   /** return a value of the link, INTERNAL
    * this method returns a value of the link
    *
    * @return string a value the link
    */
   function _getValue ($key) {
      if (!empty($this->_data[$key])) {
         $value = $this->_data[$key];
      } else {
         $value = '';
      }
      return $value;
   }

   function save() {
      $manager = $this->_environment->getManager($this->_type);
      $manager->saveItem($this);
   }
}
?>