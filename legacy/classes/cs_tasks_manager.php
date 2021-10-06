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

/** cs_list is needed for storage of the task items
 */
include_once('classes/cs_list.php');

/** upper class of the task manager
 */
include_once('classes/cs_manager.php');

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** class for database connection to the database table "tasks"
 * this class implements a database manager for the table "tasks"
 */
class cs_tasks_manager extends cs_manager {

  /**
   * string - containing a limit for the status
   */
  var $_status_limit = NULL;

  /**
   * string - containing an order limit for the select news
   */
  var $_order = NULL;

  /**
   * integer - containing a start point for the select task
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many task the select statement should get
   */
  var $_interval_limit = NULL;

   /**
    * string - containing a string as a search limit
    */
   var $_search_limit = NULL;


  var $_linked_id_limit;

  /** constructor: cs_tasks_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     cs_manager::__construct($environment);
     $this->_db_table = 'tasks';
  }

  /** reset limits
    * reset limits of this class: status limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_status_limit = NULL;
     $this->_order = NULL;
     $this->_search_limit = NULL;
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $_linked_id_limit = NULL;
  }

  /** set status limit
    * this method sets a status limit for tasks
    *
    * @param string limit status limit for tasks
    */
  function setStatusLimit ($limit) {
     $this->_status_limit = (string)$limit;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected tasks
    * @param integer interval interval limit for selected tasks
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (integer)$interval;
     $this->_from_limit = (integer)$from;
  }

  /** set order limit -> internal, do not use
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected news
    */
  function setSortOrder ($limit) {
     $this->_order = (string)$limit;
  }

  function setTaskSearchLimit ($limit) {
     $this->_search_limit = encode(AS_DB,(string)$limit);
  }

  function setLinkedIDLimit($value){
     $this->_linked_id_limit= (int)$value;
  }

  /** select tasks limited by limits
    * this method returns a list (cs_list) of tasks within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->addDatabasePrefix('tasks').'.item_id)';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('tasks').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('tasks').'.*';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('tasks');
     // now joins if necessary
     $query .= ' WHERE 1';

     // insert limits into the select statement
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('tasks').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('tasks').'.deleter_id IS NULL';
     }
     if (isset($this->_status_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('tasks').'.status = "'.encode(AS_DB,$this->_status_limit).'"';
     }
     if (isset($this->_linked_id_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('tasks').'.linked_item_id = "'.encode(AS_DB,$this->_linked_id_limit).'"';
     }

      // restrict sql-statement by search limit, create wheres
      elseif (isset($this->_search_limit) AND !empty($this->_search_limit)) {
        $query .= ' AND (';
        $query .= ' UPPER('.$this->addDatabasePrefix('tasks').'.title) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%"';
        $query .= ' OR';
        $query .= ' UPPER('.$this->addDatabasePrefix('tasks').'.status) LIKE BINARY "%'.encode(AS_DB,$this->_search_limit).'%")';
     }

     if (isset($this->_sort_order)) {
        if ($this->_sort_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date DESC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
        } elseif ($this->_sort_order == 'date_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date ASC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
        } elseif ($this->_sort_order == 'status') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.status ASC';
        } elseif ($this->_sort_order == 'status_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.status DESC';
        } elseif ($this->_sort_order == 'title_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.title DESC';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.title ASC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date DESC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
     }

     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
        }
     }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting task items from query: "'.$query.'"', E_USER_WARNING);
     } else {
        return $result;
     }
  }

  /** get a tasks
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a task
    */
  function getItem ($item_id) {
     $task = NULL;
     $query = "SELECT * FROM ".$this->addDatabasePrefix("tasks")." WHERE ".$this->addDatabasePrefix("tasks").".item_id = '".encode(AS_DB,$item_id)."'";
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0])) {
        include_once('functions/error_functions.php');trigger_error('Problems selecting one task from query: "'.$query.'"',E_USER_WARNING);
     } else {
        $task = $this->_buildItem($result[0]);
     }
     return $task;
  }

  /** build a new task item
    * this method returns a new EMTPY material item
    *
    * @return \cs_task_item
    */
   function getNewItem () {
      include_once('classes/cs_task_item.php');
      return new cs_task_item($this->_environment);
   }

  /** get a list of items (newest version)
    * this method returns a list of items
    *
    * @param array id_array ids of the items
    *
    * @return cs_list list of cs_items
    */
   function getItemList($id_array) {
      return $this->_getItemList('tasks', $id_array);
   }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();
     if (!empty($item_id)) {
        $this->_update($item);
     } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $temp_user = $this->_environment->getCurrentUser();
           $item->setCreatorItem($temp_user);
        }
        $this->_create($item);
     }

     //Add modifier to all users who ever edited this section
     $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
     $link_modifier_item_manager->markEdited($item->getItemID());
     unset($link_modifier_item_manager);
     unset($item);
  }

  /** update a task - internal, do not use -> use method save
    * this method updates a task
    *
    * @param object cs_item task_item the task
    */
  function _update ($item) {
     parent::_update($item);
     $query = 'UPDATE '.$this->addDatabasePrefix('tasks').' SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
#              'linked_item_id="'.$item->getLinkedItemID().'",'.
              'status="'.encode(AS_DB,$item->getStatus()).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
     // extras (TBD)
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems updateing task items from query: "'.$query.'"', E_USER_WARNING);
     }
     unset($item);
  }

  /** create a task - internal, do not use -> use method save
    * this method creates a task
    *
    * @param object cs_item task_item the task
    */
  function _create ($item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="task"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating task item in items table from query: "'.$query.'"', E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_newTask($item);
     }
  }

  /** creates a task - internal, do not use -> use method save
    * this method creates a task
    *
    * @param object cs_item task_item the task
    */
  function _newTask ($item) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $item->getCreatorItem();
     $linked_item = $item->getItem();
     $query = 'INSERT INTO '.$this->addDatabasePrefix('tasks').' SET '.
              'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'creator_id="'.encode(AS_DB,$current_user->getItemID()).'",'.
              'creation_date="'.$current_datetime.'",'.
              'modification_date="'.$current_datetime.'",'.
              'title="'.encode(AS_DB,$item->getTitle()).'",'.
              'linked_item_id="'.encode(AS_DB,$linked_item->getItemID()).'",'.
              'status="'.encode(AS_DB,$item->getStatus()).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating task item from query: "'.$query.'"', E_USER_WARNING);
     }
     unset($item);
     unset($current_user);
     unset($linked_item);
  }

  /**
   * Returns all existing task for an item
   */
  function getTaskListForItem($item) {
     $item_id = $item->getItemID();
     $query = 'SELECT * FROM '.$this->addDatabasePrefix('tasks').' WHERE linked_item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     $task_list = new cs_list();
     foreach ($result as $query_result) {
         $task_item = $this->_buildItem($query_result);
         $task_list->add($task_item);
         unset($task_item);
     }
     unset($item);
     return $task_list;
  }

   /** delete a task
    * this method deletes a new task
    *
    * @param integer item_id item id of the task
    */
  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID() ?: 0;
     unset($current_user);
     $query = 'UPDATE '.$this->addDatabasePrefix('tasks').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'",'.
              'status="CLOSED"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting tasks from query: "'.$query.'"',E_USER_WARNING);
     } else {
        parent::delete($item_id);
     }
  }
}
?>