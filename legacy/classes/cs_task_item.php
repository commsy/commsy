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

/** class of the task item
 */
include_once('classes/cs_item.php');

/** class for a task
 * this class implements a task item
 */
class cs_task_item extends cs_item {

   /**
    * object - linked object to the task item
    */
   var $_item = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object environment the environment of the commsy
    */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'task';
   }

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      // not yet implemented
      $this->_data = $data_array;
   }

   /** get title of a task
    * this method returns the title of the task
    *
    * @return string title of a task
    *
    * @author CommSy Development Group
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set title of a task
    * this method sets the title of the task
    *
    * @param string value title of the task
    *
    * @author CommSy Development Group
    */
   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeHTML($value);
      $this->_setValue('title', $value);
   }

   /** get status of a task
    * this method returns the status of the task
    *
    * @return string status of a task
    *
    * @author CommSy Development Group
    */
   function getStatus () {
      return $this->_getValue('status');
   }

   /** set status of a task
    * this method sets the status of the task
    *
    * @param string value status of the task
    *
    * @author CommSy Development Group
    */
   function setStatus ($value) {
      $this->_setValue('status', $value);
   }

   /** set linked item of a task
    * this method sets the linked item of the task
    *
    * @param cs_item value linked_item of the task
    *
    * @author CommSy Development Group
    */
   function setItem($item) {
      $this->setLinkedItemID($item->getItemID());
   }

   /** set linked item_id of a task
    * this method sets the linked item_id of the task
    *
    * @param int value linked_item_id of the task
    *
    * @author CommSy Development Group
    */
   function setLinkedItemID($item_id) {
      $this->_setValue('linked_item_id', $item_id);
   }

   /** get linked item_id of a task
    * this method gets the linked item_id of the task
    *
    * @retrun int value linked_item_id of the task
    *
    * @author CommSy Development Group
    */
   function getLinkedItemID() {
      return $this->_getValue('linked_item_id');
   }

   /** get linked item of a task
    * this method gets the linked item_id of the task
    *
    * @param object value linked_item of the task
    */
   function getItem() {
      return $this->getLinkedItem();
   }

   /** get linked item
    * this method returns a commsy item which is linked to the task
    *
    * @return object cs_item a commsy item (cs_*_item)
    */
   function getlinkedItem(){
      $item_id = $this->_getValue('linked_item_id');
      $item_manager = $this->_environment->getItemManager();
      $item = $item_manager->getItem($item_id);
      if (!empty($item)){
         $manager = $this->_environment->getManager($item->getItemType());
         if (!empty($manager)) {
            $item = $manager->getItem($item->getItemID());
         }
      }
      return $item;
   }

   /** save task
    * this method save the task
    */
   function save() {
      $task_manager = $this->_environment->getTaskManager();
      $title = $this->getTitle();
      $this->_save($task_manager);
   }

   /** delete task
    * this method deletes the task
    */
   function delete() {
      $task_manager = $this->_environment->getTaskManager();
      $this->_delete($task_manager);
      
      // delete associated annotations
      $this->deleteAssociatedAnnotations();
   }
}
?>