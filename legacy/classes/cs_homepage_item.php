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

/** class for a homepage
 * this class implements a homepage item
 */
class cs_homepage_item extends cs_item {

   /** constructor: cs_homepage_item
    * the only available constructor, initial values for internal variables
    */
   function cs_homepage_item ($environment) {
      $this->cs_item($environment);
      $this->_type = CS_HOMEPAGE_TYPE;
      $this->setPageTypeToChild();
   }

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
   }

   /** get title of a homepage
    * this method returns the title of the homepage
    *
    * @return string title of a homepage
    */
   function getTitle () {
      return $this->_getValue('title');
   }

   /** set title of a homepage
    * this method sets the title of the homepage
    *
    * @param string value title of the homepage
    */
   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeHTML($value);
      $this->_setValue('title', $value);
   }

   /** get description of a homepage
    * this method returns the description of the homepage
    *
    * @return string description of an homepage
    */
   function getDescription () {
      return $this->_getValue('description');
   }

   /** set description of a homepage
    * this method sets the description of the homepage
    *
    * @param string value description of the homepage
    */
   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeFullHTML($value);
      $this->_setValue('description', $value);
   }

   public function setPageTypeToChild () {
      $this->_setPageType('CHILD');
   }

   public function setPageTypeToRoot () {
      $this->_setPageType('ROOT');
   }

   public function setPageTypeToIMPRINT () {
      $this->_setPageType('IMPRINT');
   }

   private function _setPageType ($value) {
      $this->_setValue('page_type', $value);
   }

   function getPageType () {
      return $this->_getValue('page_type');
   }

   /** asks if item is editable by everybody or just creator
    *
    * @param value
    */
   function isPublic() {
      if ($this->_getValue('public')== 1) {
         return true;
      } else {
        return false;
      }
   }

   public function isSpecialPage () {
      $retour = false;
      if ( isset($this->_data['page_type']) and ( $this->_data['page_type'] == 'ROOT'
                                                  or $this->_data['page_type'] == 'IMPRINT'
                                                )
           ) {
         $retour = true;
      }
      return $retour;
   }

   public function isImprintPage () {
      $retour = false;
      if ( isset($this->_data['page_type'])
           and ( $this->_data['page_type'] == 'IMPRINT')
           ) {
         $retour = true;
      }
      return $retour;
   }

   public function isRootPage () {
      $retour = false;
      if ( isset($this->_data['page_type'])
           and ( $this->_data['page_type'] == 'ROOT')
           ) {
         $retour = true;
      }
      return $retour;
   }

   /** sets if item is editable by everybody or just creator
    *
    * @param value
    */
   function setPublic ($value) {
      $this->_setValue('public', $value);
   }

   function getFatherID () {
      return $this->_getValue('father_id');
   }

   function setFatherID ($value) {
      $this->_setValue('father_id', $value);
   }

   function getSortingPlace () {
      return $this->_getValue('sorting_place');
   }

   function setSortingPlace ($value) {
      $this->_setValue('sorting_place', $value);
   }

   function getRoomTitle () {
      return $this->_getValue('room_title');
   }

   function setRoomTitle ($value) {
      $this->_setValue('room_title', $value);
   }

   function getRoomActivity () {
      return $this->_getValue('room_activity');
   }

   function setRoomActivity ($value) {
      $this->_setValue('room_activity', $value);
   }

   function save() {
      $manager = $this->_environment->getManager($this->_type);
      $this->_save($manager);
      $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have a item id
   }

   public function moveUp () {
      $manager = $this->_environment->getManager($this->_type);
     $manager->moveUp($this);
   }

   public function moveDown () {
      $manager = $this->_environment->getManager($this->_type);
     $manager->moveDown($this);
   }

   public function moveLeft () {
      $manager = $this->_environment->getManager($this->_type);
     $manager->moveLeft($this);
   }

   public function moveRight () {
      $manager = $this->_environment->getManager($this->_type);
     $manager->moveRight($this);
   }

   function _saveFileLinks() { // es: das ist so komplex, weil wir die filelinks nicht aus der db löschen können
                                 //wenn jemandem was eleganteres einfällt: nur zu
      if ( isset($this->_filelist_changed) and $this->_filelist_changed ) {
         $this->setModificationDate(NULL);
         $link_manager = $this->_environment->getLinkManager();
         $file_id_array = $this->_getValue('file_id_array');
         if ($file_id_array === '') {
            $link_manager->deleteFileLinks($this);
         } else {
            $current_file_links = $link_manager->getFileLinks($this);
            $keep_links = array();
            if (!empty($current_file_links)) {
               foreach($current_file_links as $cur_link) {
                  if(in_array($cur_link['file_id'], $file_id_array)) {
                     $keep_links[] = $cur_link['file_id'];
                  } else {
                     $link_manager->deleteFileLinkByID($this, $cur_link['file_id']);
                  }
               }
            }
            $add_links = array_diff($file_id_array, $keep_links);
            if (!empty($add_links)) {
               foreach($add_links as $file_id) {
                  $link_manager->linkFileByID($this, $file_id);
               }
            }
         }
      }
   }

   /** delete homepage
    * this method deletes the homepage
    */
   function delete() {
      $manager = $this->_environment->getManager($this->_type);
      $this->_delete($manager);
   }

   function setFileIDArray ($value) {
      $this->_data['file_id_array'] = $value;
      $this->_data['file_list'] = NULL;
      $this->_filelist_changed = TRUE;
    }

   function setFileList ($value) {
      $this->_data['file_list'] = $value;
      $this->_data['file_id_array'] = NULL;
      $this->_filelist_changed = TRUE;
    }

   /**get list of files attached o this item
      @return cs_list list of file items
   */
   function getFileList() {
      include_once('classes/cs_list.php');
      $file_list = new cs_list;
      if(!empty($this->_data['file_list'])) {
         $file_list = $this->_data['file_list'];
      } else {
         if(isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array'])) {
            $file_id_array = $this->_data['file_id_array'];
         } else {
            $link_manager = $this->_environment->getLinkManager();
            $file_links = $link_manager->getFileLinks($this);
            if(!empty($file_links)) {
               foreach($file_links as $link) {
                  $file_id_array[] = $link['file_id'];
               }
            }
         }
         if(!empty($file_id_array)) {
            $file_manager = $this->_environment->getFileManager();
            $file_manager->setIDArrayLimit($file_id_array);
            $file_manager->select();
            $file_list = $file_manager->get();
         }
      }
      return $file_list;
   }

   /**get array of file ids
      @return array file_id_array
   */
   function getFileIDArray() {
      $file_id_array = array();
      if(isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array'])) { // check if file_id_array has been set by user or this method has been called before
         $file_id_array = $this->_data['file_id_array'];
      } else if(isset($this->_data['file_list']) and is_object($this->_data['file_list'])) {
         $file = $this->_data['file_list']->getFirst();
         while($file) {
            $file_id_array[] = $file->getFileID();
            $file = $this->_data['file_list']->getNext();
         }
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $file_links = $link_manager->getFileLinks($this);
         if(!empty($file_links)) {
            foreach($file_links as $link) {
               $file_id_array[] = $link['file_id'];
            }
         }
      }
      return $file_id_array;
   }
}
?>