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

/** upper class of the tag manager.
 */
include_once 'classes/cs_manager.php';

/** class for database connection to the database table "tag"
 * this class implements a database manager for the table "tag".
 */
class cs_tag_manager extends cs_manager
{
    /**
     * integer - containing the age of last change as a limit in days.
     */
    public $_age_limit = null;

    /**
     * string - containing a title as a limit for select labels.
     */
    public $_title_limit = null;

    /**
     * string - containing a title as a limit for select tag - exact title limit.
     */
    public $_exact_title_limit = null;

    /**
     * integer - containing a start point for the select statement.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many labels the select statement should get.
     */
    public $_interval_limit = null;

    public $_sort_order = null;

    /**
     * string - containing an order limit for the select statement.
     */
    public $_order = null;

    /**
     * array - containing the data from the database -> cache data.
     */
    public $_internal_data = null;

    public $_object_data = null;

    public $_cached_sql = [];

    /*
     * Translation Object
     */
    private $_translator = null;

    /** constructor: cs_tag_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        cs_manager::__construct($environment);
        $this->_db_table = CS_TAG_TYPE;
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset limits
     * reset limits of this class: type limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_age_limit = null;
        $this->_title_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_order = null;
        $this->_sort_order = null;
        $this->_exact_title_limit = null;
        $this->_id_array_limit = [];
    }

    /** set age limit
     * this method sets an age limit for the label (modification date).
     *
     * @param int limit age limit
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set title limit
     * this method sets a title limit.
     *
     * @param string limit title limit for labels
     */
    public function setTitleLimit($limit)
    {
        $this->_title_limit = (string) $limit;
    }

    /** set exact title limit
     * this method sets a title limit - exact.
     *
     * @param string limit title limit (exact) for tags
     */
    public function setExactTitleLimit($limit)
    {
        $this->_exact_title_limit = (string) $limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected labels
     * @param int interval interval limit for selected labels
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

     public function setSortOrder($order)
     {
         $this->_sort_order = (string) $order;
     }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected labels
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
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
         if ($this->_isAvailable()) {
             return parent::getIDArray();
         } else {
             return [];
         }
     }

     private function _getItemOutofCache($item_id)
     {
         $retour = null;

         if (isset($this->_room_limit)) {
             $current_context = $this->_room_limit;
         } else {
             $current_context = $this->_environment->getCurrentContextID();
         }

         if (!isset($this->_object_cache[$current_context])) {
             if (!isset($this->_internal_data[$current_context])) {
                 $this->_loadAllTags();
             }
             if (!empty($this->_internal_data[$current_context][$item_id])) {
                 $this->_object_data[$current_context][$item_id] = $this->_buildItem($this->_internal_data[$current_context][$item_id]);
             }
         }

         if (!empty($this->_object_data[$current_context][$item_id])) {
             $retour = $this->_object_data[$current_context][$item_id];
         }

         return $retour;
     }

     public function resetCache()
     {
         $this->_internal_data = null;
         $this->_object_data = null;
         $this->_cached_sql = [];
     }

      /** select labels limited by limits
       * this method returns a list (cs_list) of labels within the database limited by the limits.
       */
      public function select()
      {
          $data = new cs_list();

          if (isset($this->_id_array_limit)
              && !empty($this->_id_array_limit)
          ) {
              foreach ($this->_id_array_limit as $id) {
                  $item_outof_cache = $this->_getItemOutofCache($id);
                  if (isset($item_outof_cache)) {
                      $data->add($item_outof_cache);
                  }
              }
              if (isset($this->_order)) {
                  if ('title' === $this->_order) {
                      $data->sortby('title');
                  } elseif ('modification_date' === $this->_order) {
                      $data->sortby('date');
                  } else {
                      $data->sortby('title');
                  }
              }
          } else {
              $result = $this->_performQuery();
              $result = is_array($result) ? $result : [];

              foreach ($result as $query_result) {
                  $item = $this->_buildItem($query_result);
                  $data->add($item);
              }
          }

          $this->_data = $data;
      }

