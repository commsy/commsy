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

/** class for database connection to the database table "noticed"
 * this class implements a database manager for the table "noticed". Read items.
 */
class cs_noticed_manager
{
    /**
     * object cs_user_item - containing the current user.
     */
    public $_current_user;
    public $_current_user_id;
    public $_db_connector;
    public $_rubric_id_array = [];
    public $_noticed_id_array = [];
    public $_cache_on = true;

    private cs_environment $_environment;

    /** constructor: cs_noticed_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment the environment
     */
    public function __construct(cs_environment $environment)
    {
        $this->_environment = $environment;

        $this->_current_user = $this->_environment->getCurrentUser();
        $this->_current_user_id = $this->_current_user->getItemID();
        $this->_db_connector = $this->_environment->getDBConnector();
    }

    /** reset limits
     * reset limits of this class.
     *
     * @version $Revision$
     */
    public function resetLimits()
    {
    }

    public function resetData()
    {
        $this->_noticed_id_array = [];
        $this->_rubric_id_array = [];
    }

    public function setCacheOff()
    {
        $this->_cache_on = false;
    }

    /** has the current user read a specific item
     * this method returns the latest version_id of an item, the user
     * has already read. Or false, if s/he never read this item.
     *
     * @param int item_id    id of the item
     *
     * @return array contains the latest version_id and read_date
     */
    public function getLatestnoticed($item_id)
    {
        if (in_array($item_id, $this->_rubric_id_array)) {
            if (array_key_exists($item_id, $this->_noticed_id_array)) {
                return $this->_noticed_id_array[$item_id];
            } else {
                return false;
            }
        } else {
            return $this->getLatestnoticedForUserByID($item_id, $this->_current_user_id);
        }
    }

    public function getLatestNoticedForUserByID($item_id, $user_id)
    {
        $noticed = [];
        $query = 'SELECT version_id, read_date FROM '.$this->addDatabasePrefix('noticed').
                  ' WHERE item_id="'.encode(AS_DB, $item_id).'"'.
                  ' AND   user_id="'.encode(AS_DB, $user_id).'"'.
                  ' ORDER BY read_date DESC';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting noticed from query: "'.$query.'"');
        } else {
            $noticed = [];
            if (!empty($result[0])) {
                $noticed['version_id'] = $result[0]['version_id'];
                $noticed['read_date'] = $result[0]['read_date'];
            }
        }

