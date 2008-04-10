<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

/** upper class of the material item
 */
include_once('classes/cs_item.php');

/** class for object lists
 */
include_once('classes/cs_list.php');
include_once('classes/cs_section_list.php');

/** class for a material
 * this class implements a material item
 */
class cs_material_item extends cs_item {

  /**
   * boolean - containing boolean for make new version or not
   */
   var $_version_id_changed = false;

   /**
   * integer - which of the sections will be saved with new date
   */
   var $_section_save_id = 0;

   /**
   * integer - list of items attached to material
   */
   var $_attached_item_list = NULL;



   function cs_material_item ($environment) {
      $this->cs_item($environment);
      $this->_type = CS_MATERIAL_TYPE;
   }


###############  SET-METHODS



   function setCopyItem($item){
      $this->_setValue("copy_of", $item->getItemID());
   }

   function getCopyItem(){
      $copy_id = $this->_getValue("copy_of");
      if (!empty($copy_id)){
         $material_manager = $this->_environment->getMaterialManager();
         $copy_item = $material_manager->getItem($copy_id);
      }else{
         $copy_item = NULL;
      }
      return $copy_item;
   }

   /** set title of a material
    * this method sets the title of the material and marks the title as 'changed'
    *
    * @param string value title of the material
    */
   function setTitle ($value) {
      $this->_setValue('title',$value);
   }

   /** set Author of a material
    *
    * This is for loading initial values into the item
    *
    * @param string value author of the material
    */
   function setAuthor ($value) {
      $this->_setValue('author',$value);
   }


   /** set publishing_date of a material
    *
    * @param string value publishing_date of the material
    * @author CommSy Development Group
    */
   function setPublishingDate ($value) {
      $this->_setValue('publishing_date',$value);
   }

   /** set bibliographic values of a material
    * this method sets the bibliographic values of the material an marks the them as 'changed'
    *
    * @param string value bibliographic values of the material
    *
    * @author CommSy Development Group
    */
   function setBibliographicValues($value){
      $this->_addExtra('BIBLIOGRAPHIC',(string)$value);
   }


/** The following methods are for detailed bib values **/

   function setBibKind($value) {
      $this->_addExtra('BIB_KIND', (string)$value);
   }
   function getBibKind() {
      return (string) $this->_getExtra('BIB_KIND');
   }

   function setPublisher($value) {
      $this->_addExtra('BIB_PUBLISHER', (string)$value);
   }
   function getPublisher() {
      return (string) $this->_getExtra('BIB_PUBLISHER');
   }

   function setAddress($value) {
      $this->_addExtra('BIB_ADDRESS', (string)$value);
   }
   function getAddress() {
      return (string) $this->_getExtra('BIB_ADDRESS');
   }

   function setEdition($value) {
      $this->_addExtra('BIB_EDITION', (string)$value);
   }
   function getEdition() {
      return (string) $this->_getExtra('BIB_EDITION');
   }

   function setSeries($value) {
      $this->_addExtra('BIB_SERIES', (string)$value);
   }
   function getSeries() {
      return (string) $this->_getExtra('BIB_SERIES');
   }

   function setVolume($value) {
      $this->_addExtra('BIB_VOLUME', (string)$value);
   }
   function getVolume() {
      return (string) $this->_getExtra('BIB_VOLUME');
   }

   function setISBN($value) {
      $this->_addExtra('BIB_ISBN', (string)$value);
   }
   function getISBN() {
      return (string) $this->_getExtra('BIB_ISBN');
   }

   function setISSN($value) {
      $this->_addExtra('BIB_ISSN', (string)$value);
   }
   function getISSN() {
      return (string) $this->_getExtra('BIB_ISSN');
   }

   function setEditor($value) {
      $this->_addExtra('BIB_EDITOR', (string)$value);
   }
   function getEditor() {
      return (string) $this->_getExtra('BIB_EDITOR');
   }

   function setBooktitle($value) {
      $this->_addExtra('BIB_BOOKTITLE', (string)$value);
   }
   function getBooktitle() {
      return (string) $this->_getExtra('BIB_BOOKTITLE');
   }

   function setPages($value) {
      $this->_addExtra('BIB_PAGES', (string)$value);
   }
   function getPages() {
      return (string) $this->_getExtra('BIB_PAGES');
   }

   function setJournal($value) {
      $this->_addExtra('BIB_JOURNAL', (string)$value);
   }
   function getJournal() {
      return (string) $this->_getExtra('BIB_JOURNAL');
   }

   function setIssue($value) {
      $this->_addExtra('BIB_ISSUE', (string)$value);
   }
   function getIssue() {
      return (string) $this->_getExtra('BIB_ISSUE');
   }

   function setThesisKind($value) {
      $this->_addExtra('BIB_THESIS_KIND', (string)$value);
   }
   function getThesisKind() {
      return (string) $this->_getExtra('BIB_THESIS_KIND');
   }

   function setUniversity($value) {
      $this->_addExtra('BIB_UNIVERSITY', (string)$value);
   }
   function getUniversity() {
      return (string) $this->_getExtra('BIB_UNIVERSITY');
   }

   function setFaculty($value) {
      $this->_addExtra('BIB_FACULTY', (string)$value);
   }
   function getFaculty() {
      return (string) $this->_getExtra('BIB_FACULTY');
   }

   function setURL($value) {
      $this->_addExtra('BIB_URL', (string)$value);
   }
   function getURL() {
      return (string) $this->_getExtra('BIB_URL');
   }

   function setURLDate($value) {
      $this->_addExtra('BIB_URL_DATE', (string)$value);
   }
   function getURLDate() {
      return (string) $this->_getExtra('BIB_URL_DATE');
   }

/** End: detailed bib values **/