    /** perform query for labels: select and count
     * this method perform query for selecting and counting labels.
     *
     * @param bool count true: count labels
     *                      false: select labels
     *
     * @return int num of labels if count = true
     */
    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT DISTINCT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
        } else {
            if ('id_array' == $mode) {
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
            } else {
                $query = 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
            }
        }
        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
        $query .= ' WHERE 1';

        // insert limits into the select statement
        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }
        if (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if ($this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL';
        }
        if (isset($this->_title_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title like "%'.encode(AS_DB, $this->_title_limit).'%"';
        }
        if (isset($this->_exact_title_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title = "'.encode(AS_DB, $this->_exact_title_limit).'"';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }

        if (isset($this->_sort_order)) {
            if ('title' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('title_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
            }
        } elseif (isset($this->_order)) {
            if ('date' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
        }
        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // sixth, perform query
        if (!$this->_force_sql
             and isset($this->_cached_sql[$query])
        ) {
            $result = $this->_cached_sql[$query];
        } else {
            $this->_force_sql = false;
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                if ('count' == $mode) {
                    include_once 'functions/error_functions.php';
                    trigger_error('Problems counting '.$this->_db_table.'.', E_USER_WARNING);
                } elseif ('id_array' == $mode) {
                    include_once 'functions/error_functions.php';
                    trigger_error('Problems selecting '.$this->_db_table.' ids.', E_USER_WARNING);
                } else {
                    include_once 'functions/error_functions.php';
                    trigger_error('Problems selecting '.$this->_db_table.'.', E_USER_WARNING);
                }
            } else {
                if ($this->_cache_on) {
                    $this->_cached_sql[$query] = $result;
                }
            }
        }

        return $result;
    }

     /** get all tags and cache it - INTERNAL
      * this method get all tags for the context and cache it in this class.
      */
     public function _loadAllTags()
     {
         $data_array = [];
         if (isset($this->_room_limit)) {
             $current_context = $this->_room_limit;
         } else {
             $current_context = $this->_environment->getCurrentContextID();
         }

         $this->resetLimits();
         $this->setContextLimit($current_context);
         $result = $this->_performQuery();
         if (!isset($result)) {
             include_once 'functions/error_functions.php';
             trigger_error('Problems selecting all '.$this->_db_table.'.', E_USER_WARNING);
         } else {
             foreach ($result as $query_result) {
                 $data_array[$query_result['item_id']] = $query_result;
             }
         }

         $this->_internal_data[$current_context] = $data_array;
     }

    /** get one tag - INTERNAL
     * this method gets one tag.
     *
     * @param int  item_id  item id of the tag
     */
    public function _getTag($item_id)
    {
        $item = null;
        if (!empty($item_id)) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
            $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id = "'.encode(AS_DB, $item_id).'"';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                include_once 'functions/error_functions.php';
                trigger_error('Problems selecting one '.$this->_db_table.'.', E_USER_WARNING);
            } elseif (!empty($result[0])) {
                $item = $this->_buildItem($result[0]);
            } else {
 //          include_once('functions/error_functions.php');
 //          trigger_error(''.$this->_db_table.' ['.$item_id.'] does not exists.',E_USER_WARNING);
            }
        }

        return $item;
    }

     /**
      *   get empty tag_item.
      *
      *   @return cs_tag_item a tag
      */
     public function getNewItem()
     {
         include_once 'classes/cs_tag_item.php';

         return new cs_tag_item($this->_environment);
     }

    /** get a tag.
     *
     * @param int item_id id of the item
     *
     * @return object cs_item a tag
     */
    public function getItem($item_id)
    {
        $retour = null;

        if (isset($this->_room_limit)) {
            $current_context = $this->_room_limit;
        } else {
            $current_context = $this->_environment->getCurrentContextID();
        }

        if (!isset($this->_internal_data[$current_context])) {
            $this->_loadAllTags();
        }

        if (!isset($this->_internal_data[$current_context][$item_id])
             or empty($this->_internal_data[$current_context][$item_id])
        ) {
            $retour = $this->_getTag($item_id);
        } else {
            $retour = $this->_buildItem($this->_internal_data[$current_context][$item_id]);
        }

        return $retour;
    }

    public function getRootTagItem()
    {
        $retour = null;
        $this->setExactTitleLimit('CS_TAG_ROOT');
        $this->select();
        $list = $this->get();
        if ($list->isNotEmpty() and 1 == $list->getCount()) {
            $retour = $list->getFirst();
        }

        return $retour;
    }

    public function getRootTagItemFor($context_id)
    {
        $retour = null;
        $this->setExactTitleLimit('CS_TAG_ROOT');
        $this->setContextLimit($context_id);
        $this->select();
        $list = $this->get();
        if ($list->isNotEmpty() and 1 == $list->getCount()) {
            $retour = $list->getFirst();
        } elseif ($list->isNotEmpty() and $list->getCount() > 1) {
            include_once 'functions/error_functions.php';
            trigger_error('ERROR: there are more than one root tag item in database table '.$this->_db_table.' for context id '.$context_id, E_USER_ERROR);
        }

        return $retour;
    }

     public function createRootTagItem()
     {
         $this->createRootTagItemFor($this->_environment->getCurrentContextID());
     }

     public function createRootTagItemFor($context_id)
     {
         if (!empty($context_id)) {
             $item = $this->getNewItem();
             $item->setTitle('CS_TAG_ROOT');
             $item->setContextID($context_id);
             $item->setCreatorItem($this->_environment->getCurrentUserItem());
             $item->save();
             unset($item);
         }
     }

     /** get a list of items
      * this method returns a list of items.
      *
      * @param array id_array ids of the items items
      *
      * @return cs_list list of cs_items
      */
     public function getItemList($id_array)
     {
         return $this->_getItemList('tag', $id_array);
     }

    /** Prepares the db_array for the item.
     *
     * @param $db_array Contains the data from the database
     *
     * @return array Contains prepared data ( textfunctions applied etc. )
     */
    public function _buildItem($db_array)
    {
        $item = parent::_buildItem($db_array);

        return $item;
    }

    /** update a tag - internal, do not use -> use method save
     * this method updates a tag.
     *
     * @param object cs_item tag_item the tag
     */
    public function _update($item)
    {
        parent::_update($item);

        $modificator = $item->getModificatorItem();
        $current_datetime = getCurrentDateTimeInMySQL();

        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'modifier_id="'.encode(AS_DB, $modificator->getItemID()).'",'.
                 'modification_date="'.$current_datetime.'",'.
                 'title="'.encode(AS_DB, $item->getTitle()).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems updating '.$this->_db_table.': "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
        }
    }

    /** create a tag - internal, do not use -> use method save
     * this method creates a tag.
     *
     * @param object cs_item tag_item the tag
     */
    public function _create($item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
                 'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                 'type="'.CS_TAG_TYPE.'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems creating '.$this->_db_table.'.', E_USER_ERROR);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $item->setItemID($this->getCreateID());
            $this->_newTag($item);
        }
    }

    /** creates a new tag - internal, do not use -> use method save
     * this method creates a new tag.
     *
     * @param object cs_item tag_item the tag
     */
    public function _newTag($item)
    {
        $user = $item->getCreatorItem();
        $modificator = $item->getModificatorItem();
        $current_datetime = getCurrentDateTimeInMySQL();
        $user_id = $user->getItemID();
        if (empty($user_id)) {
            $user_id = $this->_environment->getRootUserItemID();
        }
        $modificator_id = $modificator->getItemID();
        if (empty($modificator_id)) {
            $modificator_id = $this->_environment->getRootUserItemID();
        }

        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
                  'item_id="'.encode(AS_DB, $item->getItemID()).'",'.
                  'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                  'creator_id="'.encode(AS_DB, $user_id).'",'.
                  'creation_date="'.$current_datetime.'",'.
                  'modifier_id="'.encode(AS_DB, $modificator_id).'",'.
                  'modification_date="'.$current_datetime.'",'.
                  'title="'.encode(AS_DB, $item->getTitle()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems creating '.$this->_db_table.'.', E_USER_WARNING);
        }
    }

    /** save a tag.
     *
     * @param object cs_item the tag
     */
    public function saveItem($item)
    {
        $item_id = $item->getItemID();
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

        // Add modifier to all users who ever edited this item
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_modifier_item_manager->markEdited($item->getItemID());
    }

     /** update a tag, with new informations, e.g. creator and modificator
      * this method updates a tag initially.
      *
      * @param object cs_item tag_item the tag
      */
     public function saveItemNew($item)
     {
         $user = $item->getCreatorItem();
         $modificator = $item->getModificatorItem();
         $current_datetime = getCurrentDateTimeInMySQL();

         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                  'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                  'creator_id="'.encode(AS_DB, $user->getItemID()).'",'.
                  'creation_date="'.$current_datetime.'",'.
                  'modifier_id="'.encode(AS_DB, $modificator->getItemID()).'",'.
                  'modification_date="'.$current_datetime.'",'.
                  'title="'.encode(AS_DB, $item->getTitle()).'"'.
                  ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             include_once 'functions/error_functions.php';
             trigger_error('Problems updating '.$this->_db_table.'.', E_USER_WARNING);
         }
     }

    public function delete($item_id, $deleteTag2TagRecursive = true)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $current_user = $this->_environment->getCurrentUserItem();
        $user_id = $current_user->getItemID() ?: 0;
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $user_id).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems deleting '.$this->_db_table.'.', E_USER_WARNING);
        } else {
            $link_manager = $this->_environment->getLinkItemManager();
            $link_manager->deleteLinksBecauseItemIsDeleted($item_id);
            unset($link_manager);
            $tag2tag_manager = $this->_environment->getTag2TagManager();
            if ($deleteTag2TagRecursive) {
                $tag2tag_manager->deleteTagLinksForTag($item_id);
            } else {
                $tag2tag_manager->deleteTagLinks($item_id);
            }
            unset($tag2tag_manager);
            parent::delete($item_id);
        }
    }

      public function copyDataFromRoomToRoom($old_id, $new_id, $user_id = '', $id_array = '')
      {
          $retour = parent::copyDataFromRoomToRoom($old_id, $new_id, $user_id, $id_array);

          $tag_root_item_old = $this->getRootTagItemFor($old_id);
          if (isset($tag_root_item_old)) {
              $this->forceSQL();
              $tag_root_item_new = $this->getRootTagItemFor($new_id);
              if (isset($tag_root_item_new)) {
                  $retour[$tag_root_item_old->getItemID()] = $tag_root_item_new->getItemID();
              }
          }

          return $retour;
      }

      public function deleteTagsOfUser($uid)
      {
          global $symfonyContainer;
          $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

          if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
              $current_datetime = getCurrentDateTimeInMySQL();
              $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.* FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.creator_id = "'.encode(AS_DB, $uid).'"';
              $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.title != "CS_TAG_ROOT"';
              $result = $this->_db_connector->performQuery($query);
              if (!empty($result)) {
                  foreach ($result as $rs) {
                      $updateQuery = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET';

                      /* flag */
                      if ('FLAG' === $disableOverwrite) {
                          $updateQuery .= ' public = "-1",';
                          $updateQuery .= ' modification_date = "'.$current_datetime.'"';
                      }

                      /* disabled */
                      if ('FALSE' === $disableOverwrite) {
                          $updateQuery .= ' modification_date = "'.$current_datetime.'",';
                          $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                          $updateQuery .= ' public = "1"';
                      }

                      $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                      $result2 = $this->_db_connector->performQuery($updateQuery);
                      if (!isset($result2) or !$result2) {
                          include_once 'functions/error_functions.php';
                          trigger_error('Problems automatic deleting '.$this->_db_table.'.', E_USER_WARNING);
                      }
                      unset($result2);
                  }
                  unset($result);
              }
          }
      }
}
