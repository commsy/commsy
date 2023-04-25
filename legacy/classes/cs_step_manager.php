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

/** class for database connection to the database table "step"
 * this class implements a database manager for the table "step".
 *
 * @version 2.1 $Revision$
 */
class cs_step_manager extends cs_manager
{
    /**
     * integer - containing a start point for the select step.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many step the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - containing a string as a search limit.
     */
    public $_search_limit = null;

    /**
     *  array - containing an id-array as search limit.
     */
    public $_id_array_limit = [];

    /**
     *  int - containing an item_id as search limit.
     */
    public $_todo_item_id_limit = 0;

    /**
     *  int - containing an version_id as search limit.
     */
    public $_version_id_limit = 0;

    /**
     * bool - tells if the next step will be saved without setting new modification date.
     */
    public $_save_step_without_date = false;

    public $_all_step_list = null;
    public $_cached_todo_item_ids = [];

    /*
     * Translation Object
     */
    private $_translator = null;

    /** constructor: cs_step_manager
     * the only available constructor, initial values for internal variables<br />
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = CS_STEP_TYPE;
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     *
     * @version $Revision$
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_order = null;
        $this->_todo_item_id_limit = 0;
        $this->_version_id_limit = 0;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected step
     * @param int interval interval limit for selected step
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    /**
     * tells to save the next step without a new modifying date.
     */
    public function setSaveStepWithoutDate()
    {
        $this->_save_step_without_date = true;
    }

  /** set todo_item_id limit
   * this method sets an refid limit for the select statement.
   *
   * @param string limit order limit
   */
  public function setTodoItemIDLimit($limit)
  {
      $this->_todo_item_id_limit = (int) $limit;
  }

    public function getIDs()
    {
        return $this->getIDArray();
    }

    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix('step').'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix('step').'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix('step').'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix('step');
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('step').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

        $query .= ' WHERE 1';