   /** set description of a material
    * this method sets the description of the material an marks it as 'changed'
    *
    * @param string value description of the material
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
      $this->_setValue('description',$value);
   }

   /** set version id of a material
    * this method sets the version id of the material WITH marking the version id as 'changed'.
    * This is for loading initial values into the item
    *
    * @param integer version ID
    *
    * @author CommSy Development Group
    */
   function setVersionID ($value) {
      $this->_setValue('version_id',$value);
      $this->_version_id_changed = TRUE; // needed in material_manager to determine wether to save as new item
   }

  /** set label item-id of a material
    * this method sets the item id of the label for this material
    *
    * @param string value title of the material
    *
    * @author CommSy Development Group
    */
   function setLabelID ($value) {
      $this->_setValueAsID('label_for', $value);
      $this->_data['label'] = '';
   }

  /** set label of a material
    * this method sets the label of the material
    *
    * @param string value title of the material
    *
    * @author CommSy Development Group
    */
   function setLabel ($value) {
      $this->_data['label'] = $value;
      $this->_data['label_for'] = '';
   }

   /** set buzzwords of a material
    * this method sets a list of buzzwords which are linked to the material
    *
    * @param string value title of the material
    *
    * @author CommSy Development Group
    */
   function setBuzzwordArray($value) {
      $this->_data['buzzword_array'] = $value;
   }


   function setFileIDArray ($value) {
      $this->_data['file_id_array'] = $value;
      $this->_data['file_list'] = NULL;
      $this->_filelist_changed = TRUE;
    }

   function setWorldPublic($value){
      $this->_setValue('world_public',(int)$value);
   }


   function setSectionList($value){
     $this->_setObject('section_for',$value,FALSE);
   }

   function isWorldPublic () {  //TBD
      $value = $this->getWorldPublic();
      if ($value == 2) {
         return TRUE;
      }
      return FALSE;
   }

################ GET-METHODS


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


   /** get title of a material
    * this method returns the title of the material
    *
    * @return string title of a material
    *
    * @author CommSy Development Group
    */
   function getTitle () {
      return (string) $this->_getValue('title');
   }

   /** get author of a material
    * this method returns the author of the material
    *
    * @return string author of a material
    * @author CommSy Development Group
    */
   function getAuthor () {
      return (string) $this->_getValue('author');
   }

   /** get publishing_date of a material
    * this method returns the publishing_date of the material
    *
    * @return string publishing_date of a material
    *
    * @author CommSy Development Group
    */
   function getPublishingDate () {
      return (int) $this->_getValue('publishing_date');
   }

   /** get bibliographic values of a material
    * this method gets the bibliographic values of the material
    *
    * @return string bibliographic values of the material
    *
    * @author CommSy Development Group
    */
   function getBibliographicValues(){
      return (string) $this->_getExtra('BIBLIOGRAPHIC');
   }

   /** get description of a material
    * this method returns the description of the material
    *
    * @return string description of a material
    *
    * @author CommSy Development Group
    */
   function getDescription () {
      return (string) $this->_getValue('description');
   }

      /** get projects of a material
    * this method returns a list of projects which are linked to the material
    *
    * @return object cs_list a list of projects (cs_label_item)
    */
   function getProjectList() {
      return $this->getLinkedItemList(CS_PROJECT_TYPE);
   }

  /** set projects of a material item by id
   * this method sets a list of project item_ids which are linked to the material
   *
   * @param array of project ids
   *
   * @author CommSy Development Group
   */
   function setProjectListByID ($value) {
      $project_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $project_array[] = $tmp_data;
      }
      $this->_setValue(CS_PROJECT_TYPE, $project_array, FALSE);
   }

   /** set projects of a material
    * this method sets a list of projects which are linked to the material
    *
    * @param object cs_list value list of projects (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setProjectList($value) {
      $this->_setObject(CS_PROJECT_TYPE, $value, FALSE);
   }


   /** get label of a material
    * this method returns the label of the material
    *
    * @return string label
    *
    * @author CommSy Development Group
    */
   function getLabel () {
      $label = $this->_getValue('label');
      if(empty($label)) {
         $label_item = $this->getLabelItem();
         if(!empty($label_item) and is_object($label_item)) {
            $this->_data['label'] = $label_item->getName();
         }
      }
      return (string) $this->_getValue('label');
   }

   /** get label item of a material
    * this method returns the label of the material
    *
    * @return cs_label_item
    *
    * @author CommSy Development Group
    */
   function getLabelItem () {
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->setContextLimit($this->getContextID());
      $label_manager->setTypeLimit('label');
      $label_list = $this->_getLinkedItemsForCurrentVersion($label_manager, 'label_for');
      $retour = NULL;
      if ($label_list->getCount() > 0) {
          $retour =  $label_list->getFirst();
      }
      return $retour;
   }

    /** get tasks associated with a material
    * this method returns a list of tasks which are linked to the material
    *
    * @return object cs_list a list of tasks
    *
    * @author CommSy Development Group
    */
   function _getTaskList () {
      $task_manager = $this->_environment->getTaskManager();
      return $task_manager->getTaskListForItem($this);
   }

   function getWorldPublic(){
      return $this->_getValue('world_public');
   }


   function getSectionList() {
      $section_list = $this->_getValue('section_for');
      if (empty($section_list) ) {
         $this->_data['section_for'] = $this->_getSectionListForCurrentVersion();
         $section_list = $this->_data['section_for'];
      }
      return $section_list;
   }


##########################
##########################
#########TESTING##########
##########################
##########BEGIN###########

