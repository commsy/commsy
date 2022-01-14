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
   function __construct($environment) {
      cs_item::__construct($environment);
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
   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
   	  }else{
         return $this->_getValue('title');
   	  }
   }

  /** set title of a todo
   * this method sets the title of the todo
   *
   * @param string value title of the todo
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

  /** get description of a todo
   * this method returns the description of the todo
   *
   * @return string description of a todo
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

  /** set description of a todo
   * this method sets the description of the todo
   *
   * @param string value description of the todo
   *
   * @author CommSy Development Group
   */
   function setDescription($description) {
   	  // sanitize description
   	  $converter = $this->_environment->getTextConverter();
   	  $description = $converter->sanitizeFullHTML($description);
      $this->_setValue('description', $description);
   }

  /** get date of a todo
   * this method returns the date of the todo
   *
   * @return datetime date of a todo
   */
   function getDate () {
      $date = $this->_getValue('end_date');
      return $date;
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
      $translator = $this->_environment->getTranslationObject();
      $value = $this->_getValue('status');
      if ($value =='2') {
        return $translator->getMessage('TODO_IN_POGRESS');
      } elseif ($value =='3') {
        return $translator->getMessage('TODO_DONE');
      } else {
        // return $translator->getMessage('TODO_NOT_STARTED');
         $context_item = $this->_environment->getCurrentContextItem();
         $extra_status_array = $context_item->getExtraToDoStatusArray();
         if (isset($extra_status_array[$value])){
            return $extra_status_array[$value];
         }else{
            return $translator->getMessage('TODO_NOT_STARTED');
         }
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


   function getFileListWithFilesFromSteps () {
      $file_list = new cs_list;

   	  if ($this->getPublic()=='-1'){
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $file_list;
   	  }else{
      	 $files = $this->getFileList();
	      // steps
	      $context_item = $this->_environment->getCurrentContextItem();
	      if ($context_item->withTodoManagement()){
	         $step_list = clone $this->getStepItemList();
	         if ( $step_list->isNotEmpty() ) {
	            $step_item = $step_list->getFirst();
	            while ($step_item) {
	               $step_file_list = $step_item->getFileList();
	               if ( $step_file_list->isNotEmpty() ) {
	                  $file_list->addList($step_file_list);
	               }
	               unset($step_item);
	               $step_item = $step_list->getNext();
	            }
	         }
	         unset($step_item);
	         unset($step_list);
	         $files->addList($file_list);
	      }
	      $files->sortby('filename');
   	  }
      return $files;
   }

   function getProcessorItemList(){
      $members = new cs_list();
      $member_ids = $this->getLinkedItemIDArray(CS_USER_TYPE);
      if ( !empty($member_ids) ){
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setIDArrayLimit($member_ids);
         $user_manager->select();
         $members = $user_manager->get();
      }
      // returns a cs_list of user_items
      return $members;
   }

    /**
     * @return \cs_list
     */
   function getStepItemList(){
      $stepManager = $this->_environment->getStepManager();

      $stepManager->reset();
      $stepManager->setContextLimit($this->getContextID());
      $stepManager->setTodoItemIDLimit($this->getItemID());

      $stepManager->select();
      /** @var \cs_list $stepItems */
      $stepItems = $stepManager->get();

      return $stepItems;
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

   function setPlannedTime($time){
      $time = str_replace(',','.',$time);
      $this->_setValue('minutes', $time);

   }

   function getPlannedTime(){
      $time = $this->_getValue('minutes');
      if (!isset($time) or empty($time)){
         $time = 0;
      }
      return $time;
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

      $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_todo');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('App:Todos');

        $this->replaceElasticItem($objectPersister, $repository);
    }

  /** delete todo item
   * this methode delete the todo item
   *
   * @author CommSy Development Group
   */
   function delete() {
      $todo_manager = $this->_environment->getTodosManager();
      $this->_delete($todo_manager);

      // delete steps
      $step_item_list = $this->getStepItemList();
      if ( $step_item_list->isNotEmpty() ) {
         $step_item = $step_item_list->getFirst();
         while ($step_item) {
            $step_item->delete();
            $step_item = $step_item_list->getNext();
         }
      }

      global $symfonyContainer;
      $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_todo');
      $em = $symfonyContainer->get('doctrine.orm.entity_manager');
      $repository = $em->getRepository('App:Todos');

      $this->deleteElasticItem($objectPersister, $repository);
   }

   /** Checks and sets the data of the todo_item.
    *
    * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
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
      $copy->setTopicList($list);
      $copy->save();
      $copy_id = $copy->getItemID();
      $step_list = $this->getStepItemList();
      $step_item = $step_list->getFirst();
      while ( $step_item ) {
         $step_item_copy = $step_item->cloneCopy();
         $step_item_copy->setItemID('');
         $file_list = $step_item->getFileList();
         if ($file_list->isNotEmpty()) {
            $file_item = $file_list->getFirst();
            while ($file_item) {
               $file_item->setTempName($file_item->getDiskFilename());
               $file_item = $file_list->getNext();
            }
            $step_item_copy->setFileList($file_list);
         }
         $step_item_copy->setContextID($this->_environment->getCurrentContextID());
         $user = $this->_environment->getCurrentUserItem();
         $step_item_copy->setCreatorItem($user);
         $step_item_copy->setModificatorItem($user);
         $step_item_copy->setToDoID($copy_id);
         $step_item_copy->save();

         // error while saving files?
         $error_array = $step_item_copy->getErrorArray();
         if ( !empty($error_array) ) {
            $error_array_sum = array_merge($error_array,$error_array_sum);
         }
         if (!empty($error_array_sum)) {
            $copy->setErrorArray($error_array_sum);
         }
         if ($step_item->isDeleted()) {
            $step_item_copy->delete();
         }
         $step_item = $step_list->getNext();
      }
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