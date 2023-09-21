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

/* upper class for manager of commsy
 * this class implements an upper class of database manager in commsy
 *
 * @author CommSy Development Group
 */

use App\Lock\LockManager;
use Doctrine\DBAL\Schema\Column;

class cs_manager
{
    /**
     * integer - containing the item id, if an item was created.
     */
    public $_create_id;

    /**
     * integer - containing the version id of the item.
     */
    public $_version_id;

    /**
     * object cs_user_item - containing the current user.
     */
    protected $_current_user;

    /**
     * integer - containing the room id as a limit for select statements.
     */
    public $_room_limit;
    public $_room_array_limit;

    /**
     * String - containing the attribute as a limit for select statements.
     */
    public $_attribute_limit;

    /**
     * boolean - true: then all deleted items where not observed in select statements, false: then all deleted items where observed in select statements.
     */
    public $_delete_limit = true;

    /**
     * object cs_list - contains stored commsy items.
     */
    public $_data;

    /**
     * id_array for item_ids.
     */
    public $_id_array;

    /**
     * integer - containing the item id of the ref item as a limit.
     */
    public $_ref_id_limit;

    /**
     * integer - containing the item id of the user as a limit.
     */
    public $_ref_user_limit;

    /**
     * integer - max number of days since creation of item.
     */
    public $_existence_limit;
    public $_age_limit;

    /**
     * @var \DateTime
     */
    protected $creationNewerThenLimit;

    /**
     * @var \DateTime
     */
    protected $modificationOlderThenLimit;

    /**
     * @var \DateTime
     */
    protected $modificationNewerThenLimit;

    /**
     * @var int[]
     */
    protected $excludedIdsLimit = [];

    protected $inactiveEntriesLimit = self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED;

    public $_update_with_changing_modification_information = true;

    public $_db_table;
    public $_tag_limit;
    public $_buzzword_limit;
    public $_user_limit;

    /**
     * Stores last query if method assigns string.
     */
    public $_last_query = '';

    public $_key_array;
    protected db_mysql_connector $_db_connector;

    public $_cached_items = [];
    public $_cache_object = [];
    public $_cache_on = true;
    public $_cached_sql = [];

    protected $_id_array_limit;

    public $_link_modifier = true;
    public $_db_prefix = '';
    public $_with_db_prefix = true;

    public $_force_sql = false;

    public const SHOW_ENTRIES_ONLY_ACTIVATED = 'only.activated';
    public const SHOW_ENTRIES_ONLY_DEACTIVATED = 'only.deactivated';
    public const SHOW_ENTRIES_ACTIVATED_DEACTIVATED = 'either';

    /** constructor: cs_manager
     * the only available constructor, initial values for internal variables. sets room limit to room.
     *
     * @param object cs_environment the environment
     */
    public function __construct(/**
     * Environment - the environment of the CommSy.
     */
    protected cs_environment $_environment)
    {
        $this->reset();
        $this->_room_limit = $this->_environment->getCurrentContextID();
        $this->_attribute_limit = null;
        $this->_current_user = $this->_environment->getCurrentUser();
        $this->_db_connector = $this->_environment->getDBConnector();
    }

   /** set context id
    * this method sets the context id.
    *
    * @param int id of the context
    */
   public function setCurrentContextID($id)
   {
       $this->_current_context = $id;
   }

   public function setCacheOff()
   {
       $this->_cache_on = false;
   }

   public function forceSQL()
   {
       $this->_force_sql = true;
   }

   public function setSaveWithoutLinkModifier()
   {
       $this->_link_modifier = false;
   }

  /** reset class
   * reset limits and data of this class.
   */
  public function reset()
  {
      $this->resetLimits();
      $this->resetData();
  }

  /** reset limits
   * reset limits of this class: room limit, delete limit.
   */
  public function resetLimits()
  {
      $this->_attribute_limit = null;
      $this->_room_limit = $this->_environment->getCurrentContextID();
      $this->_ref_id_limit = null;
      $this->_ref_user_limit = null;
      $this->_existence_limit = null;
      $this->_age_limit = null;
      $this->_tag_limit = null;
      $this->_buzzword_limit = null;
      $this->_delete_limit = true;
      $this->_update_with_changing_modification_information = true;
      $this->_room_array_limit = null;
      $this->inactiveEntriesLimit = self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED;
      $this->_id_array_limit = null;
      $this->modificationOlderThenLimit = null;
      $this->modificationNewerThenLimit = null;
      $this->creationNewerThenLimit = null;
      $this->excludedIdsLimit = [];
  }

  /** reset data
   * reset data of this class: reset list of items.
   */
  public function resetData()
  {
      $this->_data = null;
      $this->_id_array = null;
  }

   /** set limit to array of announcement item_ids.
    *
    * @param array array of ids to be loaded from db
    */
   public function setIDArrayLimit($id_array)
   {
       if (is_array($id_array)) {
           // remove NULL, FALSE and Empty Strings (""), but leave values of 0 (zero)
           $id_array = array_filter($id_array, 'strlen');
       }
       $this->_id_array_limit = (array) $id_array;
   }