#   function _getLinkedItems($item_manager, $link_type, $order='', $ignore_version = FALSE ) {
#       if ( $link_type != 'annotation_of' )  {
#          return parent::_getLinkedItems($item_manager, $link_type, $order='');
#       }
#    }


   function _getSectionListForCurrentVersion(){
      $section_manager = $this->_environment->getSectionManager();
      $this->_data['section_for'] = $section_manager->getSectionForCurrentVersion($this);
      return $this->_data['section_for'];
   }

   function getAnnotationList() {
      $annotation_manager = $this->_environment->getAnnotationManager();
      $annotation_manager->reset();
      $annotation_manager->setLinkedItemID($this->getItemID());
#      $annotation_manager->setLinkedVersionID($this->getVersionID());
      $annotation_manager->select();
      return $annotation_manager->get();
   }

  function selectAttachedItems(){
      $link_manager = $this->_environment->getLinkManager();
      $link_array = $link_manager->getLinksFromWithItemType('material_for', $this);#, $this->getVersionID());
      $id_array = array();
      foreach($link_array as $link){
         $id_array[$link['type']][] = $link['to_item_id'];
      }
      while(list($type, $id_list) = each($id_array)) {
         $manager = $this->_environment->getManager($type);
         $this->_attached_item_list[$type] = $manager->getItemList($id_list);
      }
   }

   function getAttachedNewsList(){
      return $this->_getAttachedItemList('news');
   }

   function getAttachedDateList(){
      return $this->_getAttachedItemList('date');
   }

   function getAttachedDiscussionArticleList() {
      return $this->_getAttachedItemList('discarticles');
   }

   function getAttachedSectionList() {
      return $this->_getAttachedItemList('section');
   }

   function getAttachedAnnouncementList() {
      return $this->_getAttachedItemList(CS_ANNOUNCEMENT_TYPE);
   }

   function _getAttachedItemList($type) {
      return isset($this->_attached_item_list[$type]) ? $this->_attached_item_list[$type] : NULL;
   }


###########END############
##########################
#########TESTING##########
##########################
##########################


################ SAVING


   function save($mode='') {
      $this->_saveLabel();
      $this->_saveSections($mode);
      $this->_saveFiles();
      $material_manager = $this->_environment->getMaterialManager();
      $this->_save($material_manager);
      $this->_saveFileLinks(); // this must be done after saving material so we can be sure to have a material id
      $this->_filelist_changed = false;
      $this->_version_id_changed = false;
      $this->_changed = array();
   }

   function _saveBuzzwords() {
      if ( !isset($this->_setBuzzwordsByIDs) ) {
         $buzzword_array = $this->getBuzzwordArray();
         if (!empty($buzzword_array)) {
            array_walk($buzzword_array,create_function('$buzzword','return trim($buzzword);'));
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->resetLimits();
            $label_manager->setTypeLimit('buzzword');
            $label_manager->setContextLimit($this->getContextID());
            $buzzword_exists_id_array = array();
            $buzzword_not_exists_name_array = array();
            foreach ($buzzword_array as $buzzword) {
               $buzzword_item = $label_manager->getItemByName($buzzword);
               if (!empty($buzzword_item)) {
                  $buzzword_exists_id_array[] = array('iid' => $buzzword_item->getItemID());
               } else {
                  $buzzword_not_exists_name_array[] = $buzzword;
               }
            }
            // make buzzword items to get ids
            if (count($buzzword_not_exists_name_array) > 0) {
               foreach($buzzword_not_exists_name_array as $new_buzzword) {
                  $item = $label_manager->getNewItem();
                  $item->setContextID($this->getContextID());
                  $item->setName($new_buzzword);
                  $item->setLabelType('buzzword');
                  $item->save();
                  $buzzword_exists_id_array[] = array('iid' => $item->getItemID());
               }
            }
            // set id array so the links to the items get saved
            $this->_setValue('buzzword_for', $buzzword_exists_id_array, FALSE);
         } else {
            $this->_setValue('buzzword_for', array(), FALSE); // to unset buzzword links
         }
      }
   }

   function setBuzzwordListByID($array){
      $this->_setValue('buzzword_for', $array, FALSE);
      $this->_setBuzzwordsByIDs = true;
   }

   function _saveLabel() {
      $id = $this->_getValue('label_for');
      $no_id = empty($id);
      if($no_id) {
         $label = $this->getLabel();
         if(!empty($label)) {
            // create new label_item and save it
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->setContextLimit($this->getContextID());
            $label_manager->setTypeLimit('label');
            $label_item = $label_manager->getItemByName($label);
            if(empty($label_item)) {
               $label_item = $label_manager->getNewItem();
               $label_item->setContextID($this->getContextID());
               $label_item->setCreatorItem($this->getCreatorItem());
               $label_item->setName($label);
               $label_item->setLabelType('label');
               $label_item->save();
            }
            // set label id so the link to the label gets saved
            $this->setLabelID($label_item->getItemID());
         }
      }
   }

   function _saveSections ($mode = '') {
      $error_array_sum = array();
      if ( isset($this->_changed['section_for']) ) {
         $this->setModificationDate(NULL);
         $section_list = $this->getSectionList();
         if ( $section_list->getCount() > 0 ) {
            $new_section_list = new cs_section_list();
            $section = $section_list->getFirst();
            $error_array_sum = $this->GetErrorArray();
            while ( $section ) {
               $section_id = $section->getItemID();
               $file_id_array = $section->getFileIDArray();
               $file_list = $section->getFileList();
               if ( $section->getContextID() != $this->getContextID() ) {
                  $section->setContextID($this->getContextID());
               }
               if ( $section->getVersionID() != $this->getVersionID() ) {
                  $section->setVersionID($this->getVersionID());
               }

               // set files new, so they will be saved for the new version
               // and for copiing in new rooms
               if ( $mode == 'copy' ) {
                  $section->setFileList($file_list);
               } elseif ( isset($file_id_array) ) {
                  $section->setFileIDArray($file_id_array);
               }
               unset($file_list);
               unset($file_id_array);

               //just set the date new at the modified section... all others keep their old date
               if ( $section->getItemId() == $this->_section_save_id ) {
                  $section->save();
                  $error_array = $section->getErrorArray();

                  // Error merging with values from material-copy
                  if ( !empty($error_array) ) {
                     if (isset($error_array_sum) and !empty($error_array_sum)) {
                        $error_array_sum = array_merge($error_array_sum,$error_array);
                     } else {
                        $error_array_sum = $error_array;
                     }
                     $this->SetErrorArray($error_array_sum);
                  }
               } else {
                  $section->save_without_date();
               }
               unset($error_array);
               unset($error_array_sum);
               $new_section_list->append($section);
               $section = $section_list->getNext();
            }
            $this->setSectionList($new_section_list);
         }
      }
   }

