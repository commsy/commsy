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

/** upper class of the discussion item
 */
include_once('classes/cs_item.php');

/** class for a discussion
 * this class implements a discussion item
 */
class cs_discussion_item extends cs_item {

  /** constructor
   * the only available constructor, initial values for internal variables
   *
   * @param object  environment            environment of the commsy
   *
   */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = CS_DISCUSSION_TYPE;
   }

  /** get title of a discussion
   * this method returns the title of the discussion
   *
   * @return string title of a discussion
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


  /** set title of a discussion
   * this method sets the title of the discussion
   *
   * @param string value title of the discussion
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

   /** get item id of the latest article
    * this method returns the item id of the latest article of the discussion
    *
    * @return integer item id of the latest article of an discussion
    *
    * @author CommSy Development Group
    */
   function getLatestArticleID () {
      return $this->_getValue('latest_article_item_id');
   }

   /** set item id of the Latest article
    * this method sets the item id of the Latest article of the discussion
    *
    * @param integer value item id of the Latest article of the discussion
    *
    * @author CommSy Development Group
    */
   function setLatestArticleID ($value) {
      $this->_setValue('latest_article_item_id', $value);
   }

   /** get modification date of the latest article
    * this method returns the item id of the latest article of the discussion
    *
    * @return integer item id of the latest article of an discussion
    *
    * @author CommSy Development Group
    */
   function getLatestArticleModificationDate () {
      return $this->_getValue('latest_article_modification_date');
   }

   /** set modification date of the Latest article
    * this method sets the modification date of the Latest article of the discussion
    *
    * @param string value modification date of the discussion
    *
    * @author CommSy Development Group
    */
   function setLatestArticleModificationDate ($value) {
      $this->_setValue('latest_article_modification_date', $value);
   }

   /** set status of a discussion
    * this method returns the status of the discussion
    *
    * @param integer value status of a discussion
    *
    * @author CommSy Development Group
    */
   function setDiscussionStatus ($value) {
      $this->_setValue('status', $value);
   }

   /** get status of a discussion
    * this method returns the status of the discussion
    *
    * @return integer status of a discussion
    *
    * @author CommSy Development Group
    */
   function getDiscussionStatus () {
      return $this->_getValue('status');
   }

   /** set type of a discussion
    * this method returns the type of the discussion
    *
    * @param string value type of a discussion
    *
    * @author CommSy Development Group
    */
   function setDiscussionType ($value) {
      $this->_setValue('discussion_type', $value);
   }

   /** get stype of a discussion
    * this method returns the type of the discussion
    *
    * @return string status of a discussion
    *
    * @author CommSy Development Group
    */
   function getDiscussionType () {
      return $this->_getValue('discussion_type');
   }

   /** close a discussion
    * this method sets the status of the discussion to closed
    *
    * @author CommSy Development Group
    */
   function close () {
      $this->setDiscussionStatus(2);
   }

   /** is room a normal open ?
    * this method returns a boolean explaining if a discussion is open
    *
    * @return boolean true, if a discussion is open
    *                 false, if a discussion is not open
    *
    * @author CommSy Development Group
    */
   function isOpen () {
      return $this->setDiscussionStatus(1);
   }

   /** is a discussion  closed ?
    * this method returns a boolean explaining if a discussion is open or not
    *
    * @return boolean true, if a discussion is closed
    *                 false, if a discussion is not closed
    *
    * @author CommSy Development Group
    */
   function isClosed () {
      if ($this->getDiscussionStatus()== 2){
         return true;
      }
      else{
         return false;
      }
   }


  /** get number of articles of a discussion
   * this method returns a number of articles of a discussion
   *
   * @return int
   *
   * @author CommSy Development Group
   */
   function getAllArticlesCount () {
      $discussionarticles_manager = $this->_environment->getDiscussionArticlesManager();
      $discussionarticles_manager->setDiscussionLimit($this->getItemID());
      $discussionarticles_manager->select();
      $all_articles = $discussionarticles_manager->getCountAll();
      return $all_articles;
   }

   /** get all articles of discussion
   * this method returns all articles of the discussion
   *
   * @param bool show_all If true, all articles of a closed discussion are selected. Default false.
   *
   * @return cs_list
   */

   function getAllArticles($show_all=false) {
      $discussionarticles_manager = $this->_environment->getDiscussionArticlesManager();
      return $discussionarticles_manager->getAllArticlesForItem($this,$show_all=false);
   }

  /** get unread articles of a discussion
   * this method returns a number of unread articles of a discussion
   *
   * @return int
   *
   * @author CommSy Development Group
   */
   function getUnreadArticles () {
      $discussionarticles_manager = $this->_environment->getDiscussionArticlesManager();
      $reader_manager = $this->_environment->getReaderManager();
      $discussionarticles_manager->setDiscussionLimit($this->getItemID());
      $discussionarticles_manager->select();
      $discussionarticles_list = $discussionarticles_manager->get();
      $discussionarticle_item = $discussionarticles_list->getFirst();
      $number_of_unread = 0;
      while ($discussionarticle_item) {
         // Mark item as read, if we read it for the first time
         $reader = $reader_manager->getLatestReader($discussionarticle_item->getItemID());
         if ( (empty($reader)) || ($reader['version_id'] < $discussionarticle_item->getVersionID()) || ($reader['read_date'] < $discussionarticle_item->getModificationDate()) ) {
            $number_of_unread = $number_of_unread + 1;
         }
         $discussionarticle_item = $discussionarticles_list->getNext();
      }
      return $number_of_unread;
   }

   function getAllAndUnreadArticles (){
     $noticed_manager = $this->_environment->getNoticedManager();
     $list  = $this->getAllArticles();
     $count = $list->getCount();
     $discussionarticle_item = $list->getFirst();
     $number_of_unread = 0;
     while ($discussionarticle_item) {
        // Mark item as read, if we read it for the first time
        $noticed = $noticed_manager->getLatestNoticed($discussionarticle_item->getItemID());
        if ( (empty($noticed)) || ($noticed['version_id'] < $discussionarticle_item->getVersionID()) || ($noticed['read_date'] < $discussionarticle_item->getModificationDate()) ) {
           $number_of_unread = $number_of_unread + 1;
        }
        $discussionarticle_item = $list->getNext();
     }
     $retour = array();
     $retour['count']= $count;
     $retour['unread']= $number_of_unread;
     return $retour;
   }

   /**
   save TBD
   */
   function save() {
      $discussion_manager = $this->_environment->getDiscussionManager();
      $this->_save($discussion_manager);

      $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_discussion');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Discussions');

        $this->replaceElasticItem($objectPersister, $repository);
    }

    public function delete()
    {
        global $symfonyContainer;

        /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcer */
        $eventDispatcer = $symfonyContainer->get('event_dispatcher');

        $itemDeletedEvent = new \App\Event\ItemDeletedEvent($this);
        $eventDispatcer->dispatch($itemDeletedEvent, \App\Event\ItemDeletedEvent::NAME);

        $discussion_manager = $this->_environment->getDiscussionManager();
        $this->_delete($discussion_manager);

        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_discussion');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Discussions');

        $this->deleteElasticItem($objectPersister, $repository);
    }

   /** Checks and sets the data of the discussion_item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      // TBD: check data before setting
      $this->_data = $data_array;
   }

   /* Checks access rights.
   *  Access is granted, if the user has the rights to edit a discussion if it is open.
   */
   function mayEditIgnoreClose($user_item) {
      return parent::mayEdit($user_item);
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

   function isCopiedToMaterial() {
      if ($this->_getExtra('COPIED_TO_MATERIAL')=='true') {
        return true;
      } else {
        return false;
      }
   }

   function setCopiedToMaterial($bool) {
    $value = 'false';
     if ($bool) {
        $value='true';
     }
     $this->setExtra('COPIED_TO_MATERIAL',$value);
   }

   function copy() {
      $error_array_sum = array();
      $copy = $this->cloneCopy();
      $copy->setItemID('');
      $copy->setContextID($this->_environment->getCurrentContextID());
      $user = $this->_environment->getCurrentUserItem();
      $copy->setCreatorItem($user);
      $copy->setModificatorItem($user);
      $list = new cs_list();
      $copy->setGroupList($list);
      $copy->setTopicList($list);
      $copy->save();
      $copy_id = $copy->getItemID();
      $article_list = $this->getAllArticles(true);
      $article = $article_list->getFirst();
      $article_number = 1;



      while ( $article ) {
         $article_number++;
         $arcticle_copy = $article->cloneCopy();
         $arcticle_copy->setItemID('');
         $file_list = $article->getFileList();
         if ($file_list->isNotEmpty()) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_item->setTempName($file_item->getDiskFilename());
               $file_item = $file_list->getNext();
            }
            $arcticle_copy->setFileList($file_list);
         }
         $arcticle_copy->setContextID($this->_environment->getCurrentContextID());
         $user = $this->_environment->getCurrentUserItem();
         $arcticle_copy->setCreatorItem($user);
         $arcticle_copy->setModificatorItem($user);
         $arcticle_copy->setDiscussionID($copy_id);
         $arcticle_copy->save();

         // error while saving files?
         $error_array = $arcticle_copy->getErrorArray();
         if ( !empty($error_array) ) {
            $error_array_sum = array_merge($error_array,$error_array_sum);
         }
         if (!empty($error_array_sum)) {
            $copy->setErrorArray($error_array_sum);
         }
         if ($article->isDeleted()) {
            $arcticle_copy->delete();
         }
         $article = $article_list->getNext();
      }
      if ($this->isClosed()){
         $copy->close();
         $copy->save();
      }
      return $copy;
   }


   function cloneCopy() {
      $clone_item = clone $this; // "clone" needed for php5
      $group_list = $this->getGroupList();
      $clone_item->setGroupList($group_list);
      $topic_list = $this->getTopicList();
      $clone_item->setTopicList($topic_list);
      return $clone_item;
   }

  /** get list of files attached o this item
      if a list of files has been set (@see setFileList()), get it
      if an array of file-ids has been set (@see setFileIDArray()),
      get corresponding files, otherwise get files linked in material_link_file
      @return cs_list list of file items
   */
   function getFileListWithFilesFromArticles () {
      $file_list = new cs_list;

      // articles
      $section_list = clone $this->getAllArticles();
      if ( $section_list->isNotEmpty() ) {
         $section_item = $section_list->getFirst();
         while ($section_item) {
            $section_file_list = $section_item->getFileList();
            if ( $section_file_list->isNotEmpty() ) {
               $file_list->addList($section_file_list);
            }
            unset($section_item);
            $section_item = $section_list->getNext();
         }
      }
      unset($section_item);
      unset($section_list);
      $file_list->sortby('filename');
      return $file_list;
   }
    /** set description of a material
     * this method sets the description of the material an marks it as 'changed'
     *
     * @param string value description of the material
     *
     * @author CommSy Development Group
     */
    function setDescription ($value) {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description',$value);
    }
    /** get description of a material
     * this method returns the description of the material
     *
     * @return string description of a material
     *
     * @author CommSy Development Group
     */
    function getDescription () {
        if ($this->getPublic()=='-1'){
            $translator = $this->_environment->getTranslationObject();
            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        }else{
            return (string) $this->_getValue('description');
        }
    }
}
?>