        // fifth, insert limits into the select statement
        if (isset($this->_todo_item_id_limit) and !empty($this->_todo_item_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.todo_item_id='.encode(AS_DB, $this->_todo_item_id_limit);
        }
        if (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        } else {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.deleter_id IS NULL';
        }
        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('step').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        // restrict sql-statement by search limit, create wheres
        if (isset($this->_search_limit) and !empty($this->_search_limit)) {
            $query .= ' AND (';

            // todo item
            $query .= ' UPPER('.$this->addDatabasePrefix('step').'.title) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';
            $query .= ' OR UPPER('.$this->addDatabasePrefix('step').'.description) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';
            if (':' != $this->_search_limit and '-' != $this->_search_limit) {
                $query .= ' OR UPPER('.$this->addDatabasePrefix('step').'.modification_date) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';
            }

            // creation date - modification date language problem (TBD)

            // creator and modificator
            $query .= ' OR UPPER(TRIM(CONCAT(people.firstname," ",people.lastname))) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';

            // groups
            $query .= ' OR UPPER(groups.name) LIKE BINARY "%'.encode(AS_DB, $this->_search_limit).'%"';
            $query .= ' )';
        }

        if (isset($this->_search_limit) and !empty($this->_search_limit)) {
            $query .= ' GROUP BY '.$this->addDatabasePrefix('step').'.item_id';
        }
        $query .= ' ORDER BY '.$this->addDatabasePrefix('step').'.item_id ASC, '.$this->addDatabasePrefix('step').'.modification_date DESC, '.$this->addDatabasePrefix('step').'.title DESC';

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
            }
        }

        // perform query
        if (isset($this->_cached_sql[$query])) {
            $result = $this->_cached_sql[$query];
        } else {
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting step from query: "'.$query.'"', E_USER_WARNING);
            } else {
                // sql caching
                if ($this->_cache_on) {
                    $this->_cached_sql[$query] = $result;
                }
            }
        }

        return $result;
    }

    /** build a new todo item
     * this method returns a new EMTPY todo item.
     *
     * @return object cs_item a new EMPTY todo
     */
    public function getNewItem()
    {
        return new cs_step_item($this->_environment);
    }

     public function getItem($item_id)
     {
         $step = null;
         if (!empty($this->_cache_object[$item_id])) {
             $step = $this->_cache_object[$item_id];
         } else {
             $query = 'SELECT * FROM '.$this->addDatabasePrefix('step').' WHERE '.$this->addDatabasePrefix('step').".item_id = '".encode(AS_DB, $item_id)."'";
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result) or empty($result[0])) {
                 trigger_error('Problems selecting one step item from query: "'.$query.'"', E_USER_WARNING);
             } else {
                 $step = $this->_buildItem($result[0]);
             }
         }

         return $step;
     }

    /** get a list of step in newest version.
     *
     * @param array id_array ids of the items
     * @param int version_id version of the items (optional)
     *
     * @return object cs_list of cs_step_items
     */
    public function getItemList($id_array)
    {
        $step_list = null;
        if (empty($id_array)) {
            return new cs_step_list();
        } else {
            $step = null;
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('step').' WHERE '.$this->addDatabasePrefix('step').".item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
            $query .= ' ORDER BY '.$this->addDatabasePrefix('step').'.item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of step items from query: "'.$query.'"', E_USER_WARNING);
            } else {
                $step_list = new cs_step_list();
                foreach ($result as $rs) {
                    $step_list->append($this->_buildItem($rs));
                }
            }

            return $step_list;
        }
    }

    /** get a list of step in newest version.
     *
     * @param array id_array ids of the items
     * @param int version_id version of the items (optional)
     *
     * @return object cs_list of cs_step_items
     */
    public function getAllStepItemListByIDArray($id_array)
    {
        $step_list = null;
        if (empty($id_array)) {
            return new cs_step_list();
        } else {
            $step = null;
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('step')." WHERE todo_item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
            $query .= ' AND '.$this->addDatabasePrefix('step').'.deleter_id IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('step').'.deletion_date IS NULL';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of step items from query: "'.$query.'"', E_USER_WARNING);
            } else {
                $step_list = new cs_step_list();
                foreach ($result as $rs) {
                    $step_item = $this->_buildItem($rs);
                    if (isset($step_item)) {
                        $step_list->append($step_item);
                    }
                    unset($step_item);
                }
            }
            if ($this->_cache_on) {
                $this->_all_step_list = $step_list;
                $this->_cached_todo_item_ids = $id_array;
            }

            return $step_list;
        }
    }

    /** update a step - internal, do not use -> use method save
     * this method updates the database record for a given step item.
     *
     * @param cs_step_item the step item for which an update should be made
     * @param bool can disable setting of new modification date
     */
    public function _update($item)
    {
        $date_string = '';
        if (!$this->_save_step_without_date) {
            parent::_update($item);
            $date_string = 'modification_date="'.getCurrentDateTimeInMySQL().'",';
        }
        $modificator_item = $item->getModificatorItem();

        $query = 'UPDATE '.$this->addDatabasePrefix('step').' SET '.
              $date_string.
              'title="'.encode(AS_DB, $item->getTitle()).'",'.
              'description="'.encode(AS_DB, $item->getDescription()).'",'.
              'minutes="'.encode(AS_DB, $item->getMinutes()).'",'.
              'time_type="'.encode(AS_DB, $item->getTimeType()).'",'.
              'todo_item_id="'.encode(AS_DB, $item->getTodoID()).'",'.
              'modifier_id="'.encode(AS_DB, $modificator_item->getItemID()).'"'.
              ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        // extras (TBD)

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating step from query: "'.$query.'"', E_USER_WARNING);
        }
        $this->_save_step_without_date = false; // restore default
        unset($item);
    }

  /** create a new item in the items table - internal, do not use -> use method save
   * this method creates a new item of type 'nstep' in the database and sets the step items item id.
   * it then calls the private method _newNews to store the step item itself.
   *
   * @param cs_step_item the step item for which an entry should be made
   */
  public function _create($item)
  {
      $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
               'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
               'modification_date="'.getCurrentDateTimeInMySQL().'",'.
               'type="step",'.
               'draft="'.encode(AS_DB, $item->isDraft()).'"';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
          trigger_error('Problems creating step from query: "'.$query.'"', E_USER_WARNING);
          $this->_create_id = null;
      } else {
          $this->_create_id = $result;
          $item->setItemID($this->getCreateID());
          $this->_newStep($item);
      }
      unset($item);
  }

     /**
      * store a new step item to the database - internal, do not use -> use method save
      * this method stores a newly created step item to the database.
      *
      * @param cs_step_item the step item to be stored
      */
     public function _newStep(cs_step_item $item)
     {
         $currentDateTime = getCurrentDateTimeInMySQL();

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->insert($this->addDatabasePrefix('step'))
             ->setValue('item_id', ':itemId')
             ->setValue('context_id', ':contextId')
             ->setValue('creator_id', ':creatorId')
             ->setValue('creation_date', ':creationDate')
             ->setValue('modification_date', ':modificationDate')
             ->setValue('title', ':title')
             ->setValue('description', ':description')
             ->setValue('time_type', ':timeType')
             ->setValue('todo_item_id', ':todoItemId')
             ->setParameter('itemId', $item->getItemID())
             ->setParameter('contextId', $item->getContextID())
             ->setParameter('creatorId', $item->getCreatorID())
             ->setParameter('creationDate', $currentDateTime)
             ->setParameter('modificationDate', $currentDateTime)
             ->setParameter('title', $item->getTitle())
             ->setParameter('description', $item->getDescription())
             ->setParameter('timeType', $item->getTimeType())
             ->setParameter('todoItemId', $item->getTodoID());

         if ($item->getMinutes()) {
             $queryBuilder
                 ->setValue('minutes', ':minutes')
                 ->setParameter('minutes', $item->getMinutes());
         }

         try {
             $queryBuilder->executeStatement();
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
         }
     }

    /**  delete a step item.
     *
     * @param cs_step_item the step item to be deleted
     */
    public function delete($item_id)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix('step').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item_id).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting step from query: "'.$query.'"', E_USER_WARNING);
        } else {
            parent::delete($item_id);
        }
    }

  /** save a commsy item
   * this method saves a commsy item.
   *
   * @param cs_item
   */
  public function saveItem($item, $with_date = true)
  {
      $item_id = $item->getItemID();
      if (!empty($item_id)) {
          if ($item->_version_id_changed) {
              $this->_newStep($item);
          } else {
              $this->_update($item);
          }
      } else {
          $creator_id = $item->getCreatorID();
          if (empty($creator_id)) {
              $item->setCreatorItem($this->_environment->getCurrentUser());
          }
          $this->_create($item);
      }

      // Add modifier to all users who ever edited this step
      $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
      $link_modifier_item_manager->markEdited($item->getItemID());
      unset($item);
      unset($link_modifier_item_manager);
  }

     /** select items limited by limits
      * this method returns a list (cs_list) of items within the database limited by the limits.
      * depends on _performQuery(), which must be overwritten.
      */
     public function select()
     {
         $result = $this->_performQuery();
         $this->_id_array = null;
         $data = new cs_step_list();

         $result = is_array($result) ? $result : [];

         foreach ($result as $query_result) {
             $item = $this->_buildItem($query_result);
             $data->set($item);
         }

         $this->_data = $data;
     }

     public function deleteStepsOfUser($uid)
     {
         global $symfonyContainer;
         $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

         if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
             $currentDatetime = getCurrentDateTimeInMySQL();
             $query = 'SELECT '.$this->addDatabasePrefix('step').'.* FROM '.$this->addDatabasePrefix('step').' WHERE '.$this->addDatabasePrefix('step').'.creator_id = "'.encode(AS_DB, $uid).'"';
             $result = $this->_db_connector->performQuery($query);

             if (!empty($result)) {
                 foreach ($result as $rs) {
                     $updateQuery = 'UPDATE '.$this->addDatabasePrefix('step').' SET';

                     /* flag */
                     if ('FLAG' === $disableOverwrite) {
                         $updateQuery .= ' public = "-1",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                     }

                     /* disabled */
                     if ('FALSE' === $disableOverwrite) {
                         $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                         $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                     }

                     $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                     $result2 = $this->_db_connector->performQuery($updateQuery);
                     if (!$result2) {
                         trigger_error('Problems automatic deleting steps from query: "'.$updateQuery.'"', E_USER_WARNING);
                     }
                 }
             }
         }
     }
}
