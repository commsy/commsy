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

/** class of the discussionarticle item
 */
include_once('classes/cs_item.php');

/** class for an discussionarticle
 * this class implements a discussionarticle item
 */
class cs_discussionarticle_item extends cs_item {

   /** constructor
    * the only available constructor, initial values for internal variables
    */
   function __construct($environment) {
      cs_item::__construct($environment);
      $this->_type = 'discarticle';
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

    /**
     * Returns whether this item's content should get overwritten with some placeholder text.
     * @return bool Whether this item's content should get overwritten (true), or not (false)
     */
    public function getHasOverwrittenContent(): bool
    {
        // NOTE: `public = -2` gets used for articles with answers which were "deleted" but should
        // instead have their content overwritten to keep the discussion hierarchy intact
        $hasOverwrittenContent = $this->getPublic() == '-2';

        return $hasOverwrittenContent;
    }

    /**
     * Returns the subject of the discussion article.
     * @return string subject of the discussion article
     */
    public function getSubject(): string
    {
        $public = $this->getPublic();
        if ($public == '-1' || $public == '-2') {
            $translator = $this->_environment->getTranslationObject();
            $message = ($public == '-1') ? 'COMMON_AUTOMATIC_DELETE_TITLE' : 'COMMON_DELETED_DISCARTICLE_WITH_ANSWERS_TITLE';
            return $translator->getMessage($message);
        }

        return $this->_getValue('subject');
    }

   function getTitle () {
      return $this->getSubject();
   }

   function getPosition () {
      return $this->_getValue('position');
   }

   function setPosition ($value) {
      $this->_setValue('position', $value);
   }

   /** set subject of a discussionarticle
    * this method sets the subject of the discussionarticle
    *
    * @param string value subject of the discussionarticle
    *
    * @author CommSy Development Group
    */
   function setSubject ($value) {
   	  // sanitize subject
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeHTML($value);
      $this->_setValue('subject', $value);
   }

   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
      $value = htmlentities($value);
   	  $value = $converter->sanitizeHTML($value);
      $this->setSubject($value);
   }

    /**
     * Returns the description of this discussion article.
     * @return string|null description of the discussion article
     */
    public function getDescription(): ?string
    {
        $public = $this->getPublic();
        if ($public == '-1' || $public == '-2') {
            $translator = $this->_environment->getTranslationObject();
            $message = ($public == '-1') ? 'COMMON_AUTOMATIC_DELETE_DESCRIPTION' : 'COMMON_DELETED_DISCARTICLE_WITH_ANSWERS_DESC';
            return $translator->getMessage($message);
        }

        return $this->_getValue('description');
    }

