<?PHP
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

/** upper class of the link item
 */
include_once('classes/cs_item.php');

/** class for a CommSy item: link
 * this class implements a link item
 */
class cs_link_item extends cs_item {

   /** constructor
    *
    *
    * @author CommSy Development Group
    */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'link_item';
   }

   /************** set methods*************************/

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
   }

   /** sets the first linked item of the link_item.
    *
    * @param $object
    */
   function setFirstLinkedItem ($item) {
      return $this->_setObject('first_linked_item',$item);
   }

   /** sets the first linked item id of the link_item.
    *
    * @param $value
    */
   function setFirstLinkedItemID ($value) {
      return $this->_setValue('first_item_id',$value);
   }

   /** sets the first linked item of the link_item.
    *
    * @param $object
    *
    * @author CommSy Development Group
    */
   function setSecondLinkedItem ($item) {
      return $this->_setObject('second_linked_item',$item);
   }

   /** sets the second linked item id of the link_item.
    *
    * @param $value
    */
   function setSecondLinkedItemID ($value) {
      return $this->_setValue('second_item_id',$value);
   }

   /** sets the type of the first linked item of the link_item.
    *
    * @param string
    *
    * @author CommSy Development Group
    */
   function setFirstLinkedItemType ($type) {
      return $this->_setValue('first_item_type',$type);
   }

   /** sets the type of the second linked item of the link_item.
    *
    * @param string
    */
   function setSecondLinkedItemType ($type) {
      return $this->_setValue('second_item_type',$type);
   }

   /** set the x-position of the link
    * this method set the x-position of the link for study.log
    *
    * @param int
    */
   function setPosX ($value) {
      $this->_addExtra('x',(int)$value);
   }

   /** set the y-position of the link
    * this method set the y-position of the link for study.log
    *
    * @param int
    */
   function setPosY ($value) {
      $this->_addExtra('y',(int)$value);
   }

   /************** get methods ***********************/

   /** gets the first linked item of the link_item.
    *
    * @return $object
    *
    * @author CommSy Development Group
    */
   function getFirstLinkedItem () {
      $object = $this->_getObject('first_linked_item');
      if (isset($object)) {
         return $object;
      } else {
         $type = $this->_getValue('first_item_type');
         // for caching -> use always room manager to get item
         if ( $type == CS_PROJECT_TYPE
              or $type == CS_COMMUNITY_TYPE
              or $type == CS_GROUPROOM_TYPE
            ) {
            $type = CS_ROOM_TYPE;
         }
         $item_manager = $this->_environment->getManager($type);
         $item = $item_manager->getItem($this->_getValue('first_item_id'));
         return $item;
      }
   }

   /** gets the second linked item of the link_item.
    *
    * @return $object
    *
    * @author CommSy Development Group
    */
   function getSecondLinkedItem () {
      $object = $this->_getObject('second_linked_item');
      if (isset($object)) {
         return $object;
      } else {
         $type = $this->_getValue('second_item_type');
         // for caching -> use always room manager to get item
         if ( $type == CS_PROJECT_TYPE
              or $type == CS_COMMUNITY_TYPE
              or $type == CS_GROUPROOM_TYPE
            ) {
            $type = CS_ROOM_TYPE;
         }
         $item_manager = $this->_environment->getManager($type);
         $item = $item_manager->getItem($this->_getValue('second_item_id'));
         return $item;
      }
   }

   /** gets the linked item of another item of the link_item.
    *
    * @param $object
    *
    * @return $object
    */
   function getLinkedItem($one_item){
      $temp_item = $this->getFirstLinkedItem();
      $other_item = NULL;
      if ( isset($temp_item) ) {
         if ($temp_item->getItemID() == $one_item->getItemID()) {
            $other_item = $this->getSecondLinkedItem();
         } else {
            $other_item = $temp_item;
         }
      }
      return $other_item;
   }

   function getLinkedItemID($one_item){
      $temp_id = $this->getFirstLinkedItemID();
      if ($temp_id == $one_item->getItemID()) {
         $other_id = $this->getSecondLinkedItemID();
      } else {
         $other_id = $temp_id;
      }
      return $other_id;
   }

   /** gets the type of the first linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getFirstLinkedItemType () {
      return $this->_getValue('first_item_type');
   }

   /** gets the type of the second linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getSecondLinkedItemType () {
      return $this->_getValue('second_item_type');
   }

   /** gets the id of the first linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getFirstLinkedItemID () {
      return $this->_getValue('first_item_id');
   }

   /** gets the iid of the second linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getSecondLinkedItemID () {
      return $this->_getValue('second_item_id');
   }

   /** gets the id of the first linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getFirstLinkedItemVersionID () {
      return $this->_getValue('first_version_id');
   }

   /** gets the iid of the second linked item of the link_item.
    *
    * @return $string
    *
    * @author CommSy Development Group
    */
   function getSecondLinkedItemVersionID () {
      return $this->_getValue('second_version_id');
   }

   function getSortingPlace () {
      return $this->_getValue('sorting_place');
   }

   /** sets the sorting place.
    *
    * @param $value
    */
   function setSortingPlace ($value) {
      return $this->_setValue('sorting_place',$value);
   }

   /** get the x-position of the link
    * this method get the x-position of the link for study.log
    *
    * @param int
    */
   function getPosX () {
      $retour = $this->_getExtra('x');
      return $retour;
   }

   /** get the y-position of the link
    * this method get the y-position of the link for study.log
    *
    * @param int
    */
   function getPosY () {
      $retour = $this->_getExtra('y');
      return $retour;
   }
   
/** other methods **/

  /** save link item
   * this methode save the link item into the database
   */
   function save() {
      $first_item = $this->getFirstLinkedItem();
      $second_item = $this->getSecondLinkedItem();
      $first_room_id = $first_item->getContextID();
      $second_room_id = $second_item->getContextID();
      if ($first_room_id == $second_room_id) {
         $this->setContextID($first_room_id);
      }
      $link_manager = $this->_environment->getLinkItemManager();
      $this->_save($link_manager);
   }

   /** delete link
    * this method deletes the link
    *
    * @author CommSy Development Group
    */
   function delete() {
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->delete($this->getItemID());
   }
}
?>