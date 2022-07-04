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

/** upper class of the label item
 */

use App\Entity\Labels;

include_once('classes/cs_item.php');

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 */
class cs_label_item extends cs_item {

   /**
    * string - containing the name of the label
    */
   var $_name;

   /**
    * string - containing the description of the label
    */
   var $_description;

   /**
    * string - containing the extra information of the label
    */
   var $_extras;

   /**
    * string - containing the type of the label
    */
   var $_label_type;

   /**
    * boolean - containing true or false, if label is sort criteria
    */
   var $_is_sort_criteria = false;

   /**
    * boolean - containing true or false, if label is a system label or not
    */
   var $_is_system_label = false;

   /**
    * cs_item - last changed item refering to this topic
    */
   var $_last_changed_item = NULL;

   /**
    * boolean - TRUE if already attempted to load last changed item from DB, FALSE otherwise
    */
   var $_loaded_last_changed_item = FALSE;

   var $_count_links = 0;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param string label_type type of the label
    *
    * @author CommSy Development Group
    */
   function __construct($environment, $label_type = '') {
      cs_item::__construct($environment);
      $this->_type = CS_LABEL_TYPE;
      $this->_data['type'] = $label_type;
   }

   /** sets the data of the item.
    *
    * @param $data_array
    *
    * @author CommSy Development Group
    */
   function _setItemData($data_array) {
      $translator = $this->_environment->getTranslationObject();
      $this->_data = $data_array;
      if (!empty($this->_data['name']) and $this->_data['name'] == $translator->getMessage('ALL_MEMBERS')) {
         $this->_is_system_label = true;
      }
      return $this->isValid();
   }

   /** Checks and returns the data of the item.
    *
    *
    *
    * @author CommSy Development Group
    */
   function _getItemData() {
      // not yet implemented
      if ($this->isValid()) {
         $item_array['name'] = $this->getName();
         $item_array['type'] = $this->getLabelType();
         $item_array['description'] = $this->getDescription();
         $item_array['extras'] = $this->getExtraInformation();
         return $item_array;
      } else {
         include_once('functions/error_functions.php');trigger_error('cs_label_item: getItemData(): Invalid Data');
      }
   }

   public function getCountLinks () {
      return $this->_count_links;
   }

   public function setCountLinks ($value) {
      return $this->_count_links = (int)$value;
   }

   /** get topics of a label_item
    * this method returns a list of topics which are linked to the label_item
    *
    * @return object cs_list a list of topics (cs_label_item)
    */
   function getTopicList() {
      $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }

  /** set topics of a label_item item by id
   * this method sets a list of topic item_ids which are linked to the label_item
   *
   * @param array of topic ids
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

   /** set topics of a label_item
    * this method sets a list of topics which are linked to the label_item
    *
    * @param object cs_list value list of topics (cs_label_item)
    */
   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** get materials of a label_item
    * this method returns a list of materials which are linked to the label_item
    *
    * @return object cs_list a list of materials (cs_material_item)
    */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

  /** set materials of a label item by item id and version id
   * this method sets a list of material item_ids and version_ids which are linked to the label_item
   *
   * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   */
   function setMaterialListByID ($value) {
      $this->_setValue(CS_MATERIAL_TYPE, $value, FALSE);
   }

   /** set materials of a label_item
    * this method sets a list of materials which are linked to the label_item
    *
    * @param string value title of the label_item
    */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

   function getMemberItemList(){
      $members = new cs_list();
      $member_ids = $this->getLinkedItemIDArray(CS_USER_TYPE);
      if ( !empty($member_ids) ){
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setIDArrayLimit($member_ids);
         $user_manager->setUserLimit();
         $user_manager->select();
         $members = $user_manager->get();
      }        // returns a cs_list of user_items
      return $members;
   }


   function getCountMemberItemList(){
      $members = $this->getMemberItemList();
      return $members->getCount();
   }

   function getCountAllLinkItemList($addUsers = true){
      $entries = $this->getAllLinkItemList();
      $counter = 0;
      if (!$addUsers) {
          foreach ($entries->to_array() as $entry) {
              if ($entry->getFirstLinkedItemType() != 'user' && $entry->getSecondLinkedItemType() != 'user') {
                    $counter++;
              }
          }
      } else {
        $counter = $entries->getCount();
      }
      return $counter;
   }