########################### DELETING

   /** delete material
    * this method deletes the material
    */
   function delete ($version = "current") {
      // delete associated tasks
      $task_list = $this->_getTaskList();
      if (isset($task_list)) {
         $current_task = $task_list->getFirst();
         while ($current_task) {
            $current_task->delete();
            $current_task = $task_list->getNext();
         }
      }

      // delete sections
      $section_list = $this->getSectionList();
      if ( $section_list->isNotEmpty() ) {
         $section_item = $section_list->getFirst();
         while ($section_item) {
            if ( $version == 'current' ) {
               $section_item->delete($this->getVersionID());
            } else {
               $section_item->delete();
            }
            $section_item = $section_list->getNext();
         }
      }

      // delete material with versions
      $material_manager = $this->_environment->getMaterialManager();
      if ( $version == "current" ) {
         $material_manager->delete($this->getItemID(),$this->getVersionID());
      } else {
         $material_manager->delete($this->getItemID());
      }

      // delete links
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

      // delete links to files
      $link_manager = $this->_environment->getLinkItemFileManager();
      $link_manager->deleteByItem($this->getItemID(),$this->getVersionID());
   }

   /** delete a version of a material
    * this method deletes a version of a material
    *
    * @author CommSy Development Group
    */
   function deleteAllVersions() {
      $this->delete("all");
   }



########################### COPYING AND CLONING

function copy () {
   $copy = $this->cloneCopy(true);
   $copy->setItemID('');
   $copy->setFileList($this->_copyFileList());
   $copy->setCopyItem($this);
   $copy->setContextID($this->_environment->getCurrentContextID());
   $user = $this->_environment->getCurrentUserItem();
   $copy->setCreatorItem($user);
   $copy->setModificatorItem($user);
   $list = new cs_list();
   if ( $this->_environment->getCurrentContextID() != $this->getContextID() ){
      // Add a new labels if necessary
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->reset();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit('label');
      $label_manager->select();
      $label_list = $label_manager->get();
      $exist = NULL;
      if ( !empty($label_list) ) {
        $label = $label_list->getFirst();

         while ( $label ) {
           if ( strcmp($label->getName(), ltrim($this->getLabel())) == 0 ) {
             $exist = $label->getItemID();
           }
           $label = $label_list->getNext();
         }
      }
      if ( !isset($exist) ) {
         $temp_array = array();
         $label_manager = $this->_environment->getLabelManager();
         $label_manager->reset();
         $label_item = $label_manager->getNewItem();
         $label_item->setLabelType('label');
         $label_item->setTitle(ltrim($this->getLabel()));
         $label_item->setContextID($this->_environment->getCurrentContextID());
               $user = $this->_environment->getCurrentUserItem();
         $label_item->setCreatorItem($user);
         $label_item->setCreationDate(getCurrentDateTimeInMySQL());
         $label_item->save();
         $copy->setLabelId($label_item->getItemId());
      } elseif ( isset($exist) ){
        $temp_array = array();
         $label_manager = $this->_environment->getLabelManager();
         $label_manager->reset();
         $label_item = $label_manager->getItem($exist);
         $copy->setLabelId($exist);
      }

      // Add a new buzzwords if necessary
      $original_buzzword_array = $this->getBuzzwordArray();
      //Get all buzzwords in context in array
      $buzzwords_in_room_array = array();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->reset();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      if ( !empty($buzzword_list) ) {
         $buzzword = $buzzword_list->getFirst();
         while ( $buzzword ) {
           $temp_array['name'] = $buzzword->getName();
           $temp_array['id'] = $buzzword->getItemId();
           $buzzwords_in_room_array[] = $temp_array;
           $buzzword = $buzzword_list->getNext();
         }
      }

      //if buzzword exists, put id in array, if it doesn't exist, create it, then put id in array
      $buzzword_ids = array();
      if ( isset($original_buzzword_array) and
           !empty($original_buzzword_array) ) {
         for ($i=0; $i<count($original_buzzword_array); $i++) {
            $found = false;
            if ( isset($buzzwords_in_room_array) and
                 !empty($buzzwords_in_room_array) ) { //There are buzzwords in the context
         for ($j=0;($j < count($buzzwords_in_room_array));$j++) {
            if ( isset($buzzwords_in_room_array[$j]) and
                       isset($original_buzzword_array[$i]) and
                       isset($buzzwords_in_room_array[$j]['name']) and
                       !empty($buzzwords_in_room_array[$j]['name']) and
                       isset($buzzwords_in_room_array[$j]['id']) and
                       !empty($buzzwords_in_room_array[$j]['id'])
                     ) {
                     if ( strcmp($buzzwords_in_room_array[$j]['name'], ltrim($original_buzzword_array[$i])) == 0 ) {
             $buzzword_ids[] = $buzzwords_in_room_array[$j]['id'];
                  $found=true;
                        break;
               }
               if (!$found and $j==count($buzzwords_in_room_array)-1) {
             $buzzword_manager = $this->_environment->getLabelManager();
             $buzzword_manager->reset();
             $buzzword_item = $buzzword_manager->getNewItem();
             $buzzword_item->setLabelType('buzzword');
             $buzzword_item->setTitle(ltrim($original_buzzword_array[$i]));
             $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                        $user = $this->_environment->getCurrentUserItem();
             $buzzword_item->setCreatorItem($user);
             $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
             $buzzword_item->save();
             $buzzword_ids[] = $buzzword_item->getItemID();
               }
                  }
         }
      } else { //There are no buzzwords in the room, so create all
               if ( isset($original_buzzword_array[$i]) and
                    !empty($original_buzzword_array[$i])
                  ) {
            $buzzword_manager = $this->_environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_item = $buzzword_manager->getNewItem();
            $buzzword_item->setLabelType('buzzword');
            $buzzword_item->setTitle(ltrim($original_buzzword_array[$i]));
            $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                  $user = $this->_environment->getCurrentUserItem();
            $buzzword_item->setCreatorItem($user);
            $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
            $buzzword_item->save();
            $buzzword_ids[] = $buzzword_item->getItemID();
               }
      }
         }
      }
      $copy->setBuzzwordListByID($buzzword_ids);
      $copy->setGroupList($list);
      $copy->setInstitutionList($list);
      $copy->setTopicList($list);
   }
   $copy->setSectionList(new cs_list());
   $copy->save();
   $copy_id = $copy->getItemId();

   // files in from sections
   $section_list = $this->_copySectionList($copy_id);
   $copy->setSectionList($section_list);
   unset($section_list);
   $copy->save($mode = 'copy');
   $copy_id = $copy->getItemID();

   $reader_manager = $this->_environment->getReaderManager();
   $reader_manager->markRead($copy_id, $copy->getVersionID());

   //Import all versions off the material
   $material_manager = $this->_environment->getMaterialManager();
   $version_list = $material_manager->getVersionList($this->getItemID());
   $import_version = $version_list->getFirst();
   $version = $this->getVersionID();
   while ($import_version) {
      $version_id = $import_version->getVersionID();
      if ($version_id != $version) {
         $copy_version = $import_version->copyVersion($copy_id);
         $reader_manager->markRead($copy_id, $version_id);
      }
      $import_version = $version_list->getNext();
   }
   return $copy;
}

