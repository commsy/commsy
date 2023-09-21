<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for database connection to the database table "tasks"
 * this class implements a database manager for the table "tasks".
 */
class cs_tasks_manager extends cs_manager
{
    /**
     * string - containing a limit for the status.
     */
    public $_status_limit = null;

    /**
     * string - containing an order limit for the select news.
     */
    public $_order = null;

    /**
     * integer - containing a start point for the select task.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many task the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - containing a string as a search limit.
     */
    public $_search_limit = null;

    public $_linked_id_limit;

    /** constructor: cs_tasks_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        cs_manager::__construct($environment);
        $this->_db_table = 'tasks';
    }

    /** reset limits
     * reset limits of this class: status limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_status_limit = null;
        $this->_order = null;
        $this->_search_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $_linked_id_limit = null;
    }

    /** set status limit
     * this method sets a status limit for tasks.
     *
     * @param string limit status limit for tasks
     */
    public function setStatusLimit($limit)
    {
        $this->_status_limit = (string) $limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected tasks
     * @param int interval interval limit for selected tasks
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    /** set order limit -> internal, do not use
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected news
     */
    public function setSortOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    public function setTaskSearchLimit($limit)
    {
        $this->_search_limit = encode(AS_DB, (string) $limit);
    }

    public function setLinkedIDLimit($value)
    {
        $this->_linked_id_limit = (int) $value;
    }

    /** select tasks limited by limits
     * this method returns a list (cs_list) of tasks within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix('tasks').'.item_id)';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix('tasks').'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix('tasks').'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix('tasks');
        // now joins if necessary
        $query .= ' WHERE 1';

        // insert limits into the select statement
        if (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('tasks').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('tasks').'.deleter_id IS NULL';
        }
        if (isset($this->_status_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('tasks').'.status = "'.encode(AS_DB, $this->_status_limit).'"';
        }
        if (isset($this->_linked_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('tasks').'.linked_item_id = "'.encode(AS_DB, $this->_linked_id_limit).'"';
        }

        // restrict sql-statement by search limit, create wheres
        elseif (isset($this->_search_limit) and !empty($this->_search_limit)) {
            $query .= ' AND (';
            $query .= ' UPPER('.$this->addDatabasePrefix('tasks').'.title) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';
            $query .= ' OR';
            $query .= ' UPPER('.$this->addDatabasePrefix('tasks').'.status) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%")';
        }

        if (isset($this->_sort_order)) {
            if ('date' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date DESC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
            } elseif ('date_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date ASC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
            } elseif ('status' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.status ASC';
            } elseif ('status_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.status DESC';
            } elseif ('title_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.title DESC';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.title ASC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('tasks').'.modification_date DESC, '.$this->addDatabasePrefix('tasks').'.title ASC, '.$this->addDatabasePrefix('tasks').'.status DESC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting task items from query: "'.$query.'"', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    /** get a tasks.
     *
     * @param int item_id id of the item
     *
     * @return object cs_item a task
     */
    public function getItem(?int $item_id)
    {
        $task = null;
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('tasks').' WHERE '.$this->addDatabasePrefix('tasks').".item_id = '".encode(AS_DB, $item_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
            trigger_error('Problems selecting one task from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $task = $this->_buildItem($result[0]);
        }

        return $task;
    }

     /** build a new task item
      * this method returns a new EMTPY material item.
      *
      * @return \cs_task_item
      */
     public function getNewItem()
     {
         return new cs_task_item($this->_environment);
     }

     /** get a list of items (newest version)
      * this method returns a list of items.
      *
      * @param array id_array ids of the items
      *
      * @return cs_list list of cs_items
      */
     public function getItemList(array $id_array)
     {
         return $this->_getItemList('tasks', $id_array);
     }

    /** save a commsy item
     * this method saves a commsy item.
     *
     * @param cs_item
     */
    public function saveItem($item)
    {
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

        // Add modifier to all users who ever edited this section
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_modifier_item_manager->markEdited($item->getItemID());
        unset($link_modifier_item_manager);
        unset($item);
    }

    /** update a task - internal, do not use -> use method save
     * this method updates a task.
     *
     * @param object cs_item task_item the task
     */
    public function _update($item)
    {
        parent::_update($item);
        $query = 'UPDATE '.$this->addDatabasePrefix('tasks').' SET '.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'title="'.encode(AS_DB, $item->getTitle()).'",'.
//              'linked_item_id="'.$item->getLinkedItemID().'",'.
                 'status="'.encode(AS_DB, $item->getStatus()).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        // extras (TBD)
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updateing task items from query: "'.$query.'"', E_USER_WARNING);
        }
        unset($item);
    }

    /** create a task - internal, do not use -> use method save
     * this method creates a task.
     *
     * @param object cs_item task_item the task
     */
    public function _create($item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'type="task"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating task item in items table from query: "'.$query.'"', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $item->setItemID($this->getCreateID());
            $this->_newTask($item);
        }
    }

    /** creates a task - internal, do not use -> use method save
     * this method creates a task.
     *
     * @param object cs_item task_item the task
     */
    public function _newTask($item)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $item->getCreatorItem();
        $linked_item = $item->getItem();
        $query = 'INSERT INTO '.$this->addDatabasePrefix('tasks').' SET '.
                 'item_id="'.encode(AS_DB, $item->getItemID()).'",'.
                 'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                 'creator_id="'.encode(AS_DB, $current_user->getItemID()).'",'.
                 'creation_date="'.$current_datetime.'",'.
                 'modification_date="'.$current_datetime.'",'.
                 'title="'.encode(AS_DB, $item->getTitle()).'",'.
                 'linked_item_id="'.encode(AS_DB, $linked_item->getItemID()).'",'.
                 'status="'.encode(AS_DB, $item->getStatus()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating task item from query: "'.$query.'"', E_USER_WARNING);
        }
        unset($item);
        unset($current_user);
        unset($linked_item);
    }

    /**
     * Returns all existing task for an item.
     */
    public function getTaskListForItem($item)
    {
        $item_id = $item->getItemID();
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('tasks').' WHERE linked_item_id="'.encode(AS_DB, $item_id).'"';
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
     * this method deletes a new task.
     *
     * @param int item_id item id of the task
     */
    public function delete(int $itemId): void
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        unset($current_user);
        $query = 'UPDATE '.$this->addDatabasePrefix('tasks').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'",'.
                 'status="CLOSED"'.
                 ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting tasks from query: "'.$query.'"', E_USER_WARNING);
        } else {
            parent::delete($itemId);
        }
    }
}