   /** set description of a discussionarticle
    * this method sets the description of the discussionarticle
    *
    * @param string value description of the discussionarticle
    *
    * @author CommSy Development Group
    */
   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeFullHTML($value);
      $this->_setValue('description', $value);
   }

   /** get discussion id
    * this method returns the discussion id of the article
    *
    * @return integer discussion id of the article
    *
    * @author CommSy Development Group
    */
   function getDiscussionID () {
      return $this->_getValue('discussion_id');
   }

    /**
     * @return null|\cs_discussion_item
     */
    public function getLinkedItem()
    {
        $retour = NULL;
        $item_id = $this->getDiscussionID();
        if (!empty($item_id)) {
            $type_manager = $this->_environment->getManager(CS_DISCUSSION_TYPE);
            $retour = $type_manager->getItem($item_id);
        }
        return $retour;
    }

   /** set discussion id
    * this method sets the discussion id of the article
    *
    * @param integer value discussion id of the article
    *
    * @author CommSy Development Group
    */
   function setDiscussionID ($value) {
      $this->_setValue('discussion_id', $value);
   }

  /** set materials of the discussionarticle item
   * this method sets a list of materials which are linked to the discussionarticle item
   *
   * @param cs_list list of cs_material_item
   *
   * @author CommSy Development Group
   */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

  /** set materials of a discussionarticle item by id
   * this method sets a list of group item_ids which are linked to the discussionarticle
   *
   * @param array of group ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   */
   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
   }

   /** get materials of the discussionarticle item
   * this method returns a list of materials which are linked to the discussionarticle
   *
   * @return object cs_list a list of cs_material_item
   */
   function getMaterialList () {
      global $environment;
      return $this->_getLinkedItems($environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

  /** save discussion article
   * this methode saves the discussion article
   */
   function save() {
      $discussion_manager = $this->_environment->getDiscussionArticlesManager();
      $this->_save($discussion_manager);

      // Update the discussion regarding the latest article informations...
      $discussion_manager = $this->_environment->getDiscussionManager();
      $discussion_item = $discussion_manager->getItem($this->getDiscussionID());
      $current_user = $this->_environment->getCurrentUserItem();
      $discussion_item->setModificatorItem($current_user);
      if (!$discussion_item->isNotActivated()){
         $discussion_item->setModificationDate($this->getModificationDate());
      }
      $discussion_item->setLatestArticleID($this->getItemID());
      $discussion_item->setLatestArticleModificationDate($this->getModificationDate());

      $itemManager = $this->_environment->getItemManager();
      $articleItem = $itemManager->getItem($this->getItemID());

      if (!$articleItem->isDraft()) {
        $discussion_item->save();
      }

      $this->_saveFiles();     // this must be done before saveFileLinks
      $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id

      // set and noticed reader manager
      $reader_manager = $this->_environment->getReaderManager();
      $reader_manager->markRead($this->getItemID(),0);
      $noticed_manager = $this->_environment->getNoticedManager();
      $noticed_manager->markNoticed($this->getItemID(),0);

      // unset objects
      unset($discussion_manager);
      unset($discussion_item);
      unset($reader_manager);
      unset($noticed_manager);
   }

    /**
     * Deletes the discussion article (or overwrites its content if the article has children).
     * When a discussion article has child article(s) we won't delete it but instead only indicate (by setting
     * `public = -2`) that its content should get overwritten. This will keep the discussion hierarchy intact.
     */
    public function delete(): void
    {
        $discussionManager = $this->_environment->getDiscussionArticlesManager();
        $children = $discussionManager->getChildrenForDiscArticle($this);

        if ($children->isNotEmpty()) {
            $discussionManager->overwriteContent($this->getItemID());

            return;
        }

        $this->_delete($discussionManager);

        // if this article has a parent article with `public = -2` that has no children (anymore), delete the parent as well
        $parentArticle = $discussionManager->getParentForDiscArticle($this);
        if ($parentArticle && $parentArticle->getHasOverwrittenContent()) {
            $parentArticleChildren = $discussionManager->getChildrenForDiscArticle($parentArticle);
            if ($parentArticleChildren->isEmpty()) {
                $parentArticle->delete();
            }
        }
    }

   function cloneCopy() {
      $clone_item = clone $this; // "clone" needed for php5
      return $clone_item;
   }

   public function saveWithoutChangingModificationInformation () {
      $manager = $this->_environment->getManager($this->_type);
      $manager->saveWithoutChangingModificationInformation();
      $this->_save($manager);
      $this->_changes = array();
   }

    /**
     * Returns whether the given user may edit this discussion article or not, but will always prevent editing if
     * the article has its content set to be overwritten (as indicated by getPublic() returning a value of '-2').
     * @param \cs_user_item $userItem The user for whom edit rights shall be checked
     * @return bool Whether the given user is allowed to edit this article (true), or not (false)
     */
    public function mayEdit(\cs_user_item $userItem): bool
    {
        if ($this->getHasOverwrittenContent()) {
            return false;
        }

        $mayEditItem = parent::mayEdit($userItem);

        return $mayEditItem;
    }
}
?>