function copyVersion ($id) {
   $copy = $this->cloneCopy(true);
   $copy->setItemID($id);
   $copy->setVersionID($this->getVersionID());
   $copy->setFileList($this->_copyFileList());
   $copy->setCopyItem($this);
   $copy->setContextID($this->_environment->getCurrentContextID());
   $copy->setCreatorItem($this->_environment->getCurrentUserItem());
   $list = new cs_list();
   if ( $this->_environment->getCurrentContextID() != $this->getContextID() ) {
      // Add a new labels if necessary
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->reset();
      $label_manager->setContextLimit($this->_environment->getCurrentContextID());
      $label_manager->setTypeLimit('label');
      $label_manager->select();
      $label_list = $label_manager->get();
      $exist = NULL;
      if ( !empty($label_list) ) {
         $label = $label_list->getFirst();
         while ( $label ) {
         if ( strcmp($label->getName(), ltrim($this->getLabel())) == 0 ) {
            $exist = $label->getItemID();
         }
         $label = $label_list->getNext();
      }
   }
   if ( !isset($exist) ) {
   $temp_array = array();
   $label_manager = $this->_environment->getLabelManager();
   $label_manager->reset();
   $label_item = $label_manager->getNewItem();
   $label_item->setLabelType('label');
   $label_item->setTitle(ltrim($this->getLabel()));
   $label_item->setContextID($this->_environment->getCurrentContextID());
   $label_item->setCreatorItem($this->_environment->getCurrentUserItem());
   $label_item->setCreationDate(getCurrentDateTimeInMySQL());
   $label_item->save();
   $copy->setLabelId($label_item->getItemId());
      } elseif ( isset($exist) ){
         $temp_array = array();
   $label_manager = $this->_environment->getLabelManager();
   $label_manager->reset();
   $label_item = $label_manager->getItem($exist);
   $copy->setLabelId($exist);
      }

      // Add a new buzzwords if necessary
      $original_buzzword_array = $this->getBuzzwordArray();
      //Get all buzzwords in context in array
      $buzzwords_in_room_array = array();
      $buzzword_manager = $this->_environment->getLabelManager();
      $buzzword_manager->reset();
      $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
      $buzzword_manager->setTypeLimit('buzzword');
      $buzzword_manager->select();
      $buzzword_list = $buzzword_manager->get();
      if ( !empty($buzzword_list) ) {
   $buzzword = $buzzword_list->getFirst();
   while ( $buzzword ) {
      $temp_array['name'] = $buzzword->getName();
      $temp_array['id'] = $buzzword->getItemId();
      $buzzwords_in_room_array[] = $temp_array;
      $buzzword = $buzzword_list->getNext();
   }
      }

      //if buzzword exists, put id in array, if it doesn't exist, create it, then put id in array
      $buzzword_ids = array();
      if ( isset($original_buzzword_array) and
           !empty($original_buzzword_array) ) {
         for ($i=0; $i<count($original_buzzword_array); $i++) {
            $found = false;
            if ( isset($buzzwords_in_room_array) and
                 !empty($buzzwords_in_room_array) ) { //There are buzzwords in the room
         for ($j=0;($j < count($buzzwords_in_room_array));$j++) {
            if ( isset($buzzwords_in_room_array[$j]) and
                       isset($original_buzzword_array[$i]) and
                       isset($buzzwords_in_room_array[$j]['name']) and
                       !empty($buzzwords_in_room_array[$j]['name']) and
                       isset($buzzwords_in_room_array[$j]['id']) and
                       !empty($buzzwords_in_room_array[$j]['id'])
                     ) {
                     if ( strcmp($buzzwords_in_room_array[$j]['name'], ltrim($original_buzzword_array[$i])) == 0 ) {
             $buzzword_ids[] = $buzzwords_in_room_array[$j]['id'];
                  $found=true;
                        break;
               }
               if (!$found and $j==count($buzzwords_in_room_array)-1) {
             $buzzword_manager = $this->_environment->getLabelManager();
             $buzzword_manager->reset();
             $buzzword_item = $buzzword_manager->getNewItem();
             $buzzword_item->setLabelType('buzzword');
             $buzzword_item->setTitle(ltrim($original_buzzword_array[$i]));
             $buzzword_item->setContextID($this->_environment->getCurrentContextID());
             $buzzword_item->setCreatorItem($this->_environment->getCurrentUserItem());
             $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
             $buzzword_item->save();
             $buzzword_ids[] = $buzzword_item->getItemID();
               }
                  }
         }
      } else { //There are no buzzwords in the context, so create all
               if ( isset($original_buzzword_array[$i]) and
                    !empty($original_buzzword_array[$i])
                  ) {
            $buzzword_manager = $this->_environment->getLabelManager();
            $buzzword_manager->reset();
            $buzzword_item = $buzzword_manager->getNewItem();
            $buzzword_item->setLabelType('buzzword');
            $buzzword_item->setTitle(ltrim($original_buzzword_array[$i]));
            $buzzword_item->setContextID($this->_environment->getCurrentContextID());
            $buzzword_item->setCreatorItem($this->_environment->getCurrentUserItem());
            $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
            $buzzword_item->save();
            $buzzword_ids[] = $buzzword_item->getItemID();
               }
      }
         }
      }
      $copy->setBuzzwordListByID($buzzword_ids);
      $copy->setGroupList($list);
      $copy->setInstitutionList($list);
      $copy->setTopicList($list);
   }

   $copy->setSectionList(new cs_list());
   $copy->save();
   $section_list = $this->_copySectionList($id);
   $copy->setSectionList($section_list);
   unset($section_list);
   $copy->save($mode = 'copy');
}