        return $noticed;
    }

    public function getLatestNoticedByIDArray($id_array, $user_id = 0)
    {
        // ------------------
        // --->UTF8 - OK<----
        // ------------------
        if (empty($user_id)) {
            $user_id = $this->_current_user_id;
        }
        if ($this->_cache_on and (is_countable($id_array) ? count($id_array) : 0) > 0) {
            foreach ($id_array as $id) {
                if (!in_array($id, $this->_rubric_id_array)) {
                    $this->_rubric_id_array[] = $id;
                }
            }
            $query = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                   ' WHERE item_id IN ('.implode(',', encode(AS_DB, $id_array)).')'.
                   ' AND   user_id="'.encode(AS_DB, $user_id).'"'.
                   ' GROUP BY item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                include_once 'functions/error_functions.php';
                trigger_error('Problems selecting noticed from query: "'.$query.'"');
            } else {
                $noticed = [];
                foreach ($result as $rs) {
                    $temp = [];
                    $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
                    $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
                    if (!in_array($temp, $this->_noticed_id_array)) {
                        $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                        $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
                    }
                }
            }
        }
    }

    public function getLatestNoticedByIDArrayAndUser($id_array, $user_id)
    {
        if ($this->_cache_on and (is_countable($id_array) ? count($id_array) : 0) > 0) {
            foreach ($id_array as $id) {
                if (!in_array($id, $this->_rubric_id_array)) {
                    $this->_rubric_id_array[] = $id;
                }
            }
            $query = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('noticed').
                   ' WHERE item_id IN ('.implode(',', encode(AS_DB, $id_array)).')'.
                   ' AND   user_id="'.encode(AS_DB, $user_id).'"'.
                   ' GROUP BY item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                include_once 'functions/error_functions.php';
                trigger_error('Problems selecting noticed from query: "'.$query.'"');
            } else {
                $noticed = [];
                foreach ($result as $rs) {
                    $temp = [];
                    $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
                    $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
                    if (!in_array($temp, $this->_noticed_id_array)) {
                        $this->_noticed_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                        $this->_noticed_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
                    }
                }

                return $this->_noticed_id_array;
            }
        }
    }

    /**
     * Marks the item with the given item ID & version ID as noticed by the current user.
     *
     * @param int $itemId    ID of the item to be marked as noticed
     * @param int $versionId ID of the item version to be marked as noticed
     */
    public function markNoticed(int $itemId, int $versionId)
    {
        if (!empty($itemId)) {
            $this->markItemsAsNoticed([$itemId], $versionId); // defaults to current user
        }
    }

    /**
     * Marks an array of items (of the given version ID) as noticed by the given users
     * (or the current user in case no user IDs were given).
     *
     * @param int[]      $itemIds   Array of item IDs for items to be marked as noticed
     * @param int        $versionId ID of the item version (applied to all given items) to be marked as noticed
     * @param int[]|null $userIds   Optional array of user IDs specifying the users for whom the given items shall
     *                              be marked as noticed; defaults to null in which case given items will be marked as noticed for the current user
     */
    public function markItemsAsNoticed(array $itemIds, int $versionId, array $userIds = null)
    {
        if (empty($itemIds)) {
            return;
        }

        if (empty($userIds)) {
            if (empty($this->_current_user_id)) {
                $this->_current_user_id = $this->_environment->getCurrentUserID();
                if (empty($this->_current_user_id)) {
                    return;
                }
            }
            $userIds = [$this->_current_user_id];
        }

        /*
         * There was a problem in reader- and noticed-manager when marking an entry as read, if
         * it was a non-active entry. In this case, the manager tried to execute the same insert
         * statement twice, which caused an error because of the tables primary key.
         * To fix this, the query was changed to "INSERT IGNORE INTO..."
         */
        $query = 'INSERT IGNORE INTO '.$this->addDatabasePrefix('noticed')
            .' (item_id, version_id, user_id, read_date) VALUES ';

        $valueRows = [];
        $currentDateTime = getCurrentDateTimeInMySQL();

        foreach ($itemIds as $itemId) {
            foreach ($userIds as $userId) {
                $valueRow = '("'
                    .encode(AS_DB, $itemId).'", "'
                    .encode(AS_DB, $versionId).'", "'
                    .encode(AS_DB, $userId).'", "'
                    .$currentDateTime
                    .'")';
                $valueRows[] = $valueRow;
            }
        }
        $query .= implode(', ', $valueRows);

        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems marking item(s) as noticed from query: "'.$query.'"');
        }
    }

    public function mergeAccounts($new_id, $old_id)
    {
        $select = 'SELECT * FROM '.$this->addDatabasePrefix('noticed')." WHERE user_id = '".encode(AS_DB, $old_id)."'";

        $result = $this->_db_connector->performQuery($select);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems creating notice from query: "'.$select.'"', E_USER_WARNING);
        }

        foreach ($result as $row) {
            $select2 = 'SELECT * FROM '.$this->addDatabasePrefix('noticed')." WHERE user_id = '".encode(AS_DB, $new_id)."' ";
            $select2 .= ' AND item_id = '.$row['item_id'];
            $select2 .= ' AND version_id = '.$row['version_id'];

            $result2 = $this->_db_connector->performQuery($select2);
            if (!isset($result2)) {
                include_once 'functions/error_functions.php';
                trigger_error('Problems creating notice from query: "'.$select2.'"', E_USER_WARNING);
            } elseif (empty($result2[0])) {
                $row2 = '';
            } else {
                $row2 = $result2[0];
            }

            if (empty($row2)) {
                $update = 'UPDATE '.$this->addDatabasePrefix('noticed').' SET ';
                $update .= ' user_id = '.encode(AS_DB, $new_id);
                $update .= ' WHERE user_id = '.encode(AS_DB, $old_id);
                $update .= ' AND item_id = '.$row['item_id'];
                $update .= ' AND version_id = '.$row['version_id'];

                $result3 = $this->_db_connector->performQuery($update);
                if (!isset($result3) or !$result3) {
                    include_once 'functions/error_functions.php';
                    trigger_error('Problems creating notice from query: "'.$update.'"', E_USER_WARNING);
                }
            } else {
                $update = 'DELETE FROM '.$this->addDatabasePrefix('noticed').' ';
                $update .= ' WHERE user_id = '.encode(AS_DB, $old_id);
                $update .= ' AND item_id = '.$row['item_id'];
                $update .= ' AND version_id = '.$row['version_id'];

                $result3 = $this->_db_connector->performQuery($update);
                if (!isset($result3) or !$result3) {
                    include_once 'functions/error_functions.php';
                    trigger_error('Problems creating notice from query: "'.$update.'"', E_USER_WARNING);
                }
            }
        }
    }

    public function addDatabasePrefix($db_table)
    {
        return $db_table;
    }

     public function deleteFromDb($context_id)
     {
         $id_array_items = [];
         $id_array_users = [];

         $item_manager = $this->_environment->getItemManager();
         $item_manager->setContextLimit($context_id);
         $item_manager->setNoIntervalLimit();
         $item_manager->select();
         $item_list = $item_manager->get();
         $temp_item = $item_list->getFirst();
         while ($temp_item) {
             $id_array_items[] = $temp_item->getItemID();
             $temp_item = $item_list->getNext();
         }
         $user_manager = $this->_environment->getUserManager();
         $user_manager->setContextLimit($context_id);
         $user_manager->select();
         $user_list = $user_manager->get();
         $temp_user = $user_list->getFirst();
         while ($temp_user) {
             $id_array_users[] = $temp_user->getItemID();
             $temp_user = $user_list->getNext();
         }

         if (!empty($id_array_items) and !empty($id_array_users)) {
             $query = 'DELETE FROM noticed WHERE noticed.item_id IN ('.implode(',', $id_array_items).') OR noticed.user_id IN ('.implode(',', $id_array_users).')';
             $this->_db_connector->performQuery($query);
         }
     }
}
