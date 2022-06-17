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

/** class for links
 * this class implements a link object
 */
class cs_tag2tag_item {

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
      $this->_type = CS_TAG2TAG_TYPE;
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

   /** set the link id of the item
    * this method sets the link id of the item
    *
    * @param integer link_id of item
    */
   function setLinkID ($value) {
      $this->_data['link_id'] = (int)$value;
   }

   /** return the link id of the item
    * this method returns the link id of the item
    *
    * @return integer link_id of the item
    */
   function getLinkID () {
      return $this->_getValue('link_id');
   }

   /** set the item id of the father item
    * this method sets the item id of the father item
    *
    * @param integer item_id of father item
    */
   function setFatherItemID ($value) {
      $this->_data['father_id'] = (int)$value;
   }

   /** return the item id of the father item
    * this method returns the item id of the father item
    *
    * @return integer item_id of the father item
    */
   function getFatherItemID () {
      return $this->_getValue('father_id');
   }

   /** set the item id of the child item
    * this method sets the item id of the child item
    *
    * @param integer item_id of child item
    */
   function setChildItemID ($value) {
      $this->_data['child_id'] = (int)$value;
   }

   /** return the item id of the child item
    * this method returns the item id of the child item
    *
    * @return integer item_id of the child item
    */
   function getChildItemID () {
      return $this->_getValue('child_id');
   }

   /** set the item_id of the creator
    * this method sets the item_id of the creator
    *
    * @param integer item_id of the creator
    */
   function setCreatorItemID ($value) {
      $this->_data['creator_id'] = (int)$value;
   }

   /** return the item_id of the creator
    * this method returns the item_id of the creator
    *
    * @return integer item_id of the creator
    */
   function getCreatorItemID () {
      return $this->_getValue('creator_id');
   }

   /** set the item_id of the modifier
    * this method sets the item_id of the modifier
    *
    * @param integer item_id of the modifier
    */
   function setModifierItemID ($value) {
      $this->_data['modifier_id'] = (int)$value;
   }

   /** return the item_id of the modifier
    * this method returns the item_id of the modifier
    *
    * @return integer item_id of the modifier
    */
   function getModifierItemID () {
      return $this->_getValue('modifier_id');
   }

   /** set the item_id of the deleter
    * this method sets the item_id of the deleter
    *
    * @param integer item_id of the deleter
    */
   function setDeleterItemID ($value) {
      $this->_data['deleter_id'] = (int)$value;
   }

   /** return the item_id of the deleter
    * this method returns the item_id of the deleter
    *
    * @return integer item_id of the deleter
    */
   function getDeleterItemID () {
      return $this->_getValue('deleter_id');
   }

   function setCreationDate ($value) {
      $this->_data['creation_date'] = $value;
   }

   function setModificationDate ($value) {
      $this->_data['modification_date'] = $value;
   }

   function setDeletionDate ($value) {
      $this->_data['deletion_date'] = $value;
   }

   /** sets the sorting place
    *
    * @param int sorting place
    */
   function setSortingPlace ($value) {
      $this->_data['sorting_place'] = (int)$value;
   }

   /** gets the sorting place
    *
    * @param int sorting place
    */
   function getSortingPlace () {
      return $this->_getValue('sorting_place');
   }

   /** set the context id of the link
    * this method sets the context id of the link
    *
    * @param string context id of the link
    */
   function setContextItemID ($value) {
      $this->_data['context_id'] = (int)$value;
   }

   /** return the context id of the link, INTERNAL
    * this method returns the context id of the link
    *
    * @return string context id of the link
    */
   function getContextItemID () {
      return $this->_getValue('context_id');
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