   /** checks the data of the item.
    */
   function isValid() {
      $name = $this->getName();
      $type = $this->getLabelType();
      return (!empty($name) and !empty($type));
   }

   /** get name
    * this method returns the name of the label
    *
    * @return string name of the label
    */
   function getName () {
      return $this->_getValue('name');
   }

   /** set name
    * this method sets the name of the label
    *
    * @param string value name of the item
    */
   function setName ($value) {
      $converter = $this->_environment->getTextConverter();
      $value = $converter->sanitizeHTML($value);
      $this->_setValue('name', $value);
   }

   /** set title
    * this method sets the title of the label
    *
    * @param string value title of the item
    */
   function setTitle ($value) {
   	  // sanitize title
   	  $converter = $this->_environment->getTextConverter();
   	  $value = htmlentities($value);
   	  $value = $converter->sanitizeHTML($value);
      $this->_setValue('name', $value);
   }

   /** get title
    * this method returns the name of the label
    *
    * @return string name of the label
    */
   function getTitle () {
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
   	  }else{
         return $this->_getValue('name');
   	  }
   }

   /** get description
    * this method returns the description of the label
    *
    * @return string description of the label
    */
   function getDescription () {
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
   	  }else{
         return $this->_getValue('description');
   	  }
   }

   /** set description
    * this method sets the description of the label
    *
    * @param string value description of the item
    */
   function setDescription ($value) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $value = $converter->sanitizeFullHTML($value);
      $this->_setValue('description', $value);
   }

  /** get the last change item
   * this method returns the last change item linked to this label
   * this is only relevant for groups (which are cs_label_items)
   *
   * @return cs_item last change item
   */
   function getLastChangedItem () {
      if (!$this->_loaded_last_changed_item) {
         $this->_loaded_last_changed_item = TRUE;
         $query = "SELECT items.item_id, items.type, items.modification_date".
                  " FROM items".
                  " LEFT JOIN links".
                  " ON links.from_item_id = items.item_id".
                  " WHERE links.to_item_id = '".$this->getItemID()."'".
                  " ORDER BY items.modification_date DESC".
                  " LIMIT 1";
         $db_connector = $this->_environment->getDBConntector();
         $result = $db_connector->performQuery($query);
         if (!empty($result[0])) {
            $rs = $result[0];
         } else {
            $rs = '';
         }
         $retour = NULL;
         $manager = NULL;
         if(!empty($rs) and !empty($rs['modification_date'])) {
            switch($rs['type']) {
               case CS_DATE_TYPE:
                  $manager = $this->_environment->getDatesManager();
                  break;
               case 'discussions':
               case 'discussion':
                  $manager = $this->_environment->getDiscussionManager();
                  break;
               case 'label':
                  $manager = $this->_environment->getLabelManager();
                  break;
               case 'materials':
                  $manager = $this->_environment->getMaterialManager();
                  break;
               case 'user':
                  $manager = $this->_environment->getUserManager();
                  break;
               case 'annotation':
                  $manager = $this->_environment->getAnnotationManager();
                  break;
            }
            if(!is_null($manager)) {
               $manager->setDeleteLimit(false);
               $item = $manager->getItem($rs['item_id']);
               $manager->setDeleteLimit(true);
               if(empty($item)) {
                  include_once('functions/error_functions.php');trigger_error('cs_label_item: getLastChangedItem(): last changed item is empty for group: "'.$this->getName().'"', E_USER_WARNING);
               }
            } else {
               include_once('functions/error_functions.php');trigger_error('cs_label_item: getLastChangedItem(): Failed finding last changed item of type '.$rs['type'].' for group "'.$this->getName().'"', E_USER_WARNING);
            }
            //$deletionDate = $item->getDeletionDate();
            //if(empty($deletionDate)) {
               $this->_last_changed_item = $item;
            //}
         }
      }
      return $this->_last_changed_item;
   }

   /** get type of label
    * this method returns the type of the label
    *
    * @return string type of the label
    */
   function getLabelType () {
      return $this->_getValue('type');
   }

   /** set label type
    * this method sets the type of the label
    *
    * @param string value type of the item
    *
    * @author CommSy Development Group
    */
   function setLabelType ($value) {
      $this->_setValue('type', (string)$value);
   }

   /** is the label sort criteria ?
    * this method returns a boolean expressing if label is sort criteria or not
    *
    * @return boolean   true - label is sort criteria
    *                   false - label is not sort criteria
    *
    * @author CommSy Development Group
    */
   function isSortCriteria () {
      return $this->_is_sort_criteria;
   }

   /** make label a sort criteria
    * this method makes the label to a sort criteria
    *
    * @param boolean value true - label is sort criteria
    *                      false - label is not sort criteria
    *
    * @author CommSy Development Group
    */
   function makeSortCriteria ($value = true) {
      $this->_is_sort_criteria = $value;
   }

   /** is the label a system generated label ?
    * this method returns a boolean expressing if label is a system generated label or not
    *
    * @return boolean   true - label is a system generated label
    *                   false - label is not a system generated label
    *
    * @author CommSy Development Group
    */
   function isSystemLabel () {
      $retour = false;
      if ( $this->_issetExtra('SYSTEM_LABEL')) {
         $value = $this->_getExtra('SYSTEM_LABEL');
         if ( $value == 1 ) {
            $retour = true;
         }
      }
      return $retour;
   }

   /** make label a system generated label
    * this method makes the label to a system generated label
    *
    * @param boolean value true - label is a system generated label
    *                      false - label is not a system generated label
    */
   function makeSystemLabel ($value = true) {
      if ( $value ) {
         $this->_addExtra('SYSTEM_LABEL',1);
      } else {
         $this->_addExtra('SYSTEM_LABEL',-1);
      }
   }

   /** save news item
    * this methode save the news item into the database
    *
    * @author CommSy Development Group
    */
   function save() {
      $label_manager = $this->_environment->getLabelManager();
      $this->_save($label_manager);

      // prevent indexing of label types like buzzwords
      if (in_array($this->getLabelType(), [
          'group',
          'topic',
          'institution'
      ])) {
            $this->updateElastic();
      }
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_label');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Labels::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

   /** delete label item
    * this methode delete the label item
    *
    * @author CommSy Development Group
    */
   function delete() {
      $manager = $this->_environment->getLabelManager();
      $this->_delete($manager);

      global $symfonyContainer;
      $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_label');
      $em = $symfonyContainer->get('doctrine.orm.entity_manager');
      $repository = $em->getRepository(Labels::class);

      $this->deleteElasticItem($objectPersister, $repository);
   }

    /** set picture filename of the label (used for groups)
    * this method sets the picture filename of the label
    *
    * @param string value picture filename of the label
    *
    * @author CommSy Development Group
    */
   function setPicture ($name) {
      $this->_addExtra('LABELPICTURE',$name);
   }

   /** get picture filename of the label (used for groups)
    * this method gets the picture filename of the label
    *
    * @return string picture filename of the label
    */
   public function getPicture () {
      $retour = '';
      if ($this->_issetExtra('LABELPICTURE')) {
         $retour = $this->_getExtra('LABELPICTURE');
      }
      return $retour;
   }

   public function isMember ($user) {
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

   public function addMember ($user) {
      if ( !$this->isMember($user) ) {
         $link_manager = $this->_environment->getLinkItemManager();
         $link_item = $link_manager->getNewItem();
         $link_item->setFirstLinkedItem($this);
         $link_item->setSecondLinkedItem($user);
         $link_item->save();
      }
   }

   public function removeMember ($user) {
      $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
      $link_member_item = $link_member_list->getFirst();
      while ( $link_member_item ) {
         $linked_user_id = $link_member_item->getLinkedItemID($this);
         if ( isset($user)
              and is_object($user)
              and $user->isA(CS_USER_TYPE)
              and $user->getItemID() == $linked_user_id
            ) {
            $link_member_item->delete();
         }
         $link_member_item = $link_member_list->getNext();
      }
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

   /** sets if announcement is editable by everybody or just creator
    *
    * @param value
    */
   function setPublic ($value) {
      $this->_setValue('public', $value);
   }

   /** change creator and modificator of the label
    * needed for saving group all, because at the first saving, creator ist user from
    * community room
    *
    * @param user cs_user_item
    */
   function changeCreatorItemAndModificatorItemTo ($user) {
      $this->_changeCreatorItemAndModificatorItemTo($user,$this->_environment->getLabelManager());
   }

    /** returns whether the given user may edit the label item or not,
     * but will always prevent editing if the label item is a system label
     */
    function mayEdit(cs_user_item $user_item)
    {
        if ($this->isSystemLabel()) {
            return false;
        }

        $mayEditItem = parent::mayEdit($user_item);

        return $mayEditItem;
    }
}