function cloneCopy($new_version = false) {
   $clone_item = clone $this; // "clone" needed for php5
   if (!empty($this->_changed) and !$new_version) {
      include_once('functions/error_functions.php');
      trigger_error("attempt to clone unsaved / changed material; clone will match the persistent state of this item", E_USER_WARNING);
   }
   $label_item = $this->getLabelItem();
   if ($label_item!=NULL){
      $clone_item->setLabel($label_item->getName());
   }
   $clone_item->setBuzzwordArray($this->getBuzzwordArray());
   $clone_item->setFileIDArray($this->getFileIDArray());
   $group_list = $this->getGroupList();
   $clone_item->setGroupList($group_list);
   $section_list = $this->getSectionList();
   $clone_item->setSectionList($section_list);
   $institution_list = $this->getInstitutionList();
   $clone_item->setInstitutionList($institution_list);
   $topic_list = $this->getTopicList();
   $clone_item->setTopicList($topic_list);

   return $clone_item;
}


function _copySectionList ($copy_id) {
   $section_list = $this->getSectionList();
   $section_new_list = new cs_section_list();
   if (!empty($section_list) and $section_list->getCount() > 0) {
      $section_item = $section_list->getFirst();
      while ($section_item) {
         $file_list = $section_item->_copyFileList();
         $section_item->setFileList($file_list);
         $section_item->setItemID('');
         $section_item->setContextID($this->_environment->getCurrentContextID());
         $section_item->setLinkedItemID($copy_id);
         $section_new_list->append($section_item);
         $section_item = $section_list->getNext();
      }
   }
   return $section_new_list;
}

