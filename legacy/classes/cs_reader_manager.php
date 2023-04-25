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

/** class for database connection to the database table "reader"
 * this class implements a database manager for the table "reader". Read items.
 */
class cs_reader_manager
{
    /**
     * object cs_user_item - containing the current user.
     */
    public $_current_user;
    public $_current_user_id;
    public $_db_connector;

    public $_rubric_id_array = [];
    public $_reader_id_array = [];
    public $_cache_on = true;

    /** constructor: cs_reader_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment the environment
     */
    public function __construct(private readonly cs_environment $_environment)
    {
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
        $this->_reader_id_array = [];
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
     public function getLatestReader($item_id)
     {
         return $this->getLatestReaderForUserByID($item_id, $this->_current_user_id);
     }

    public function getLatestReaderByUserIDArray($id_array, $item_id)
    {
        if ($this->_cache_on and (is_countable($id_array) ? count($id_array) : 0) > 0) {
            foreach ($id_array as $id) {
                if (!in_array($id, $this->_rubric_id_array)) {
                    $this->_rubric_id_array[] = $id;
                }
            }
            $query = 'SELECT user_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('reader').
                   ' WHERE item_id="'.encode(AS_DB, $item_id).'"'.
                   ' AND   user_id IN ('.implode(',', encode(AS_DB, $id_array)).')'.
                   ' GROUP BY user_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting reader from query: "'.$query.'"');
            } else {
                foreach ($result as $rs) {
                    $temp = [];
                    $temp[$rs['user_id']]['version_id'] = $rs['version_id'];
                    $temp[$rs['user_id']]['read_date'] = $rs['read_date'];
                    if (!in_array($temp, $this->_reader_id_array)) {
                        $this->_reader_id_array[$rs['user_id']]['version_id'] = $rs['version_id'];
                        $this->_reader_id_array[$rs['user_id']]['read_date'] = $rs['read_date'];
                    }
                }
            }
        }
    }

    public function getLatestReaderByIDArray($id_array)
    {
        if ($this->_cache_on and (is_countable($id_array) ? count($id_array) : 0) > 0) {
            foreach ($id_array as $id) {
                if (!in_array($id, $this->_rubric_id_array)) {
                    $this->_rubric_id_array[] = $id;
                }
            }
            $query = 'SELECT item_id, version_id, MAX(read_date) as read_date FROM '.$this->addDatabasePrefix('reader').
                   ' WHERE item_id IN ('.implode(',', encode(AS_DB, $id_array)).')'.
                   ' AND   user_id="'.encode(AS_DB, $this->_current_user_id).'"'.
                   ' GROUP BY item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting reader from query: "'.$query.'"');
            } else {
                foreach ($result as $rs) {
                    $temp = [];
                    $temp[$rs['item_id']]['version_id'] = $rs['version_id'];
                    $temp[$rs['item_id']]['read_date'] = $rs['read_date'];
                    if (!in_array($temp, $this->_reader_id_array)) {
                        $this->_reader_id_array[$rs['item_id']]['version_id'] = $rs['version_id'];
                        $this->_reader_id_array[$rs['item_id']]['read_date'] = $rs['read_date'];
                    }
                }

                return $this->_reader_id_array;
            }
        }
    }

     public function getLatestReaderForUserByID($item_id, $user_id)
     {
         // get latest reader entry from cache (keyed be item ID _or_ user ID), or query database
         $reader = [];
         if (in_array($item_id, $this->_rubric_id_array)) {
             if (array_key_exists($item_id, $this->_reader_id_array)) {
                 $reader = $this->_reader_id_array[$item_id];
             }

             return $reader;
         } else {
             if (in_array($user_id, $this->_rubric_id_array)) {
                 if (array_key_exists($user_id, $this->_reader_id_array)) {
                     $reader = $this->_reader_id_array[$user_id];
                 }

                 return $reader;
             } else {
                 $query = 'SELECT version_id, read_date FROM '.$this->addDatabasePrefix('reader').
                     ' WHERE item_id="'.encode(AS_DB, $item_id).'"'.
                     ' AND   user_id="'.encode(AS_DB, $user_id).'"'.
                     ' ORDER BY read_date DESC';
                 $result = $this->_db_connector->performQuery($query);
                 if (!isset($result)) {
                     trigger_error('Problems selecting reader from query: "'.$query.'"');
                 } else {
                     if (!empty($result[0])) {
                         $reader['version_id'] = $result[0]['version_id'];
                         $reader['read_date'] = $result[0]['read_date'];
                     }
                 }
             }
         }

         return $reader;
     }

    /**
     * Marks the item with the given item ID & version ID as read by the current user.
     *
     * @param int $itemId    ID of the item to be marked as read
     * @param int $versionId ID of the item version to be marked as read
     */
    public function markRead(int $itemId, int $versionId)
    {
        if (!empty($itemId)) {
            $this->markItemsAsRead([$itemId], $versionId); // defaults to current user
        }
    }

    /**
     * Marks an array of items (of the given version ID) as read by the given users
     * (or the current user in case no user IDs were given).
     *
     * @param int[]      $itemIds   Array of item IDs for items to be marked as read
     * @param int        $versionId ID of the item version (applied to all given items) to be marked as read
     * @param int[]|null $userIds   Optional array of user IDs specifying the users for whom the given items shall
     *                              be marked as read; defaults to null in which case given items will be marked as read for the current user
     */
    public function markItemsAsRead(array $itemIds, int $versionId, array $userIds = null)
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
        $query = 'INSERT IGNORE INTO '.$this->addDatabasePrefix('reader')
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

                // fire a ReadStatusPreChangeEvent (which will e.g. trigger invalidation of the read status cache for this item & user)
                global $symfonyContainer;

                /** @var \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher */
                $eventDispatcher = $symfonyContainer->get('event_dispatcher');

                $readStatusPreChangeEvent = new \App\Event\ReadStatusPreChangeEvent($userId, $itemId, \App\Utils\ReaderService::READ_STATUS_SEEN);
                $eventDispatcher->dispatch($readStatusPreChangeEvent, \App\Event\ReadStatusPreChangeEvent::class);
            }
        }
        $query .= implode(', ', $valueRows);

        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems marking item(s) as read from query: "'.$query.'"');
        }
    }

    public function mergeAccounts($new_id, $old_id)
    {
        $select = 'SELECT * FROM '.$this->addDatabasePrefix('reader')." WHERE user_id = '".encode(AS_DB, $old_id)."'";

        $result = $this->_db_connector->performQuery($select);
        if (!isset($result)) {
            trigger_error('Problems creating reader from query: "'.$select.'"', E_USER_WARNING);
        }

        foreach ($result as $row) {
            $select2 = 'SELECT * FROM '.$this->addDatabasePrefix('reader')." WHERE user_id = '".encode(AS_DB, $new_id)."' ";
            $select2 .= ' AND item_id = '.$row['item_id'];
            $select2 .= ' AND version_id = '.$row['version_id'];

            $result2 = $this->_db_connector->performQuery($select2);
            if (!isset($result2)) {
                trigger_error('Problems creating reader from query: "'.$select2.'"', E_USER_WARNING);
            } elseif (!empty($result[0])) {
                $row2 = $result[0];
            } else {
                $row2 = '';
            }

            if (empty($row2)) {
                $update = 'UPDATE '.$this->addDatabasePrefix('reader').' SET ';
                $update .= ' user_id = '.encode(AS_DB, $new_id);
                $update .= ' WHERE user_id = '.encode(AS_DB, $old_id);
                $update .= ' AND item_id = '.$row['item_id'];
                $update .= ' AND version_id = '.$row['version_id'];

                $result3 = $this->_db_connector->performQuery($update);
                if (!isset($result3) or !$result3) {
                    trigger_error('Problems creating reader from query: "'.$update.'"', E_USER_WARNING);
                }
            } else {
                $update = 'DELETE FROM '.$this->addDatabasePrefix('reader').' ';
                $update .= ' WHERE user_id = '.encode(AS_DB, $old_id);
                $update .= ' AND item_id = '.$row['item_id'];
                $update .= ' AND version_id = '.$row['version_id'];

                $result3 = $this->_db_connector->performQuery($update);
                if (!isset($result3) or !$result3) {
                    trigger_error('Problems creating reader from query: "'.$update.'"', E_USER_WARNING);
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
             $query = 'DELETE FROM reader WHERE reader.item_id IN ('.implode(',', $id_array_items).') OR reader.user_id IN ('.implode(',', $id_array_users).')';
             $this->_db_connector->performQuery($query);
         }
     }
}
