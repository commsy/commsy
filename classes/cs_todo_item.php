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

/** upper class of the todo item
 */
include_once('classes/cs_item.php');

/** class for a todo
 * this class implements a todo item
 */
class cs_todo_item extends cs_item {

  /** constructor
   * the only available constructor, initial values for internal variables
   */
   function cs_todo_item($environment) {
      $this->cs_item($environment);
      $this->_type = CS_TODO_TYPE;
   }

  /** get title of a todo
   * this method returns the title of the todo
   *
   * @return string title of a todo
   *
   * @author CommSy Development Group
   */
   function getTitle () {
      return $this->_getValue('title');
   }

  /** set title of a todo
   * this method sets the title of the todo
   *
   * @param string value title of the todo
   *
   * @author CommSy Development Group
   */
   function setTitle($title) {
      $this->_setValue('title', $title);
   }

  /** get description of a todo
   * this method returns the description of the todo
   *
   * @return string description of a todo
   *
   * @author CommSy Development Group
   */
   function getDescription () {
      return $this->_getValue('description');
   }

  /** set description of a todo
   * this method sets the description of the todo
   *
   * @param string value description of the todo
   *
   * @author CommSy Development Group
   */
   function setDescription($description) {
      $this->_setValue('description', $description);
   }

  /** get date of a todo
   * this method returns the date of the todo
   *
   * @return datetime date of a todo
   */
   function getDate () {
      return $this->_getValue('end_date');
   }

  /** set date of a todo
   * this method sets the date of the todo
   *
   * @param string value date of the todo
   */
   function setDate($date) {
      $this->_setValue('end_date', $date);
   }

  /** get status of a todo
   * this method returns the status of the todo
   *
   * @return statustime status of a todo
   */
   function getStatus () {
      $value = $this->_getValue('status');
      if ($value =='2') {
        return getMessage('TODO_IN_POGRESS');
      } elseif ($value =='3') {
        return getMessage('TODO_DONE');
      } else {
        return getMessage('TODO_NOT_STARTED');
      }
   }

  /** get status of a todo
   * this method returns the status of the todo
   *
   * @return statustime status of a todo
   *
   * @author CommSy Development Group
   */
   function getInternalStatus () {
      return $this->_getValue('status');
   }

  /** set status of a todo
   * this method sets the status of the todo
   *
   * @param string value status of the todo
   *
   * @author CommSy Development Group
   */
   function setStatus($status) {
      $this->_setValue('status', $status);
   }

  /** set groups of a todo
   * this method sets a list of groups which are linked to the todo
   *
   * @param string value title of the todo
   *
   * @author CommSy Development Group
   */
   function setGroupList ($value) {
      $this->_setObject(CS_GROUP_TYPE, $value, FALSE);
   }

  /** set groups of a todo item by id
   * this method sets a list of group item_ids which are linked to the todo
   *
   * @param array of group ids, index of id must be 'iid'<br />
   * Example:<br />
   * array(array('iid' => value1), array('iid' => value2))
   *
   * @author CommSy Development Group
   */
   function setGroupListByID ($value) {
      $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
   }

  /** get groups of a todo
   * this method returns a list of groups which are linked to the todo
   *
   * @return object cs_list a list of groups (cs_label_item)
   *
   * @author CommSy Development Group
   */
   function getGroupList () {
      $group_manager = $this->_environment->getLabelManager();
      $group_manager->setTypeLimit(CS_GROUP_TYPE);
      return $this->_getLinkedItems($group_manager, CS_GROUP_TYPE);
   }

  /** set materials of the todo item
   * this method sets a list of materials which are linked to the todo item
   *
   * @param cs_list list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

  /** set materials of a todo item by id
   * this method sets a list of group item_ids which are linked to the todo
   *
   * @param array of group ids, index of id must be 'iid', index of version must be 'vid'<br />
   * Example:<br />
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
   }

  /** get materials of the todo item
   * this method returns a list of materials which are linked to the todo
   *
   * @return object cs_list a list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }



   function setTopicList ($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   function setTopicListByID ($value) {
      $this->setLinkedItemsByID(CS_TOPIC_TYPE, $value);
   }

   function getTopicList () {
      $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }


   function getProcessorItemList(){
      $members = new cs_list();
      $member_ids = $this->getLinkedItemIDArray(CS_USER_TYPE);
      if ( !empty($member_ids) ){
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setIDArrayLimit($member_ids);
         $user_manager->select();
         $members = $user_manager->get();
      }        // returns a cs_list of user_items
      return $members;
   }

   function isProcessor($user) {
      $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
      $link_member_item = $link_member_list->getFirst();
      $is_member = false;
      while ( $link_member_item ) {
         $linked_user_id = $link_member_item->getLinkedItemID($this);
         if ( $user->getItemID() == $linked_user_id ) {
            $is_member = true;
            break;
         }
         $link_member_item = $link_member_list->getNext();
      }
      return $is_member;
   }

   function addProcessor ($user) {
      if ( !$this->isProcessor($user) ) {
         $link_manager = $this->_environment->getLinkItemManager();
         $link_item = $link_manager->getNewItem();
         $link_item->setFirstLinkedItem($this);
         $link_item->setSecondLinkedItem($user);
         $link_item->save();
      }
   }

   function removeProcessor ($user) {
      $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
      $link_member_item = $link_member_list->getFirst();
      while ( $link_member_item ) {
         $linked_user_id = $link_member_item->getLinkedItemID($this);
         if ( $user->getItemID() == $linked_user_id ) {
            $link_member_item->delete();
         }
         $link_member_item = $link_member_list->getNext();
      }
   }


  /** save todo item
   * this methode save the todo item into the database
   *
   * @author CommSy Development Group
   */
   function save() {
      $todo_manager = $this->_environment->getTodosManager();
      $this->_save($todo_manager);
      $this->_saveFiles();     // this must be done before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
   }

  /** delete todo item
   * this methode delete the todo item
   *
   * @author CommSy Development Group
   */
   function delete() {
      $todo_manager = $this->_environment->getTodosManager();
      $this->_delete($todo_manager);
   }

   /** Checks and sets the data of the todo_item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      // TBD: check data before setting
       $this->_data = $data_array;
   }

   /** asks if item is editable by everybody or just creator
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function isPublic() {
      if ($this->_getValue('public')== 1) {
         return true;
      } else {
        return false;
      }
   }

   /** sets if announcement is editable by everybody or just creator
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function setPublic ($value) {
      $this->_setValue('public', $value);
   }

   function copy() {
      $copy = $this->cloneCopy();
      $copy->setItemID('');
      $copy->setFileList($this->_copyFileList());
      $copy->setContextID($this->_environment->getCurrentContextID());
      $user = $this->_environment->getCurrentUserItem();
      $copy->setCreatorItem($user);
      $copy->setModificatorItem($user);
      $list = new cs_list();
      $copy->setGroupList($list);
#      $copy->setInstitutionList($list);
      $copy->setTopicList($list);
      $copy->save();
      return $copy;
   }

   function cloneCopy() {
      $clone_item = clone $this; // "clone" needed for php5
      $group_list = $this->getGroupList();
      $clone_item->setGroupList($group_list);
#      $institution_list = $this->getInstitutionList();
#      $clone_item->setInstitutionList($institution_list);
      $topic_list = $this->getTopicList();
      $clone_item->setTopicList($topic_list);
      return $clone_item;
   }

}
?>