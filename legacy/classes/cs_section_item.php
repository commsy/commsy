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

/** upper class of the section item
 */
include_once('classes/cs_item.php');

/** class for a section
 * this class implements a section item
 */
class cs_section_item extends cs_item {

   /** constructor: cs_section_item
   * the only available constructor, initial values for internal variables
   */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'section';

   }

   var $_version_id_changed = false;

   var $_oldnumber = NULL;

   /** get title of a section
   * this method returns the title of the section
   *
   * @return string title of a section
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

   /** set title of a section
   * this method sets the title of the section
   *
   * @param string value title of the section
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
   function getLinkedItemID(){
      return $this->_getValue('material_item_id');
   }

    /**
     * @return null|\cs_material_item
     */
   function getLinkedItem () {
     $retour = NULL;
     $item_id = $this->getLinkedItemID();
     if (!empty($item_id)) {
       $type_manager = $this->_environment->getManager(CS_MATERIAL_TYPE);
       $retour = $type_manager->getItem($item_id);
     }
     return $retour;
   }

   /** set id of a linked material
   *
   *
   * @author CommSy Development Group
   */
   function setLinkedItemID($value){
      $this->_setValue('material_item_id', $value);

   }

   /** get description of a section
   * this method returns the description of the section
   *
   * @return string description of a section
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

   /** set description of a section
   * this method sets the description of the section
   *
   * @param string value description of the section
   *
   * @author CommSy Development Group
   */
   function setDescription($description) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $description = $converter->sanitizeFullHTML($description);
      $this->_setValue('description', $description);

   }

   /** get version id of a material
    * this method returns the version id of the material
    *
    * @return int version of the material
    *
    * @author CommSy Development Group
    */
   function getVersionID () {
      return $this->_getValue('version_id');
   }

   /** set version id of a material
    * this method sets the version id of the material WITH marking the version id as 'changed'.
    * This is for loading initial values into the item
    *
    * @return boolean true: version id has changed -> new version of material
    *                 false: some version of material
    *
    * @author CommSy Development Group
    */
   function setVersionID ($value) {
      $this->_version_id_changed = true;
      $this->_setValue('version_id',$value);

   }

   /** is the material a new version ???
    * this method returns a boolean whether it is an new version or not
    * This is for loading initial values into the item
    *
    * @param string value title of the material
    *
    * @author CommSy Development Group
    */
   function newVersion () {
     $this->_version_id_changed = true;


   }

   /** set materials of the section item
   * this method sets a list of materials which are linked to the section item
   *
   * @param cs_list list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function setMaterialList ($value) {
      $this->_setObject('CS_MATERIAL_TYPE', $value, FALSE);

   }

   /** set materials of a section item by id
   * this method sets a list of group item_ids which are linked to the section
   *
   * @param array of group ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      //$this->_setValue('material_for', $value, FALSE);
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);

   }

   /** get materials of the section item
   * this method returns a list of materials which are linked to the section
   *
   * @return object cs_list a list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

    public function getNumber(): int
    {
        return (int) $this->_getValue('number');
    }

   function getOldNumber(){
     return $this->_getValue('oldnumber');
   }


   /** set groups of a section
    * this method sets a list of groups which are linked to the section
    *
    * @param string value title of the section
    *
    * @author CommSy Development Group
    */
   function setNumber ($value) {
     $this->_setValue('oldnumber',$this->getNumber());
     $this->_setValue('number', $value);
   }

   /**
   save
   */
   function save() {
      $section_manager = $this->_environment->getSectionManager();
      $this->_save($section_manager);
      $this->_saveFiles();     // this must be before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
   }

   function save_without_date() {
      $section_manager = $this->_environment->getSectionManager();
      $section_manager->setSaveSectionWithoutDate();
      $this->_save($section_manager);
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
   }

   function delete ($version = '') {
      $section_manager = $this->_environment->getSectionManager();
      if ( !empty($version) and $version == 'current' ) {
         $section_manager->delete($this->getItemID(),$this->getVersionID());
      } elseif ( isset($version)
                 and $version != CS_ALL
                 and is_int((int)$version)
               ) {
         $section_manager->delete($this->getItemID(),$version);
      } else {
         $section_manager->delete($this->getItemID());
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
      $section_manager = $this->_environment->getSectionManager();
      $section_manager->delete($this->getItemID(),$this->getVersionID());
   }

    /** Checks and sets the data of the section_item.
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