    /**
     * Set a limit to show only activate, inactive or both items.
     *
     * @return $this
     */
    public function setInactiveEntriesLimit(string $limit): self
    {
        if (
            self::SHOW_ENTRIES_ONLY_ACTIVATED !== $limit &&
            self::SHOW_ENTRIES_ONLY_DEACTIVATED !== $limit &&
            self::SHOW_ENTRIES_ACTIVATED_DEACTIVATED !== $limit
        ) {
            throw new InvalidArgumentException('unknown limit given');
        }

        $this->inactiveEntriesLimit = $limit;

        return $this;
    }

   public function setBuzzwordLimit($limit)
   {
       $this->_buzzword_limit = (int) $limit;
   }

   public function setTagLimit($limit)
   {
       $this->_tag_limit = (array) $limit;
   }

   public function setTagArrayLimit($limit)
   {
       $this->_tag_limit = $limit;
   }

   public function _getTagIDArrayByTagIDArray($array)
   {
       $id_array = [];
       $first_element = [];
       $tag2tag_manager = $this->_environment->getTag2TagManager();
       foreach ($array as $key => $value) {
           $output = preg_replace('/[^0-9]/', '', $value);
           if (!empty($output)) {
               $first_element[] = $value;
           } else {
               $first_element[] = substr($key, 7);
               if ($array[$key]) {
                   $id_array = array_unique(array_merge($id_array, $tag2tag_manager->getRecursiveChildrenItemIDArray(substr($key, 7))));
               }
           }
       }
       $id_array = array_merge($id_array, $first_element);
       return $id_array;
   }

   public function _getTagIDArrayByTagID($id)
   {
       $id_array = [$id];
       $tag2tag_manager = $this->_environment->getTag2TagManager();
       $id_array = array_merge($id_array, $tag2tag_manager->getRecursiveChildrenItemIDArray($id));

       return $id_array;
   }

   public function setUserLimit($limit)
   {
       $this->_user_limit = (int) $limit;
   }

   /** set context limit
    * this method sets a context limit.
    *
    * @param int limit id of the context
    */
   public function setContextLimit($limit)
   {
       $this->_room_limit = (int) $limit;
   }

   public function unsetContextLimit()
   {
       $this->_room_limit = null;
   }

   public function setContextArrayLimit($limit)
   {
       $this->_room_array_limit = $limit;
   }

  /** set delete limit
   * this method sets the delete limit: true, all deleted items will be not observed - false, all items will be observed.
   *
   * @param bool limit with delete limit ?
   */
  public function setDeleteLimit($limit)
  {
      $this->_delete_limit = (bool) $limit;
  }

   /** set limit
    * this method sets a group limit for material.
    *
    * @param int limit id of the group
    *
    * @author CommSy Development Group
    */
   public function setRefIDLimit($limit)
   {
       $this->_ref_id_limit = (int) $limit;
   }

   public function setRefUserLimit($limit)
   {
       $this->_ref_user_limit = (int) $limit;
   }

   /** set existence limit
    * The existence limit sets the max number of days that passed
    * since the creation of this item.
    *
    * @param int max number of days
    */
   public function setExistenceLimit($limit)
   {
       $this->_existence_limit = (int) $limit;
   }

   public function setAgeLimit($limit)
   {
       $this->_age_limit = (int) $limit;
   }

   public function setModificationNewerThenLimit(DateTime $newerThen)
   {
       $this->modificationNewerThenLimit = $newerThen;
   }

   public function setCreationNewerThenLimit(DateTime $newerThen)
   {
       $this->creationNewerThenLimit = $newerThen;
   }

   public function setExcludedIdsLimit($ids)
   {
       $this->excludedIdsLimit = $ids;
   }

  public function saveWithoutChangingModificationInformation()
  {
      $this->_update_with_changing_modification_information = false;
  }

  /** get error number
   * this method returns the number of an error, if an error occured.
   *
   * @return int error number
   */
  public function getErrorNumber()
  {
      return $this->_db_connector->getErrno();
  }

  /** get error text
   * this method returns the text of an error, if an error occured.
   *
   * @return string error number
   */
  public function getErrorMessage()
  {
      return $this->_db_connector->getError();
  }

  /** get item id of created item
   * this method returns the item id of the item just created.
   *
   * @return int item id
   */
  public function getCreateID()
  {
      return $this->_create_id;
  }

  /** get version id of created item
   * this method returns the version id of the item just created.
   *
   * @return int version id
   */
  public function getVersionID()
  {
      return $this->_version_id;
  }

    /** get the data of the manager
     * this method returns a list of commsy items.
     *
     * @return cs_list|null list of commsy items
     */
    public function get(): ?cs_list
    {
        return $this->_data;
    }

    /** get one item (newest version)
     * this method returns an item in his newest version - this method needs to be overwritten.
     *
     * @param int $item_id id of the commsy item
     *
     * @return object cs_item one commsy items
     */
    public function getItem(?int $item_id)
    {
        throw new LogicException('cs_manager (getItem): needs to be overwritten !!!');
    }

  /** get a list of items (newest version)
   * this method returns a list of items.
   *
   * @param array id_array ids of the items items
   *
   * @return cs_list list of cs_items
   *
   * @author CommSy Development Group
   */
  public function getItemList(array $id_array)
  {
      echo static::class.': cs_manager->getItemList needs to be overwritten !!!<br />'."\n";
  }

