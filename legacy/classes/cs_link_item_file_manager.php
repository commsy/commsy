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

/** class for database connection to the database table "link_material_file"
 * this class implements a database manager for the table "link_material_file",
 * in which we store the links between materials and files.
 */
class cs_link_item_file_manager extends cs_link_father_manager
{
    /**
     * @var mixed|null
     */
    private $_limit_file_id = null;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'item_link_file';
    }

    public function setFileIDLimit($value)
    {
        $this->_limit_file_id = $value;
    }

    public function resetLimits()
    {
        parent::resetLimits();
        $this->_limit_file_id = null;
    }

    public function copyDataFromRoomToRoom($old_id, $new_id, $user_id = '', $id_array = '')
    {
        $retour = [];
        $current_date = getCurrentDateTimeInMySQL();

        $file_id_array = [];
        $file_id_array2 = [];
        foreach ($id_array as $key => $value) {
            if (mb_stristr($key, CS_FILE_TYPE)) {
                $real_file_id = str_replace(CS_FILE_TYPE, '', $key);
                $file_id_array[] = $real_file_id;
                $file_id_array2[] = $value;
            }
        }

        if (!empty($file_id_array)) {
            $query = '';
            $query .= 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deleter_id IS NULL AND deletion_date IS NULL';
            $query .= ' AND file_id IN ('.implode(',', encode(AS_DB, $file_id_array)).')';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems getting data "'.$this->_db_table.'" from query: "'.$query.'"', E_USER_WARNING);
            } else {
                $current_data_array = [];

                $sql = 'SELECT item_iid,file_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE 1';
                $sql .= ' AND file_id IN ('.implode(',', encode(AS_DB, $file_id_array2)).')';
                $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
                $sql_result = $this->_db_connector->performQuery($sql);
                if (!isset($sql_result)) {
                    trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
                } else {
                    foreach ($sql_result as $sql_row) {
                        $current_data_array[] = [$sql_row['item_iid'], $sql_row['file_id']];
                    }
                }

                foreach ($result as $query_result) {
                    $do_it = true;
                    if (!empty($query_result['item_iid'])
                         and !empty($query_result['file_id'])
                         and !empty($id_array[$query_result['item_iid']])
                         and !empty($id_array[CS_FILE_TYPE.$query_result['file_id']])
                         and in_array([$id_array[$query_result['item_iid']], $id_array[CS_FILE_TYPE.$query_result['file_id']]], $current_data_array)
                    ) {
                        $do_it = false;
                    }

                    if ($do_it) {
                        $insert_query = '';
                        $insert_query .= 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET';
                        $first = true;
                        foreach ($query_result as $key => $value) {
                            $value = encode(FROM_DB, $value);
                            if ($first) {
                                $first = false;
                                $before = ' ';
                            } else {
                                $before = ',';
                            }
                            if ('deletion_date' == $key
                                 or 'deleter_id' == $key
                            ) {
                                // do nothing
                            } elseif ('item_iid' == $key) {
                                if (isset($id_array[$value])) {
                                    $insert_query .= $before.$key.'="'.encode(AS_DB, $id_array[$value]).'"';
                                } else {
                                    $do_it = false;
                                }
                            } elseif ('file_id' == $key) {
                                if (isset($id_array[CS_FILE_TYPE.$value])) {
                                    $insert_query .= $before.$key.'="'.encode(AS_DB, $id_array[CS_FILE_TYPE.$value]).'"';
                                } else {
                                    $do_it = false;
                                }
                            }

                            // default
                            else {
                                $insert_query .= $before.$key.'="'.encode(AS_DB, $value).'"';
                            }
                        }
                    }
                    if (!$do_it) {
                        $do_it = true;
                    } else {
                        $result_insert = $this->_db_connector->performQuery($insert_query);
                        if (!isset($result_insert)) {
                            trigger_error('Problem creating item from query: "'.$insert_query.'"', E_USER_ERROR);
                        }
                    }
                }
            }
        }

        return $retour;
    }

    public function _performQuery($mode = 'select')
    {
        $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);

        // restrict context_limit
        if (isset($this->_context_limit)) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('files').' AS f ON f.files_id='.$this->addDatabasePrefix($this->_db_table).'.file_id';
        }

        $query .= ' WHERE 1';

        if (isset($this->_context_limit)) {
            $query .= ' AND f.context_id = "'.encode(AS_DB, $this->_context_limit).'"';
        }
        if (isset($this->_limit_file_id)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.file_id = "'.encode(AS_DB, $this->_limit_file_id).'"';
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' from query: "'.$query.'"', E_USER_WARNING);
        } else {
            return $result;
        }
    }

     /** delete link , but it is just an update
      * this method deletes all links from an item, but only as an update to restore it later and for evaluation.
      *
      * @param int item_id       id of the item
      * @param int version_id    version id of the item
      */
     public function deleteByItem($item_id, $version_id = null)
     {
         $deleterId = $this->_current_user->getItemID() ?: 0;
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
             'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
             'deleter_id="'.encode(AS_DB, $deleterId).'"'.
             ' WHERE item_iid="'.encode(AS_DB, $item_id).'"';
         if ($version_id) {
             $query .= ' AND item_vid="'.encode(AS_DB, $version_id).'"';
         }
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"', E_USER_WARNING);
         }
     }

  /** delete link , but it is just an update
   * this method deletes all links from an item, but only as an update to restore it later and for evaluation.
   *
   * @param int file_id       id of the file item
   */
  public function deleteByFileID($file_id)
  {
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
               'deleter_id="'.encode(AS_DB, $this->_current_user->getItemID()).'"'.
               ' WHERE file_id="'.encode(AS_DB, $file_id).'";';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or !$result) {
          trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'". - '.__FILE__.' - '.__LINE__, E_USER_WARNING);
      }
  }

    public function deleteByFileReally($file_id)
    {
        $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).
                 ' WHERE file_id="'.encode(AS_DB, $file_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems deleting links of a file item from query: "'.$query.'"', E_USER_WARNING);
        }
    }

    public function _updateFromBackup($data_array)
    {
        return $this->_updateFromBackup2($data_array);
    }

    /** build an item out of an (database) array - internal method, do not use
     * this method returns a item out of a row form the database.
     *
     * @param array item_array array with information about the item out of the respective database table
     *
     * @return object cs_item an item
     */
    public function _buildItem($db_array)
    {
        $item = $this->getNewItem();
        $item->_setItemData(encode(FROM_DB, $db_array));

        return $item;
    }

    /** build a new material item
     * this method returns a new EMTPY material item.
     *
     * @return object cs_item a new EMPTY material
     *
     * @author CommSy Development Group
     */
    public function getNewItem()
    {
        return new cs_link_item_file($this->_environment);
    }

     public function deleteFromDb($context_id)
     {
         $id_array = [];

         $item_manager = $this->_environment->getItemManager();
         $item_manager->setContextLimit($context_id);
         $item_manager->setNoIntervalLimit();
         $item_manager->select();
         $item_list = $item_manager->get();
         $temp_item = $item_list->getFirst();
         while ($temp_item) {
             $id_array[] = $temp_item->getItemID();
             $temp_item = $item_list->getNext();
         }

         if (!empty($id_array)) {
             $query = 'DELETE FROM '.$this->_db_table.' WHERE '.$this->_db_table.'.item_iid IN ('.implode(',', $id_array).')';
             $this->_db_connector->performQuery($query);
         }
     }

    // used for ex- and import
    public function insertDirectly($item_id, $version_id, $file_id)
    {
        $query = 'INSERT INTO '.$this->_db_table.' (item_iid, item_vid, file_id) VALUES ('.$item_id.', '.$version_id.', '.$file_id.')';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems while inserting directly: '.$query, E_USER_WARNING);
        }
    }
}
