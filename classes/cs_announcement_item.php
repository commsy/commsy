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

/** upper class of the announcement item
 */
include_once('classes/cs_item.php');

/** class for a announcement
 * this class implements a announcement item
 */
class cs_announcement_item extends cs_item {

   /** constructor: cs_announcement_item
    * the only available constructor, initial values for internal variables
    */
   function cs_announcement_item ($environment) {
      $this->cs_item($environment);
      $this->_type = CS_ANNOUNCEMENT_TYPE;
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


   /** get title of an announcement
    * this method returns the title of the announcement
    *
    * @return string title of an announcement
    *
    * @author CommSy Development Group
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set title of an announcement
    * this method sets the title of the announcement
    *
    * @param string value title of the announcement
    *
    * @author CommSy Development Group
    */
   function setTitle ($value) {
      $this->_setValue('title', $value);
   }

   /** get description of an announcement
    * this method returns the description of the announcement
    *
    * @return string description of an announcement
    *
    * @author CommSy Development Group
    */
   function getDescription () {
      return $this->_getValue('description');
   }

   /** set description of an announcement
    * this method sets the description of the announcement
    *
    * @param string value description of the announcement
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
      $this->_setValue('description', $value);
   }

   /** set setfirstdate of an announcement
    * this method sets the setfirstdate of the announcement
    *
    * @param date value setfirstdate of the announcement
    *
    * @author CommSy Development Group
    */
   function setFirstDateTime ($value) {
      $this->_setValue('creation_date', $value);
   }

   /** get description of an announcement
    * this method returns the description of the announcement
    *
    * @return string description of an announcement
    *
    * @author CommSy Development Group
    */
   function getFirstDateTime () {
      return $this->_getValue('creation_date');
   }

   /** set setfirstdate of an announcement
    * this method sets the setfirstdate of the announcement
    *
    * @param date value setfirstdate of the announcement
    *
    * @author CommSy Development Group
    */
   function setSecondDateTime ($value) {
      $this->_setValue('enddate', $value);
   }

   /** get description of an announcement
    * this method returns the description of the announcement
    *
    * @return string description of an announcement
    *
    * @author CommSy Development Group
    */
   function getSecondDateTime () {
      return $this->_getValue('enddate');
   }

   /** get topics of a announcement
    * this method returns a list of topics which are linked to the announcement
    *
    * @return object cs_list a list of topics (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function getTopicList() {
      $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }

  /** set topics of a announcement item by id
   * this method sets a list of topic item_ids which are linked to the announcement
   *
   * @param array of topic ids
   *
   * @author CommSy Development Group
   */
   function setTopicListByID ($value) {
      $topic_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $topic_array[] = $tmp_data;
      }
      $this->_setValue(CS_TOPIC_TYPE, $topic_array, FALSE);
   }

   /** set topics of a announcement
    * this method sets a list of topics which are linked to the announcement
    *
    * @param object cs_list value list of topics (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** get institutions of a announcement
    * this method returns a list of institutions which are linked to the announcement
    *
    * @return object cs_list a list of institutions (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function getInstitutionList() {
      $institution_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_INSTITUTION_TYPE);
      $institution_list->sortBy('name');
      return $institution_list;
   }

  /** set institutions of a announcement item by id
   * this method sets a list of institution item_ids which are linked to the announcement
   *
   * @param array of institution ids
   *
   * @author CommSy Development Group
   */
   function setInstitutionListByID ($value) {
      $this->setLinkedItemsByID (CS_INSTITUTION_TYPE, $value);
   }

   /** set institutions of a announcement
    * this method sets a list of institutions which are linked to the announcement
    *
    * @param object cs_list value list of institutions (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setInstitutionList($value) {
      $this->_setObject(CS_INSTITUTION_TYPE, $value, FALSE);
   }

  /** set groups of a announcement item by id
   * this method sets a list of group item_ids which are linked to the announcement
   *
   * @param array of group ids
   *
   * @author CommSy Development Group
   */
   function setGroupListByID ($value) {
      $this->setLinkedItemsByID (CS_GROUP_TYPE, $value);
   }

   /** set groups of a announcement
    * this method sets a list of groups which are linked to the announcement
    *
    * @param object cs_list value list of groups (cs_label_item)
    */
   function setGroupList($value) {
      $this->_setObject(CS_GROUP_TYPE, $value, FALSE);
   }

   /** get materials of a announcement
    * this method returns a list of materials which are linked to the announcement
    *
    * @return object cs_list a list of materials (cs_material_item)
    */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

  /** set materials of a announcement item by item id and version id
   * this method sets a list of material item_ids and version_ids which are linked to the announcement
   *
   * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID (CS_MATERIAL_TYPE, $value);
   }

   /** set materials of a announcement
    * this method sets a list of materials which are linked to the news
    *
    * @param string value title of the news
    */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

   function save() {
      $announcement_manager = $this->_environment->getAnnouncementManager();
      $this->_save($announcement_manager);
      $this->_saveFiles();     // this must be done before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id
   }

   /** delete announcement
    * this method deletes the announcement
    */
   function delete() {
      $manager = $this->_environment->getAnnouncementManager();
      $this->_delete($manager);
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

   function getGroupList () {
      $group_manager = $this->_environment->getLabelManager();
      $group_manager->setTypeLimit(CS_GROUP_TYPE);
      return $this->_getLinkedItems($group_manager, CS_GROUP_TYPE);
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
      $copy->setInstitutionList($list);
      $copy->setTopicList($list);
      $copy->save();
      return $copy;
   }

   function cloneCopy() {
      $clone_item = clone $this; // "clone" needed for php5
      $group_list = $this->getGroupList();
      $clone_item->setGroupList($group_list);
      $institution_list = $this->getInstitutionList();
      $clone_item->setInstitutionList($institution_list);
      $topic_list = $this->getTopicList();
      $clone_item->setTopicList($topic_list);
      return $clone_item;
   }
}
?>