   public function _existsField($table, $field)
   {
       $retour = false;
       $sql = 'SHOW COLUMNS FROM '.$this->addDatabasePrefix($table);
       if (empty($this->_cached_sql[$sql])) {
           $db = $this->_environment->getDBConnector();
           $result = $db->performQuery($sql);
           if ($this->_cache_on) {
               $this->_cached_sql[$sql] = $result;
           }
       } else {
           $result = $this->_cached_sql[$sql];
       }
       foreach ($result as $field_array) {
           if (!empty($field_array)
                and !empty($field_array['Field'])
                and $field_array['Field'] == $field
           ) {
               $retour = true;
               break;
           }
       }

       return $retour;
   }

   /** get a list of items
    * this method returns a list of items.
    *
    * @param type name of the db-table to query
    * @param array id_array ids of the items items
    *
    * @return cs_list list of cs_items
    */
   public function _getItemList($type, $id_array)
   {
       /** cs_list is needed for storage the commsy items.
        */
       if (empty($id_array)) {
           return new cs_list();
       } else {
           if ('discussion' == $type) {
               $type = 'discussions';
           } elseif ('todo' == $type) {
               $type = 'todos';
           }
           $query = 'SELECT * FROM '.encode(AS_DB, $this->addDatabasePrefix($type)).' WHERE '.encode(AS_DB, $this->addDatabasePrefix($type)).".item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result)) {
               trigger_error('Problems selecting list of '.$type.' items.', E_USER_WARNING);
           } else {
               $list = new cs_list();
               foreach ($result as $rs) {
                   // special for todo
                   if ('todos' == $type and isset($rs['date'])) {
                       $rs['end_date'] = $rs['date'];
                       unset($rs['date']);
                   }
                   $list->add($this->_buildItem($rs));
               }
               unset($result);
           }
           unset($query);

           return $list;
       }
   }

  /** save a commsy item
   * this method saves a commsy item.
   *
   * @param cs_item
   */
  public function saveItem($item)
  {
      $item_id = $item->getItemID();

      $modifier = $item->getModificatorItem();
      if (!isset($modifier)) {
          $user = $this->_environment->getCurrentUser();
          $item->setModificatorItem($user);
      } else {
          $modifier_id = $modifier->getItemID();
          if (empty($modifier_id)) {
              $user = $this->_environment->getCurrentUser();
              $item->setModificatorItem($user);
          }
      }

      if (!empty($item_id)) {
          $this->_update($item);
      } else {
          $creator_id = $item->getCreatorID();
          if (empty($creator_id)) {
              $user = $this->_environment->getCurrentUser();
              $item->setCreatorItem($user);
          }
          $this->_create($item);
      }

      // Add modifier to all users who ever edited this section
      if ($this->_update_with_changing_modification_information) {
          $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
          $link_modifier_item_manager->markEdited($item->getItemID());
      }
  }

   /** update an item, with new informations, e.g. creator and modificator
    * this method updates an item initially.
    *
    * @param object cs_item
    */
   public function saveItemNew($item)
   {
       // needs to be overwritten
   }

    /**
     * update modification date of item in items table
     * this method updates the database record for a given item.
     *
     * @param cs_item $item
     */
    public function _update($item)
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $queryBuilder
            ->update($this->addDatabasePrefix('items'))
            ->set('context_id', ':contextId')
            ->set('activation_date', ':activationDate')
            ->where('item_id = :itemId')
            ->setParameter('contextId', $item->getContextID())
            ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
            ->setParameter('itemId', $item->getItemID());

        if ($item->isChangeModificationOnSave() || $this->_update_with_changing_modification_information) {
            $queryBuilder
                ->set('modification_date', ':modificationDate')
                ->setParameter('modificationDate', getCurrentDateTimeInMySQL());
        }

        if ('cs_item' == $item::class) {
            $queryBuilder
                ->set('draft', ':draft')
                ->setParameter('draft', $item->isDraft());
        }

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }

    /** delete a commsy item
     * this method deletes a commsy item.
     *
     * @param int $itemId id of the commsy item
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(int $itemId): void
    {
        $currentDatetime = getCurrentDateTimeInMySQL();
        $currentUser = $this->_environment->getCurrentUserItem();
        $deleterId = (0 !== $currentUser->getItemID()) ? $currentUser->getItemID() : 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('items').' SET '.
            'deletion_date="'.$currentDatetime.'",'.
            'deleter_id="'.encode(AS_DB, $deleterId).'"'.
            ' WHERE item_id="'.$itemId.'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) || !$result) {
            trigger_error('Problems deleting item in table items.', E_USER_WARNING);
        }
    }

  public function undeleteItemByItemID($item_id)
  {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
               ' deletion_date=NULL,'.
               ' deleter_id=NULL,'.
               ' modification_date="'.$current_datetime.'",'.
               ' modifier_id="'.encode(AS_DB, $user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB, $item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or !$result) {
          trigger_error('Problems undeleting '.$this->_db_table.'.', E_USER_WARNING);
      } else {
          unset($result);
          $this->undelete($item_id);
      }
      unset($query);
  }

   public function undelete($item_id)
   {
       $current_datetime = getCurrentDateTimeInMySQL();
       $query = 'UPDATE '.$this->addDatabasePrefix('items').' SET '.
                'modification_date="'.$current_datetime.'",'.
                'deletion_date=NULL,'.
                'deleter_id=NULL'.
                ' WHERE item_id="'.encode(AS_DB, $item_id).'"';
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problems undeleting item in table items.', E_USER_WARNING);
       } else {
           unset($result);
       }
       unset($query);
   }

    /** build an item out of an (database) array - internal method, do not use
     * this method returns a item out of a row form the database.
     *
     * @param array item_array array with information about the item out of the respective database table
     *
     * @return object cs_item an item
     */
    protected function _buildItem(array $db_array)
    {
        /** @var cs_item $item */
        $item = $this->getNewItem();
        if (isset($item)) {
            $item->_setItemData(encode(FROM_DB, $db_array));
            if (method_exists($item, 'getItemID')) {
                $item_id = $item->getItemID();
                if (!empty($item_id)) {
                    // cache
                    if (empty($this->_cache_object[$item_id])) {
                        $this->_cache_object[$item_id] = $item;
                    }
                }

                if ($this->_cache_on) {
                    if (!empty($item_id) && empty($this->_cache_object[$item_id])) {
                        $this->_cache_object[$item_id] = $item;
                    }
                }
            }
        }

        return $item;
    }

    /**
     * select items limited by limits
     * this method returns a list (cs_list) of items within the database limited by the limits.
     * depends on _performQuery(), which must be overwritten.
     */
    public function select()
    {
        $result = $this->_performQuery();
        $this->_id_array = null;
        $data = new cs_list();

        $result = is_array($result) ? $result : [];

        foreach ($result as $query_result) {
            $item = $this->_buildItem($query_result);
            $data->add($item);
        }

        $this->_data = $data;
    }

   /**
    * count all items limited by the limits
    * this method returns the number of selected items limited by the limits.
    * if no items are loaded, the count is performed by the database
    * depends on _performQuery(), which must be overwritten.
    */
   public function getCountAll(): int
   {
       $result = 0;
       if (empty($this->_id_array)) {
           $rs = $this->_performQuery('count');
           if (is_array($rs)
                and isset($rs[0]['count'])
           ) {
               $result = $rs[0]['count'];
           }
       } else {
           $result = is_countable($this->_id_array) ? count($this->_id_array) : 0;
       }

       return $result;
   }

   /** get all ids of the selected items as an array
    * this method returns all ids of the selected items limited by the limits as an array.
    * if no items are loaded, the ids are loaded from the database
    * depends on _performQuery(), which must be overwritten.
    *
    * @return array $this->_id_array id array of selected materials
    */
   public function getIDArray()
   {
       // ------------------
       // --->UTF8 - OK<----
       // ------------------
       if (empty($this->_id_array)) {
           $result = $this->_performQuery('id_array');
           if (is_array($result)) {
               foreach ($result as $row) {
                   $this->_id_array[] = $row['item_id'];
               }
           }
       }

       return $this->_id_array;
   }

   public function getIDs()
   {
       return $this->getIDArray();
   }

   /** perform database query : select and count
    * abstract method for performing database queries; must be overwritten.
    *
    * @param string mode    one of select, count or id_array
    *
    * @return resource result from database
    */
   public function _performQuery($mode = 'select')
   {
       trigger_error('must be overwritten!', E_USER_ERROR);
   }

    public function mergeAccounts($new_id, $old_id)
    {
        // creator id
        if (!in_array($this->_db_table, ['links', 'items', 'portal'])) {
            $query1 = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET creator_id = "'.encode(AS_DB,
                $new_id).'" WHERE creator_id = "'.encode(AS_DB, $old_id).'";';
            $result = $this->_db_connector->performQuery($query1);
            if (!isset($result) or !$result) {
                trigger_error('Problems merging accounts "'.$this->_db_table.'".', E_USER_WARNING);
            }
        }

        // modifier id
        if (!in_array($this->_db_table, ['files', 'link_items', 'links', 'tasks', 'items', 'portal'])) {
            $query2 = ' UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET modifier_id = "'.encode(AS_DB,
                $new_id).'" WHERE modifier_id = "'.encode(AS_DB, $old_id).'";';
            $result = $this->_db_connector->performQuery($query2);
            if (!isset($result) or !$result) {
                trigger_error('Problems merging accounts "'.$this->_db_table.'".', E_USER_WARNING);
            }
        }

        // deleter id
        $query3 = ' UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id = "'.encode(AS_DB,
            $new_id).'" WHERE deleter_id = "'.encode(AS_DB, $old_id).'";';
        $result = $this->_db_connector->performQuery($query3);
        if (!isset($result) or !$result) {
            trigger_error('Problems merging accounts "'.$this->_db_table.'": "'.$this->_dberror.'" from query: "'.$query3.'"',
                E_USER_WARNING);
        }
    }

   public function copyDataFromRoomToRoom($old_id, $new_id, $user_id = '', $id_array = '')
   {
       $retour = [];
       $current_date = getCurrentDateTimeInMySQL();

       $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.
           encode(AS_DB, $old_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';

       // special for links
       // should be deleted when data clean
       if (CS_LINK_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' AND to_item_id != "-2"';
       }

       // not group all, is allready in the new room
       if (CS_LABEL_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' AND name != "ALL"';
       }

       // not root tag, is allready in the new room
       if (CS_TAG_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' AND title != "CS_TAG_ROOT"';
       }

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
       } else {
           $current_data_array = [];
           $current_copy_date_array = [];
           $current_mod_date_array = [];
           if (CS_LABEL_TYPE == DBTable2Type($this->_db_table)
                or CS_TAG_TYPE == DBTable2Type($this->_db_table)
           ) {
               $title_field = 'title';
               $type_field = '';
               if (CS_LABEL_TYPE == DBTable2Type($this->_db_table)) {
                   $title_field = 'name';
                   $type_field = 'type';
               }
               $type_sql_statement = '';
               if (!empty($type_field)) {
                   $type_sql_statement = ', '.$type_field;
               }
               $sql = 'SELECT item_id,'.$title_field.$type_sql_statement.' FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL;';
               $sql_result = $this->_db_connector->performQuery($sql);
               if (!isset($sql_result)) {
                   trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   foreach ($sql_result as $sql_row) {
                       if (!empty($sql_row[$title_field])) {
                           if (empty($type_field)) {
                               $current_data_array[$sql_row[$title_field]] = $sql_row['item_id'];
                           } else {
                               $current_data_array[$sql_row[$type_field]][$sql_row[$title_field]] = $sql_row['item_id'];
                           }
                       }
                   }
               }
           } elseif (CS_TAG2TAG_TYPE == DBTable2Type($this->_db_table)) {
               $sql = 'SELECT to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL;';
               $sql_result = $this->_db_connector->performQuery($sql);
               if (!isset($sql_result)) {
                   trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   foreach ($sql_result as $sql_row) {
                       $current_data_array[] = $sql_row['to_item_id'];
                   }
               }
           } elseif (CS_MATERIAL_TYPE == DBTable2Type($this->_db_table)
                      or CS_SECTION_TYPE == DBTable2Type($this->_db_table)
                      or CS_ANNOUNCEMENT_TYPE == DBTable2Type($this->_db_table)
                      or CS_DATE_TYPE == DBTable2Type($this->_db_table)
                      or CS_DISCUSSION_TYPE == DBTable2Type($this->_db_table)
                      or CS_TODO_TYPE == DBTable2Type($this->_db_table)
                      or CS_ANNOTATION_TYPE == DBTable2Type($this->_db_table)
                      or CS_DISCARTICLE_TYPE == DBTable2Type($this->_db_table)
                      or CS_STEP_TYPE == DBTable2Type($this->_db_table)
           ) {
               $item_id = 'item_id';
               $modification_date = 'modification_date';
               $sql = 'SELECT '.$item_id.','.$modification_date.',extras FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $new_id).'"';
               $sql .= ' AND extras LIKE "%s:4:\"COPY\";a:2:{s:7:\"ITEM_ID\";%"';
               $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
               $sql_result = $this->_db_connector->performQuery($sql);
               if (!isset($sql_result)) {
                   trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   foreach ($sql_result as $sql_row) {
                       $extra_array = mb_unserialize($sql_row['extras']);
                       $current_data_array[$extra_array['COPY']['ITEM_ID']] = $sql_row[$item_id];
                   }
               }
           } elseif (CS_LINK_TYPE == DBTable2Type($this->_db_table)) {
               $sql = 'SELECT from_item_id,to_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $new_id).'"';
               $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
               $sql_result = $this->_db_connector->performQuery($sql);
               if (!isset($sql_result)) {
                   trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   foreach ($sql_result as $sql_row) {
                       $current_data_array[] = [$sql_row['from_item_id'], $sql_row['to_item_id']];
                   }
               }
           } elseif (CS_LINKITEM_TYPE == DBTable2Type($this->_db_table)) {
               $sql = 'SELECT first_item_id,second_item_id FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $new_id).'"';
               $sql .= ' AND deleter_id IS NULL AND deletion_date IS NULL;';
               $sql_result = $this->_db_connector->performQuery($sql);
               if (!isset($sql_result)) {
                   trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   foreach ($sql_result as $sql_row) {
                       $current_data_array[] = [$sql_row['first_item_id'], $sql_row['second_item_id']];
                   }
               }
           }
           foreach ($result as $query_result) {
               $do_it = true;

               if (CS_LABEL_TYPE == DBTable2Type($this->_db_table)
                    and !empty($current_data_array)
                    and !empty($current_data_array[$query_result[$type_field]])
                    and is_array($current_data_array[$query_result[$type_field]])
                    and array_key_exists($query_result[$title_field], $current_data_array[$query_result[$type_field]])
               ) {
                   $retour[$query_result['item_id']] = $current_data_array[$query_result[$type_field]][$query_result[$title_field]];
                   $do_it = false;
               } elseif (CS_TAG_TYPE == DBTable2Type($this->_db_table)
                          and array_key_exists($query_result[$title_field], $current_data_array)
               ) {
                   $retour[$query_result['item_id']] = $current_data_array[$query_result[$title_field]];
                   $do_it = false;
               } elseif (CS_TAG2TAG_TYPE == DBTable2Type($this->_db_table)
                          and in_array($id_array[$query_result['to_item_id']], $current_data_array)
               ) {
                   $do_it = false;
               } elseif ((CS_MATERIAL_TYPE == DBTable2Type($this->_db_table)
                            or CS_SECTION_TYPE == DBTable2Type($this->_db_table)
                            or CS_ANNOUNCEMENT_TYPE == DBTable2Type($this->_db_table)
                            or CS_DATE_TYPE == DBTable2Type($this->_db_table)
                            or CS_DISCUSSION_TYPE == DBTable2Type($this->_db_table)
                            or CS_TODO_TYPE == DBTable2Type($this->_db_table)
                            or CS_ANNOTATION_TYPE == DBTable2Type($this->_db_table)
                            or CS_DISCARTICLE_TYPE == DBTable2Type($this->_db_table)
                            or CS_STEP_TYPE == DBTable2Type($this->_db_table)
               )
               and array_key_exists($query_result['item_id'], $current_data_array)) {
                   $retour[$query_result['item_id']] = $current_data_array[$query_result['item_id']];
                   $do_it = false;
               } elseif (CS_LINK_TYPE == DBTable2Type($this->_db_table)
                          and !empty($id_array[$query_result['from_item_id']])
                          and !empty($id_array[$query_result['to_item_id']])
                          and in_array([$id_array[$query_result['from_item_id']], $id_array[$query_result['to_item_id']]], $current_data_array)
               ) {
                   $do_it = false;
               } elseif (CS_LINKITEM_TYPE == DBTable2Type($this->_db_table)
                          and !empty($id_array[$query_result['first_item_id']])
                          and !empty($id_array[$query_result['second_item_id']])
                          and (in_array([$id_array[$query_result['first_item_id']], $id_array[$query_result['second_item_id']]], $current_data_array)
                                or in_array([$id_array[$query_result['second_item_id']], $id_array[$query_result['first_item_id']]], $current_data_array)
                          )
               ) {
                   $do_it = false;
               }

               // Skip draft items
               if ($do_it && isset($query_result['item_id'])) {
                   $itemManager = $this->_environment->getItemManager();
                   $correspondingItem = $itemManager->getItem($query_result['item_id']);
                   $do_it = !$correspondingItem->isDraft();
               }

               if ($do_it
                    and CS_LINKITEMFILE_TYPE != DBTable2Type($this->_db_table)
                    and CS_LINK_TYPE != DBTable2Type($this->_db_table)
                    and CS_TAG2TAG_TYPE != DBTable2Type($this->_db_table)
                    and isset($query_result['item_id'])
                    and !isset($retour[$query_result['item_id']])
               ) {
                   $new_item_id = $this->_createItemInItemTable($new_id, DBTable2Type($this->_db_table), $current_date);
               }
               if ($do_it) {
                   $insert_query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET';
                   $first = true;
                   $old_item_id = '';
                   foreach ($query_result as $key => $value) {
                       $value = encode(FROM_DB, $value);
                       if ($first) {
                           $first = false;
                           $before = ' ';
                       } else {
                           $before = ',';
                       }
                       if ('item_id' == $key) {
                           $old_item_id = $value;
                           if (!empty($retour[$value])) {
                               $insert_query .= $before.$key.'="'.$retour[$value].'"';
                           } elseif (!empty($new_item_id)) {
                               $insert_query .= $before.$key.'="'.encode(AS_DB, $new_item_id).'"';
                           } else {
                               $do_it = false;
                           }
                       } elseif ('context_id' == $key) {
                           $insert_query .= $before.$key.'="'.encode(AS_DB, $new_id).'"';
                       } elseif ('modification_date' == $key
                                  or 'creation_date' == $key
                       ) {
                           $insert_query .= $before.$key.'="'.$current_date.'"';
                       } elseif (!empty($user_id)
                                  and ('creator_id' == $key
                                        or 'modifier_id' == $key)
                       ) {
                           $insert_query .= $before.$key.'="'.encode(AS_DB, $user_id).'"';
                       } elseif ('deletion_date' == $key
                                  or 'deleter_id' == $key
                       ) {
                           // do nothing
                       }

                       // special for ANNOTATION
                       elseif ('linked_item_id' == $key
                                and CS_ANNOTATION_TYPE == DBTable2Type($this->_db_table)
                                and isset($id_array[$value])
                       ) {
                           $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                       }

                       // special for DISCUSSIONARTICLE
                       elseif ('discussion_id' == $key
                                and CS_DISCARTICLE_TYPE == DBTable2Type($this->_db_table)
                                and isset($id_array[$value])
                       ) {
                           $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                       }

                       // special for SECTION
                       elseif ('material_item_id' == $key
                                and CS_SECTION_TYPE == DBTable2Type($this->_db_table)
                                and isset($id_array[$value])
                       ) {
                           $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                       }

                       // special for STEP
                       elseif ('todo_item_id' == $key
                                and CS_STEP_TYPE == DBTable2Type($this->_db_table)
                                and isset($id_array[$value])
                       ) {
                           $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                       }

                       // special for LINKS / TAG2TAG
                       elseif (('from_item_id' == $key
                                  or 'to_item_id' == $key
                       ) and (CS_LINK_TYPE == DBTable2Type($this->_db_table)
                               or CS_TAG2TAG_TYPE == DBTable2Type($this->_db_table)
                       )
                       ) {
                           if (isset($id_array[$value])) {
                               $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                           } else {
                               $do_it = false;
                           }
                       }

                       // special for TAG2TAG
                       elseif ('link_id' == $key
                                and CS_TAG2TAG_TYPE == DBTable2Type($this->_db_table)
                       ) {
                           // link_id is primary key so don't insert it
                       }

                       // special for LINK_ITEM
                       elseif (('first_item_id' == $key or 'second_item_id' == $key)
                                  and CS_LINKITEM_TYPE == DBTable2Type($this->_db_table)
                       ) {
                           if (isset($id_array[$value])) {
                               $insert_query .= $before.$key.'="'.$id_array[$value].'"';
                           } else {
                               $do_it = false;
                           }
                       }

                       // special for MATERIAL
                       elseif ('copy_of' == $key
                                and empty($value)
                                and CS_MATERIAL_TYPE == DBTable2Type($this->_db_table)
                       ) {
                           $insert_query .= $before.$key.'=NULL';
                       }

                       // special for labels
                       elseif ('name' == $key
                                and empty($value)
                                and CS_LABEL_TYPE == DBTable2Type($this->_db_table)
                       ) {
                           $insert_query .= $before.$key.'=" "';
                       }

                       // extra
                       elseif ('extras' == $key
                                and !empty($old_item_id)
                       ) {
                           $extra_array = mb_unserialize($value);
                           $extra_array['COPY']['ITEM_ID'] = $old_item_id;
                           $extra_array['COPY']['COPYING_DATE'] = $current_date;
                           $value = serialize($extra_array);
                           $insert_query .= $before.$key.'="'.encode(AS_DB, $value).'"';
                       }

                       // default
                       elseif (!empty($value)) {
                           $insert_query .= $before.$key.'="'.encode(AS_DB, $value).'"';
                       }
                   }
               }
               if (!$do_it) {
                   $do_it = true;
               } else {
                   $insert_query = str_replace('SET,', 'SET ', $insert_query);
                   $result_insert = $this->_db_connector->performQuery($insert_query);
                   if (!isset($result_insert)) {
                       trigger_error('Problem creating item.', E_USER_ERROR);
                   } else {
                       if (!empty($old_item_id)) {
                           if (!empty($new_item_id)) {
                               if (CS_FILE_TYPE == DBTable2Type($this->_db_table)) {
                                   $retour[CS_FILE_TYPE.$old_item_id] = $new_item_id;
                               } else {
                                   $retour[$old_item_id] = $new_item_id;
                               }
                           }
                       }

                       // link_item_modifier
                       if (!empty($new_item_id)
                            and !empty($user_id)
                            and CS_FILE_TYPE != DBTable2Type($this->_db_table)
                            and CS_LINKITEMFILE_TYPE != DBTable2Type($this->_db_table)
                            and CS_LINK_TYPE != DBTable2Type($this->_db_table)
                            and CS_TAG2TAG_TYPE != DBTable2Type($this->_db_table)
                       ) {
                           $this->_createEntryInLinkItemModifier($new_item_id, $user_id);
                       }
                   }
               }
           }
       }

       return $retour;
   }

   public function _createEntryInLinkItemModifier($item_id, $user_id)
   {
       $manager = $this->_environment->getLinkModifierItemManager();
       $manager->markEdited($item_id, $user_id);
   }

   public function refreshInDescLinks($context_id, $id_array)
   {
       $query = '';
       $query .= 'SELECT item_id, description FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB, $context_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
       } else {
           foreach ($result as $query_result) {
               $replace = false;
               $item_id = $query_result['item_id'];
               $desc = $query_result['description'];
               preg_match_all('~\[[0-9]*(\]|\|)~u', $query_result['description'], $matches);
               if (isset($matches[0])) {
                   foreach ($matches[0] as $match) {
                       $id = mb_substr($match, 1);
                       $last_char = mb_substr($id, mb_strlen($id));
                       $id = mb_substr($id, 0, mb_strlen($id) - 1);
                       if (isset($id_array[$id])) {
                           $desc = str_replace('['.$id.$last_char, '['.$id_array[$id].$last_char, $desc);
                           $replace = true;
                       }
                   }
               }
               // preg_match_all('~\(:item ([0-9]*) ~u', $query_result['description'], $matches);
               // because of html tags from (f)ckeditor
               preg_match_all('~\(:item[^0-9]*([0-9]*) ~u', $query_result['description'], $matches);
               if (isset($matches[1])
                    and !empty($matches[1])
               ) {
                   foreach ($matches[1] as $key => $match) {
                       $id = $match;
                       if (isset($id_array[$id])) {
                           // $desc = str_replace('(:item '.$id,'(:item '.$id_array[$id],$desc);
                           $match2 = str_replace($id, $id_array[$id], strip_tags($matches[0][$key]));
                           // if there are html tags, then there are double spaces, don't know why (IJ 27.10.2011)
                           $match2 = str_replace('  ', ' ', $match2);
                           $desc = str_replace($matches[0][$key], $match2, $desc);
                           $replace = true;
                       }
                   }
               }
               if (strstr($desc, '<!-- KFC TEXT')
                    and $replace
               ) {
                   $desc = renewSecurityHash($desc);
               }
               $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET description="'.encode(AS_DB, $desc).'" WHERE item_id='.encode(AS_DB, $item_id);
               $result_update = $this->_db_connector->performQuery($query);
               if (!isset($result_update) or !$result_update) {
                   trigger_error('Problems refresh links in description "'.$this->_db_table.'".', E_USER_WARNING);
               } else {
                   unset($result_update);
               }
           }
           unset($result);
       }
   }

   public function _createItemInItemTable($context_id, $type, $date)
   {
       $retour = '';
       $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB, $context_id).'",'.
              'modification_date="'.encode(AS_DB, $date).'",'.
              'type="'.$type.'"';
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problem creating item.', E_USER_ERROR);
       } else {
           $retour = $result;
           unset($result);
       }

       return $retour;
   }

   public function deleteReallyOlderThan($days)
   {
       $retour = false;
       $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
       $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'"';
       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problem deleting items.', E_USER_ERROR);
       } else {
           unset($result);
           $retour = true;
       }

       return $retour;
   }

   public function getLastQuery()
   {
       return $this->_last_query;
   }

   public function _updateFromBackup($data_array)
   {
       $success = false;

       // get columns form database table
       if (!isset($this->_key_array)) {
           $query = 'SHOW COLUMNS FROM '.$this->addDatabasePrefix($this->_db_table);
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result)) {
               trigger_error('Problem get colums from table '.$this->_db_table.'.', E_USER_ERROR);
           } else {
               $this->_key_array = [];
               foreach ($result as $query_result) {
                   $this->_key_array[] = $query_result['Field'];
               }
               unset($result);
           }
       }

       // perform update
       $query = '';
       $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';

       $query .= ' SET ';
       $first = true;

       foreach ($data_array as $key => $value) {
           if ('item_id' != $key
                and 'files_id' != $key
                and 'version_id' != $key
                and in_array($key, $this->_key_array)
           ) {
               if ($first) {
                   $first = false;
               } else {
                   $query .= ',';
               }
               $query .= $key.'="'.encode(AS_DB, $value).'"';
           }
       }

       if (!isset($data_array['deleter_id']) or empty($data_array['deleter_id'])) {
           $query .= ',deleter_id=NULL';
       }
       if (!isset($data_array['deletion_date']) or empty($data_array['deletion_date'])) {
           $query .= ',deletion_date=NULL';
       }

       if (CS_FILE_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' WHERE files_id="'.encode(AS_DB, $data_array['files_id']).'"';
       } elseif (CS_TAG2TAG_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' WHERE link_id="'.encode(AS_DB, $data_array['link_id']).'"';
       } else {
           $query .= ' WHERE item_id="'.encode(AS_DB, $data_array['item_id']).'"';
       }
       if (isset($data_array['version_id'])) {
           $query .= ' AND version_id="'.encode(AS_DB, $data_array['version_id']).'"';
       } elseif (CS_MATERIAL_TYPE == DBTable2Type($this->_db_table)) {
           $query .= ' AND version_id="0"';
       }
       $query .= ';';

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result) or !$result) {
           trigger_error('Problem backuping item.', E_USER_ERROR);
       } else {
           $success = true;
           unset($result);
       }

       return $success;
   }

   public function existsItem($item_id)
   {
       $retour = false;
       if (!empty($item_id)) {
           $query = 'SELECT item_id FROM '.$this->addDatabasePrefix($this->_db_table);
           $query .= ' WHERE item_id = "'.encode(AS_DB, $item_id).'"';
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result)) {
               trigger_error('Problems selecting one label.', E_USER_WARNING);
           } elseif (!empty($result[0])) {
               $retour = true;
           }
       }

       return $retour;
   }

   public function addDatabasePrefix($db_table)
   {
       return $db_table;
   }

   public function setWithoutDatabasePrefix()
   {
       $this->_with_db_prefix = false;
   }

   public function setWithDatabasePrefix()
   {
       $this->_with_db_prefix = true;
   }

   public function withDatabasePrefix()
   {
       return $this->_with_db_prefix;
   }

    public function deleteFromDb($context_id)
    {
        $query = 'DELETE FROM '.$this->_db_table.' WHERE '.$this->_db_table.'.context_id = "'.$context_id.'"';
        $this->_db_connector->performQuery($query);
    }

    /**
     * @param int[] $contextIds List of context ids
     * @param array Limits for buzzwords / categories
     * @param \DateTime $newerThen   The oldest modification date to consider
     * @param int[]     $excludedIds Ids to exclude
     *
     * @return \cs_list
     */
    protected function setGenericNewestItemsLimits($contextIds, $limits, DateTime $newerThen = null, $excludedIds = [])
    {
        $this->reset();

        $this->setContextArrayLimit($contextIds);
        $this->setDeleteLimit(true);
        $this->setInactiveEntriesLimit(self::SHOW_ENTRIES_ONLY_ACTIVATED);

        if ($newerThen) {
            $this->setModificationNewerThenLimit($newerThen);
        }

        if ($excludedIds) {
            $this->setExcludedIdsLimit($excludedIds);
        }

        if (isset($limits['buzzword'])) {
            $this->setBuzzwordLimit($limits['buzzword']);
        }

        if (isset($limits['categories'])) {
            $this->setTagArrayLimit($limits['categories']);
        }
    }
}