########################### DEPRECATED: SHOULD BE REMOVED ASAP

   function isNotRequestedForPublishing () {
      $value = $this->getWorldPublic();
      if (empty($value) or $value == 0) {
         return TRUE;
      }
      return FALSE;
   }

   function isRequestedForPublishing () {
      $value = $this->getWorldPublic();
      if ($value == 1) {
         return TRUE;
      }
      return FALSE;
   }

   function isPublished () {
      $value = $this->getWorldPublic();
      if ($value == 2) {
         return TRUE;
      }
      return FALSE;
   }

   /** get information in dublin core style
    * this method returns an array with information of the material in dublin core style
    *
    * @return array array with information in dublin core style
    */
   function getDublinCoreArray () {
      $retour = array();
      $retour['DC.TITLE'] = $this->getTitle();
      $retour['DC.CREATOR.NAME'] = $this->getAuthor();

      // hier sollte eigentlich nur der Verleger / Herausgeber erscheinen
      // das ist aber im grunde genommen okay
      $bibliographic  = $this->getBibliographicValues();
      if (!empty($bibliographic) and strstr($bibliographic,'<!-- KFC TEXT -->') ) {
        $bibliographic = str_replace('<!-- KFC TEXT -->','',$bibliographic);
      }
      if (!empty($bibliographic)) {
         $retour['DC.PUBLISHER'] = htmlentities($bibliographic);
      }

      // das Datum muss eigentlich so vorliegen jjjjmmtt
      $retour['DC.DATE.CREATION'] = $this->getPublishingDate();

      // hierf�r gibt es eigentlich eine definierte Liste im Standard
      $material_type = $this->getLabelItem();
      if (isset($material_type)) {
         $retour['DC.TYPE'] = $material_type->getName();
      }

      $file_list = $this->getFileList();
      if (!$file_list->isEmpty()) {
         $format = '';
         $first = true;
         $file_item = $file_list->getFirst();
         while ($file_item) {
            if ($first) {
               $first = false;
            } else {
               $format .= ', ';
            }
            $format .= $file_item->getMime();
            $format .= ' ('.$file_item->getFileSize().'kb)';
            $file_item = $file_list->getNext();
         }
      }
      if (empty($format)) {
         $format = 'Text/HTML';
      }
      $retour['DC.FORMAT'] = '(SCHEME=IMT) '.$format;

      #$retour['DC.Language'] = '';
      #$retour['DC.Coverage.Spatial'] = ''; //Geografische G�ltigkeit

      $keyword_array = $this->getBuzzwordArray();
      if (!empty($keyword_array)) {
         $retour['DC.SUBJECT.KEYWORD'] = implode(',',$keyword_array);
      }

      $topic_list = $this->getTopicList();
      if (!$topic_list->isEmpty()) {
         $topic = '';
         $first = true;
         $topic_item = $topic_list->getFirst();
         while ($topic_item) {
            if ($first) {
               $first = false;
            } else {
               $topic .= ', ';
            }
            $topic .= $topic_item->getName();
            $topic_item = $topic_list->getNext();
         }
         $retour['DC.SUBJECT.CLASSIFICATION'] = $topic;
      }

      $description = $this->getDescription();
      if (!empty($description)) {
         $retour['DC.DESCRIPTION'] = strip_tags($description);
      }

      #$retour['DC.Relation'] = ''; //Angabe einer URL zu einer Ressource, die mit dem Material assiziierbar ist.

      // Die folgenden Angaben beziehen sich immer auf die Quelle, in der das Material publiziert wurde.
      // Dies k�nnte z.B. ein Buch sein, in dem das Material (Artikel) erschienen ist.
      #$retour['DC.Source.Creator'] = '';
      #$retour['DC.Source.Title'] = '';
      #$retour['DC.Source.Volume'] = '';
      #$retour['DC.Source.PublishingPlace'] = '';
      #$retour['DC.Source.Date'] = '';
      #$retour['DC.Source.PageNumber'] = '';

      #$retour['DC.RIGHTS'] = ''; // Standardtext zur Nutzerinformation, dass die Urheberrechte bzw. die spezifischen Verwertungsrechte am Dokument zu beachten sind.

      return $retour;
   }


   function getGroupList() {
      $group_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_GROUP_TYPE);
      $group_list->sortBy('name');
      return $group_list;
   }

   function setGroupListByID ($value) {
      $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
   }

   function setGroupList($value) {
      $this->_setObject(CS_GROUP_TYPE, $value, FALSE);
   }


   /** get topics of a material
    * this method returns a list of topics which are linked to the material
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

  /** set topics of a material item by id
   * this method sets a list of topic item_ids which are linked to the material
   *
   * @param array of topic ids
   *
   * @author CommSy Development Group
   */
   function setTopicListByID ($value) {
      $this->setLinkedItemsByID(CS_TOPIC_TYPE, $value);
   }

   /** set topics of a material
    * this method sets a list of topics which are linked to the material
    *
    * @param object cs_list value list of topics (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** get institutions of a material
    * this method returns a list of institutions which are linked to the material
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

  /** set institutions of a material item by id
   * this method sets a list of institution item_ids which are linked to the material
   *
   * @param array of institution ids
   *
   * @author CommSy Development Group
   */
   function setInstitutionListByID ($value) {
      $this->setLinkedItemsByID(CS_INSTITUTION_TYPE, $value);
   }

   /** set institutions of a material
    * this method sets a list of institutions which are linked to the material
    *
    * @param object cs_list value list of institutions (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setInstitutionList($value) {
      $this->_setObject(CS_INSTITUTION_TYPE, $value, FALSE);
   }

   function maySee ($user_item) {
      if ( $this->_environment->inProjectRoom()
           or $user_item->isUser() or $this->isWorldPublic() ) {
         $access = parent::maySee($user_item);
      } else {
         $access = false;
      }
      return $access;
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
      }
      return false;
   }

   /** sets if announcement is editable by everybody or just creator
    *
    * @param value
    */
   function setPublic ($value) {
      $this->_setValue('public', $value);
   }

   function setSectionSaveId($section_id) {
      if (!empty($section_id)) {
         $this->_section_save_id = $section_id;
      } else {
         $this->_section_save_id = 'NEW';
      }
   }

   public function getDataAsXML () {
      $retour  = '<material_item>';
      $retour .= $this->_getDataAsXML();

      $retour = preg_replace('$<copy_of><!\[CDATA\[[\d]*\]\]></copy_of>$','',$retour);
      $retour = preg_replace('$<new_hack><!\[CDATA\[[\d]*\]\]></new_hack>$','',$retour);

      if ( strstr($retour,'<STUDY_LOG>') ) {
         $first_pos = strpos($retour,'<STUDY_LOG>');
         $second_pos = strrpos($retour,'</STUDY_LOG>');
         $first_pos = $first_pos + 11;
         $substring = substr($retour,$first_pos,$second_pos-$first_pos);
      }

      $retour = preg_replace('$<extras>[\d\D]*</extras>$','',$retour);

      if ( !strstr($retour,'<extras>') and isset($substring) ) {
         $retour .= '<extras><study_log><![CDATA['.$substring.']]></study_log></extras>'.LF;
      }

      // special date format
      $publishing_date = $this->getPublishingDate();

      $retour .= '<date>'.LF;
      $retour .= '<day>';
      $retour .= '</day>'.LF;
      $retour .= '<month>';
      $retour .= '</month>'.LF;
      $retour .= '<year><![CDATA[';
      if ( is_int($publishing_date) ) {
         $retour .= trim($publishing_date);
      }
      $retour .= ']]></year>'.LF;
      $retour .= '</date>'.LF;

      // special author format
      $retour .= '<author_list>'.LF;
      $author_array = explode(';',$this->getAuthor());
      foreach ($author_array as $value) {
         $retour .= '<author_item><![CDATA[';
         $retour .= trim($value);
         $retour .= ']]></author_item>'.LF;
      }
      $retour .= '</author_list>'.LF;

      $value = $this->getBibKind();
      if ( !empty($value) ) {
         $retour .= '<bib_kind><![CDATA['.$this->getBibKind().']]></bib_kind>'.LF;
      }
      $value = $this->getBibliographicValues();
      if ( !empty($value) ) {
         $retour .= '<common><![CDATA['.$this->getBibliographicValues().']]></common>'.LF;
      }
      $value = $this->getBooktitle();
      if ( !empty($value) ) {
         $retour .= '<booktitle><![CDATA['.$this->getBooktitle().']]></booktitle>'.LF;
      }
      $value = $this->getPublisher();
      if ( !empty($value) ) {
         $retour .= '<publisher><![CDATA['.$this->getPublisher().']]></publisher>'.LF;
      }
      $value = $this->getAddress();
      if ( !empty($value) ) {
         $retour .= '<address><![CDATA['.$this->getAddress().']]></address>'.LF;
      }
      $value = $this->getVolume();
      if ( !empty($value) ) {
         $retour .= '<volume><![CDATA['.$this->getVolume().']]></volume>'.LF;
      }
      $value = $this->getSeries();
      if ( !empty($value) ) {
         $retour .= '<series><![CDATA['.$this->getSeries().']]></series>'.LF;
      }
      $value = $this->getISBN();
      if ( !empty($value) ) {
         $retour .= '<isbn><![CDATA['.$this->getISBN().']]></isbn>'.LF;
      }
      $value = $this->getISSN();
      if ( !empty($value) ) {
         $retour .= '<issn><![CDATA['.$this->getISSN().']]></issn>'.LF;
      }
      $value = $this->getPages();
      if ( !empty($value) ) {
         $retour .= '<pages><![CDATA['.$this->getPages().']]></pages>'.LF;
      }
      $value = $this->getJournal();
      if ( !empty($value) ) {
         $retour .= '<journal><![CDATA['.$this->getJournal().']]></journal>'.LF;
      }
      $value = $this->getIssue();
      if ( !empty($value) ) {
         $retour .= '<issue><![CDATA['.$this->getIssue().']]></issue>'.LF;
      }
      $value = $this->getUniversity();
      if ( !empty($value) ) {
         $retour .= '<university><![CDATA['.$this->getUniversity().']]></university>'.LF;
      }
      $value = $this->getFaculty();
      if ( !empty($value) ) {
         $retour .= '<faculty><![CDATA['.$this->getFaculty().']]></faculty>'.LF;
      }
      $value = $this->getThesiskind();
      if ( !empty($value) ) {
         $retour .= '<thesis_kind><![CDATA['.$this->getThesiskind().']]></thesis_kind>'.LF;
      }
      $value = $this->getURL();
      if ( !empty($value) ) {
         $retour .= '<url><![CDATA['.$this->getURL().']]></url>'.LF;
      }
      $value = $this->getURLDate();
      if ( !empty($value) ) {
         $retour .= '<url_date><![CDATA['.$this->getURLDate().']]></url_date>'.LF;
      }

      // special editor format
      $retour .= '<editor_list>'.LF;
      $author_array = explode(';',$this->getEditor());
      foreach ($author_array as $value) {
         if ( !empty($value) ) {
            $retour .= '<editor_item><![CDATA[';
            $retour .= trim($value);
            $retour .= ']]></editor_item>'.LF;
         }
      }
      $retour .= '</editor_list>'.LF;

      // buzzword
      $buzzword_array = $this->getBuzzwordArray();
      $retour .= '<keyword_list>'.LF;
      if ( !empty($buzzword_array) ) {
         foreach ($buzzword_array as $buzzword) {
            $retour .= '<keyword_item><![CDATA['.$buzzword.']]></keyword_item>'.LF;
         }
      }
      $retour .= '</keyword_list>'.LF;

      $value = $this->getLabel();
      if ( !empty($value) ) {
         $retour .= '<label><![CDATA['.$this->getLabel().']]></label>'.LF;
      }

      $retour .= '</material_item>'.LF;
      return $retour;
   }

  /** get list of files attached o this item
      if a list of files has been set (@see setFileList()), get it
      if an array of file-ids has been set (@see setFileIDArray()),
      get corresponding files, otherwise get files linked in material_link_file
      @return cs_list list of file items
   */
   function getFileListWithFilesFromSections () {
      $file_list = new cs_list;

      // material
      if ( !empty($this->_data['file_list']) ) {
         $file_list = $this->_data['file_list'];
      } else {
         if ( isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array']) ) {
            $file_id_array = $this->_data['file_id_array'];
         } else {
            $link_manager = $this->_environment->getLinkManager();
            $file_links = $link_manager->getFileLinks($this);
            if ( !empty($file_links) ) {
               foreach($file_links as $link) {
                  $file_id_array[] = $link['file_id'];
               }
            }
         }
         if ( !empty($file_id_array) ) {
            $file_manager = $this->_environment->getFileManager();
            $file_manager->setIDArrayLimit($file_id_array);
            $file_manager->setContextLimit('');
            $file_manager->select();
            $file_list = $file_manager->get();
         }
      }

      // sections
      $section_item_list = clone $this->getSectionList();
      if ( $section_item_list->isNotEmpty() ) {
         $section_list_item = $section_item_list->getFirst();
         while ($section_list_item) {
            $section_file_list = $section_list_item->getFileList();
            if ( $section_file_list->isNotEmpty() ) {
               $file_list->addList($section_file_list);
            }
            unset($section_list_item);
            $section_list_item = $section_item_list->getNext();
         }
      }
      unset($section_list_item);
      unset($section_item_list);
      return $file_list;
   }
}
?>