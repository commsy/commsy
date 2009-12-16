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

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** upper class of the totos manager
 */
include_once('classes/cs_manager.php');

include_once('functions/text_functions.php');

/** class for database connection to the database table "todo"
 * this class implements a database manager for the table "todo"
 */

class cs_todos_manager extends cs_manager {

   var $_age_limit = NULL;
   var $_future_limit = NULL;
   var $_from_limit = NULL;
   var $_interval_limit = NULL;
   var $_search_limit = NULL;
   var $_id_array_limit = array();
   var $_group_limit = NULL;
   var $_topic_limit = NULL;
   var $_sort_order = NULL;
   
   /*
    * Translation Object
    */
   private $_translator = null;

   /** constructor: cs_todo_manager
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @param object cs_environment the environment
    */
   function cs_todos_manager ($environment) {
      $this->cs_manager($environment);
      $this->_db_table = 'todos';
      $this->_translator = $environment->getTranslationObject();
   }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
   */
   function resetLimits () {
      parent::resetLimits();
      $this->_age_limit = NULL;
      $this->_future_limit = NULL;
      $this->_status_limit = NULL;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_search_limit = NULL;
      $this->_group_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_user_limit = NULL;
      $this->_sort_order = NULL;
   }

   /** set age limit
    * this method sets an age limit for todo
    *
    * @param integer limit age limit for todo
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   function setStatusLimit ($limit) {
      $this->_status_limit = (int)$limit;
   }
   /** set future limit
    * Restricts selected dates to dates in the future
    */
   function setFutureLimit () {
      $this->_future_limit = TRUE;
   }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected todo
    * @param integer interval interval limit for selected todo
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

   function setGroupLimit ($limit) {
      $this->_group_limit = (int)$limit;
   }

   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

   function _performQuery ( $mode = 'select' ) {
      if ($mode == 'count') {
         $query = 'SELECT count(todos.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT todos.item_id';
      } elseif ($mode == 'distinct') {
         $query = 'SELECT DISTINCT '.$this->_db_table.'.*';
      } else {
         $query = 'SELECT todos.*';
      }
      $query .= ' FROM todos';

     if ( ( isset($this->_search_array) AND !empty($this->_search_array) )
        ) {
        $query .= ' LEFT JOIN step ON (step.todo_item_id = todos.item_id AND step.context_id = "'.encode(AS_DB,$this->_room_limit).'")';
     }
     if ( !empty($this->_search_array) ||
           (isset($this->_sort_order) and
           ($this->_sort_order == 'modificator' || $this->_sort_order == 'modificator_rev')) ) {
         $query .= ' LEFT JOIN user AS people ON (people.item_id=todos.creator_id )'; // modificator_id (TBD)
     }
     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN link_items AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id=todos.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id=todos.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN link_items AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id=todos.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id=todos.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }

     if ( isset($this->_user_limit) ) {
        $query .= ' LEFT JOIN link_items AS user_limit1 ON ( user_limit1.deletion_date IS NULL AND ((user_limit1.first_item_id=todos.item_id AND user_limit1.second_item_type="'.CS_USER_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS user_limit2 ON ( user_limit2.deletion_date IS NULL AND ((user_limit2.second_item_id=todos.item_id AND user_limit2.first_item_type="'.CS_USER_TYPE.'"))) ';
     }

     if ( isset($this->_tag_limit) ) {
        $tag_id_array = $this->_getTagIDArrayByTagID($this->_tag_limit);
        $query .= ' LEFT JOIN link_items AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id=todos.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
        $query .= ' LEFT JOIN link_items AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id=todos.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
     }

