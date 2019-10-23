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
   function __construct($environment) {
      cs_item::__construct($environment);
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
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
   	  }else{
         return $this->_getValue('title');
   	  }
   }

   /** set title of an announcement
    * this method sets the title of the announcement
    *
    * @param string value title of the announcement
    *
    * @author CommSy Development Group
    */
   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $value = htmlentities($value);
   	  $value = $converter->sanitizeHTML($value);
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
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
   	  }else{
         return $this->_getValue('description');
   	  }
   }

   /** set description of an announcement
    * this method sets the description of the announcement
    *
    * @param string value description of the announcement
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeFullHTML($value);
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


   function save() {
      $announcement_manager = $this->_environment->getAnnouncementManager();
      $this->_save($announcement_manager);
      $this->_saveFiles();     // this must be done before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id

      $this->updateElastic();
   }

   public function updateElastic()
   {
       global $symfonyContainer;
       $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.announcement');
       $em = $symfonyContainer->get('doctrine.orm.entity_manager');
       $repository = $em->getRepository('App:Announcement');

       $this->replaceElasticItem($objectPersister, $repository);
   }

   /** delete announcement
    * this method deletes the announcement
    */
   function delete() {
      $manager = $this->_environment->getAnnouncementManager();
      $this->_delete($manager);

      // delete associated annotations
      $this->deleteAssociatedAnnotations();

      global $symfonyContainer;
      $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.announcement');
      $em = $symfonyContainer->get('doctrine.orm.entity_manager');
      $repository = $em->getRepository('App:Announcement');

      $this->deleteElasticItem($objectPersister, $repository);
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