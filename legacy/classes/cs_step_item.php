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

/** upper class of the step item
 */
include_once('classes/cs_item.php');

/** class for a step
 * this class implements a step item
 */
class cs_step_item extends cs_item {

   /** constructor: cs_step_item
   * the only available constructor, initial values for internal variables
   */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'step';

   }

   var $_version_id_changed = false;


   /** get title of a step
   * this method returns the title of the step
   *
   * @return string title of a step
   *
   * @author CommSy Development Group
   */
   function getTitle () {
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
   	  }else{
         return $this->_getValue('title');
   	  }
   }

   /** set title of a step
   * this method sets the title of the step
   *
   * @param string value title of the step
   *
   * @author CommSy Development Group
   */
   function setTitle($title) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $title = htmlentities($title);
   	  $title = $converter->sanitizeHTML($title);
      $this->_setValue('title', $title);

   }

   /** get id of a linked material
   *
   * @return int id of a material
   *
   * @author CommSy Development Group
   */
   function getTodoID(){
      return $this->_getValue('todo_item_id');
   }

   function getLinkedItem () {
     $retour = NULL;
     $item_id = $this->getTodoID();
     if (!empty($item_id)) {
       $type_manager = $this->_environment->getManager(CS_TODO_TYPE);
       $retour = $type_manager->getItem($item_id);
     }
     return $retour;
   }

    function getLinkedItemId () {
        return $this->getLinkedItem()->getItemId();
    }

   /** set id of a linked material
   *
   *
   * @author CommSy Development Group
   */
   function setTodoID($value){
      $this->_setValue('todo_item_id', $value);

   }

   function setTimeType($type) {
      $this->_setValue('time_type', $type);
   }

   function getTimeType() {
      $retour = 1;
      $r = $this->_getValue('time_type');
      if (isset($r) and !empty($r)){
         $retour = $r;
      }
      return $retour;
   }


   /** get description of a step
   * this method returns the description of the step
   *
   * @return string description of a step
   *
   * @author CommSy Development Group
   */
   function getDescription () {
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
   	  }else{
         return $this->_getValue('description');
   	  }
   }

   /** set description of a step
   * this method sets the description of the step
   *
   * @param string value description of the step
   *
   * @author CommSy Development Group
   */
   function setDescription($description) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $description = $converter->sanitizeFullHTML($description);
      $this->_setValue('description', $description);
   }

   /** set materials of the step item
   * this method sets a list of materials which are linked to the step item
   *
   * @param cs_list list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function setMaterialList ($value) {
      $this->_setObject('CS_MATERIAL_TYPE', $value, FALSE);

   }

   /** set materials of a step item by id
   * this method sets a list of group item_ids which are linked to the step
   *
   * @param array of group ids, index of id must be 'iid', index of version must be 'vid'<br />
   * Example:<br />
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      //$this->_setValue('material_for', $value, FALSE);
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);

   }

   /** get materials of the step item
   * this method returns a list of materials which are linked to the step
   *
   * @return object cs_list a list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

   /** get groups of a step
    * this method returns a list of groups which are linked to the step
    *
    * @return object cs_list a list of groups (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function getMinutes() {
      return $this->_getValue('minutes');
   }

   function setMinutes($min) {
      return $this->_setValue('minutes',(int)$min);
   }

   function cloneCopy() {
      $clone_item = clone $this; // "clone" needed for php5
      return $clone_item;
   }


   /**
   save
   */
   function save() {
      $step_manager = $this->_environment->getStepManager();
      $this->_save($step_manager);
      $this->_saveFiles();     // this must be before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
   }

   function save_without_date() {
      $step_manager = $this->_environment->getStepManager();
      $step_manager->setSaveStepWithoutDate();
      $this->_save($step_manager);
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
   }

   function delete ($version = '') {
      $step_manager = $this->_environment->getStepManager();
      if ( !empty($version) and $version == 'current' ) {
         $step_manager->delete($this->getItemID(),$this->getVersionID());
      } elseif ( isset($version)
                 and $version != CS_ALL
                 and is_int((int)$version)
               ) {
         $step_manager->delete($this->getItemID(),$version);
      } else {
         $step_manager->delete($this->getItemID());
      }

      // delete links
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

      // delete links to files
      $link_manager = $this->_environment->getLinkItemFileManager();
      if ( !empty($version) and $version == 'current' ) {
         $link_manager->deleteByItem($this->getItemID(),$this->getVersionID());
      } elseif ( isset($version)
                 and $version != CS_ALL
                 and is_int((int)$version)
               ) {
         $link_manager->deleteByItem($this->getItemID(),$version);
      } else {
         $link_manager->deleteByItem($this->getItemID());
      }
   }

   function deleteVersion() {
      $step_manager = $this->_environment->getStepManager();
      $step_manager->delete($this->getItemID(),$this->getVersionID());
   }

    /** Checks and sets the data of the step_item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      // TBD: check data before setting
       $this->_data = $data_array;
   }

    function isLocked() {
        return $this->getLinkedItem()->isLocked();
    }
}