      // restrict todos by buzzword (la4)
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit == -1){
            $query .= ' LEFT JOIN links AS l6 ON l6.from_item_id=todos.item_id AND l6.link_type="buzzword_for"';
            $query .= ' LEFT JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }else{
            $query .= ' INNER JOIN links AS l6 ON l6.from_item_id=todos.item_id AND l6.link_type="buzzword_for"';
            $query .= ' INNER JOIN labels AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
         }
      }

      if (isset($this->_ref_id_limit)) {
         $query .= ' INNER JOIN link_items AS l5 ON ( (l5.first_item_id=todos.item_id AND l5.second_item_id="'.encode(AS_DB,$this->_ref_id_limit).'")
                     OR(l5.second_item_id=todos.item_id AND l5.first_item_id="'.encode(AS_DB,$this->_ref_id_limit).'") AND l5.deleter_id IS NULL)';
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' INNER JOIN item_link_file AS lf ON '.$this->_db_table.'.item_id = lf.item_iid';
      }

      $query .= ' WHERE 1';
      if ( isset($this->_room_limit) ) {
         $query .= ' AND todos.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
      if (!$this->_show_not_activated_entries_limit) {
         $query .= ' AND (todos.modification_date IS NULL OR todos.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
      }
#      if ( $this->_future_limit ) {
#         $date = date("Y-m-d").' 00:00:00';
#         $query .= ' AND todos.date >= "'.$date.'"';
#      }
      if ( $this->_delete_limit == true ) {
         $query .= ' AND todos.deleter_id IS NULL';
      }
      if (isset($this->_ref_user_limit)) {
         $query .= ' AND (todos.creator_id = "'.encode(AS_DB,$this->_ref_user_limit).'" )';
      }
      if (isset($this->_status_limit)) {
         if ($this->_status_limit == 4){
            $query .= ' AND (todos.status != "3")';
         }else{
            $query .= ' AND (todos.status = "'.encode(AS_DB,$this->_status_limit).'" )';
         }
      }
      if ( isset($this->_age_limit) ) {
         $query .= ' AND todos.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
      if ( isset($this->_existence_limit) ) {
         $query .= ' AND todos.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
      }
      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND todos.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }
      if ( isset($this->_topic_limit) ){
         if($this->_topic_limit == -1){
            $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
            $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
            $query .= ' OR (l22.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l22.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'"))';
         }
      }
      if ( isset($this->_group_limit) ){
         if($this->_group_limit == -1){
            $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
            $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
            $query .= ' OR (l32.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$this->_group_limit).'"))';
         }
      }
      if ( isset($this->_user_limit) ){
         if($this->_user_limit == -1){
            $query .= ' AND (user_limit1.first_item_id IS NULL AND user_limit1.second_item_id IS NULL)';
            $query .= ' AND (user_limit2.first_item_id IS NULL AND user_limit2.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((user_limit1.first_item_id = "'.encode(AS_DB,$this->_user_limit).'" OR user_limit1.second_item_id = "'.encode(AS_DB,$this->_user_limit).'")';
            $query .= ' OR (user_limit2.first_item_id = "'.encode(AS_DB,$this->_user_limit).'" OR user_limit2.second_item_id = "'.encode(AS_DB,$this->_user_limit).'"))';
         }
      }
      if ( isset($this->_tag_limit) ) {
         $tag_id_array = $this->_getTagIDArrayByTagID($this->_tag_limit);
         $id_string = implode(', ',$tag_id_array);
         if( isset($tag_id_array[0]) and $tag_id_array[0] == -1 ){
            $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
            $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
         }else{
            $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB,$id_string).') OR l41.second_item_id IN ('.encode(AS_DB,$id_string).') )';
            $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB,$id_string).') OR l42.second_item_id IN ('.encode(AS_DB,$id_string).') ))';
         }
      }
      if (isset($this->_buzzword_limit)) {
         if ($this->_buzzword_limit ==-1){
            $query .= ' AND (l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL)';
         }else{
            $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_buzzword_limit).'"';
         }
      }

      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND step.deletion_date IS NULL';
         $query .= ' AND (';
         $field_array = array('TRIM(CONCAT(people.firstname," ",people.lastname))','todos.description','todos.title','step.title','step.description');
         $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
         $query .= $search_limit_query_code;
         $query .= ')';
      }
      // init and perform ft search action
      if (!empty($this->_search_array)) {
         $query .= $this->initFTSearch();
      }

      // only files limit -> entries with files
      if ( isset($this->_only_files_limit) and $this->_only_files_limit ) {
         $query .= ' AND lf.deleter_id IS NULL AND lf.deletion_date IS NULL';
      }

      // order
      if ( isset($this->_sort_order) ) {
         if ( $this->_sort_order == 'modified' ) {
            $query .= ' ORDER BY todos.modification_date DESC';
         } elseif ( $this->_sort_order == 'modified_rev' ) {
            $query .= ' ORDER BY todos.modification_date';
         } elseif ( $this->_sort_order == 'title' ) {
            $query .= ' ORDER BY todos.title';
         } elseif ( $this->_sort_order == 'title_rev' ) {
            $query .= ' ORDER BY todos.title DESC';
         } elseif ( $this->_sort_order == 'date' ) {
            $query .= ' ORDER BY todos.date';
         } elseif ( $this->_sort_order == 'date_rev' ) {
            $query .= ' ORDER BY todos.date DESC';
         } elseif ( $this->_sort_order == 'status' ) {
            $query .= ' ORDER BY todos.status';
         } elseif ( $this->_sort_order == 'status_rev' ) {
            $query .= ' ORDER BY todos.status DESC';
         }
      }else{
         $query .= ' ORDER BY todos.date DESC';
      }

      if ( $mode == 'select' ) {
         if ( isset($this->_interval_limit) and isset($this->_from_limit) ) {
            $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
         }
      }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $i=0;
         while( isset($result[$i]) ){
            if ( isset($result[$i]['date']) ){
               $result[$i]['end_date'] = $result[$i]['date'];
               unset($result[$i]['date']);
            }
            $i++;
         }
         return $result;
      }
   }

   /** build a new todo item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    */
   function getNewItem () {
      include_once('classes/cs_todo_item.php');
      return new cs_todo_item($this->_environment);
   }

  /** get a todo
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a todo
    */
   function getItem ($item_id) {
      $todo = NULL;
      if (array_key_exists($item_id,$this->_cached_items)){
         return $this->_buildItem($this->_cached_items[$item_id]);
      }else{
         $query = "SELECT * FROM todos WHERE todos.item_id = '".encode(AS_DB,$item_id)."'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or empty($result[0])) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one todos item from query: "'.$query.'"',E_USER_WARNING);
         } else {
            if ( isset($result[0]['date']) ){
               $result[0]['end_date'] = $result[0]['date'];
               unset($result[0]['date']);
            }
            $todo = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
         }
         return $todo;
      }
   }

  /** get a list of todo in newest version
    *
    * @param array id_array ids of the items
    *
    * @return object cs_list of cs_todo_items
    */
   function getItemList ($id_array) {
      return $this->_getItemList("todo", $id_array);
   }

  /** update a todo - internal, do not use -> use method save
   * this method updates the database record for a given todo item
   *
   * @param cs_todo_item the todo item for which an update should be made
   */
   function _update ($item) {
      parent::_update($item);

      $modificator = $item->getModificatorItem();
      $modification_date = getCurrentDateTimeInMySQL();

      if ( $item->isPublic() ) {
         $public = '1';
      } else {
         $public = '0';
      }
      if ($item->isNotActivated()){
         $modification_date = $item->getModificationDate();
      }
      $query = 'UPDATE todos SET '.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$modification_date.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
               'date="'.encode(AS_DB,$item->getDate()).'",'.
               'status="'.encode(AS_DB,$item->getInternalStatus()).'",'.
               'minutes="'.encode(AS_DB,$item->getPlannedTime()).'",'.
               'time_type="'.encode(AS_DB,$item->getTimeType()).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'description="'.encode(AS_DB,$item->getDescription()).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
      // extras (TBD)
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating todos from query: "'.$query.'"',E_USER_WARNING);
      }
   }

  /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'todo' in the database and sets the todo items item id.
   * it then calls the private method _newNews to store the todo item itself.
   * @param cs_todo_item the todo item for which an entry should be made
   */
  function _create ($item) {
     $query = 'INSERT INTO items SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="todo"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating todo from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newNews($item);
     }
     unset($item);
  }

  /** store a new todo item to the database - internal, do not use -> use method save
    * this method stores a newly created todo item to the database
    *
    * @param cs_todo_item the todo item to be stored
    */
   function _newNews ($item) {
      $user = $item->getCreatorItem();
      $modificator = $item->getModificatorItem();
      $modification_date = getCurrentDateTimeInMySQL();
      $current_datetime = getCurrentDateTimeInMySQL();

      if ( $item->isPublic() ) {
         $public = '1';
      } else {
         $public = '0';
      }

      $date = $item->getDate();
      if ($item->isNotActivated()){
         $modification_date = $item->getModificationDate();
      }

      $query = 'INSERT INTO todos SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modifier_id="'.encode(AS_DB,$modificator->getItemID()).'",'.
               'modification_date="'.$modification_date.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",';
      if ( !empty($date) ) {
         $query .= 'date="'.encode(AS_DB,$item->getDate()).'",';
      }
      $query .= 'status="'.encode(AS_DB,$item->getInternalStatus()).'",'.
               'minutes="'.encode(AS_DB,$item->getPlannedTime()).'",'.
               'time_type="'.encode(AS_DB,$item->getTimeType()).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'description="'.encode(AS_DB,$item->getDescription()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating todos from query: "'.$query.'"',E_USER_WARNING);
      }
      unset($item);
      unset($user);
      unset($modificator);
   }

  /**  delete a todo item
   *
   * @param cs_todo_item the todo item to be deleted
   *
   * @access public
   */
   function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID();
      $query = 'UPDATE todos SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
         unset($link_manager);
         parent::delete($item_id);
      }
      unset($current_user);
   }

   ########################################################
   # statistic functions
   ########################################################

   function getCountTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(todos.item_id) as number FROM todos WHERE todos.context_id = '".encode(AS_DB,$this->_room_limit)."' and ((todos.creation_date > '".encode(AS_DB,$start)."' and todos.creation_date < '".encode(AS_DB,$end)."') or (todos.modification_date > '".encode(AS_DB,$start)."' and todos.modification_date < '".encode(AS_DB,$end)."'))";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all todos from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountNewTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(todos.item_id) as number FROM todos WHERE todos.context_id = '".encode(AS_DB,$this->_room_limit)."' and todos.creation_date > '".encode(AS_DB,$start)."' and todos.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting todos from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function getCountModTodos ($start, $end) {
      $retour = 0;

      $query = "SELECT count(todos.item_id) as number FROM todos WHERE todos.context_id = '".encode(AS_DB,$this->_room_limit)."' and todos.modification_date > '".encode(AS_DB,$start)."' and todos.modification_date < '".encode(AS_DB,$end)."' and todos.modification_date != todos.creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting todos from query:<br />"'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
      }

      return $retour;
   }

   function deleteTodosOfUser($uid) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $query  = 'SELECT todos.* FROM todos WHERE todos.creator_id = "'.encode(AS_DB,$uid).'"';
      $result = $this->_db_connector->performQuery($query);
      if (!empty($result)) {
         foreach ( $result as $rs ) {
            $insert_query = 'UPDATE todos SET';
            $insert_query .= ' title = "'.encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
            $insert_query .= ' description = "'.encode(AS_DB,$this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
            $insert_query .= ' modification_date = "'.$current_datetime.'",';
            $insert_query .= ' public = "1"';
            $insert_query .=' WHERE item_id = "'.encode(AS_DB,$rs['item_id']).'"';
            $result2 = $this->_db_connector->performQuery($insert_query);
            if ( !isset($result2) or !$result2 ) {
               include_once('functions/error_functions.php');trigger_error('Problems automatic deleting todos from query: "'.$insert_query.'"',E_USER_WARNING);
            }
         }
      }
   }
}
?>