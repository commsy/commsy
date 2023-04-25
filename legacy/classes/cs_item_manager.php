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

/** class for database connection to the database table "material"
 * this class implements a database manager for the table "material".
 */
class cs_item_manager extends cs_manager
{
    /**
     * integer - containing the age of news as a limit.
     */
    public $_age_limit = null;

    public $_type_limit = null;

    public $_label_limit = null;

    public $_list_limit = null;

    public $_matrix_limit = null;

    public $_interval_limit = null;

    public $_type_array_limit = [];

    public $_user_userid_limit = null;
    public $_user_authsourceid_limit = null;
    public $_user_since_lastlogin_limit = null;
    public $_cache_row = [];
    private bool $_no_interval_limit = false;

    /**
     * integer - containing the age of material as a limit.
     */
    public $_type = null;

    /** constructor: cs_item_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'items';
    }

     /** reset limits
      * reset limits of this class: age limit and all limits from upper class.
      *
      * @author CommSy Development Group
      */
     public function resetLimits()
     {
         parent::resetLimits();
         $this->_age_limit = null;
         $this->_type_limit = null;
         $this->_order_limit = null;
         $this->_list_limit = null;
         $this->_matrix_limit = null;
         $this->_label_limit = null;
         $this->_interval_limit = null;
         $this->_type_array_limit = [];
         $this->_user_userid_limit = null;
         $this->_user_authsourceid_limit = null;
         $this->_user_since_lastlogin_limit = null;
         $this->_no_interval_limit = false;
     }

     /** set age limit
      * this method sets an age limit for items.
      *
      * @param int limit age limit for items
      *
      * @author CommSy Development Group
      */
     public function setAgeLimit($limit)
     {
         $this->_age_limit = (int) $limit;
     }

     public function setIntervalLimit($interval)
     {
         $this->_interval_limit = (int) $interval;
     }

     public function setNoIntervalLimit()
     {
         $this->_no_interval_limit = true;
     }

     public function setTypeArrayLimit($array)
     {
         $this->_type_array_limit = $array;
     }

     public function setOrderLimit($limit)
     {
         $this->_order_limit = $limit;
     }

     public function setListLimit($limit)
     {
         $this->_list_limit = $limit;
     }

     public function setMatrixLimit($limit)
     {
         $this->_matrix_limit = $limit;
     }

     public function setUserUserIDLimit($limit)
     {
         $this->_user_userid_limit = $limit;
     }

     public function setUserAuthSourceIDLimit($limit)
     {
         $this->_user_authsourceid_limit = $limit;
     }

     public function setUserSinceLastloginLimit()
     {
         $this->_user_sincelastlogin_limit = true;
     }

     public function _performQuery($mode = 'select')
     {
         if ('count' == $mode) {
             $query = 'SELECT count('.$this->addDatabasePrefix('items').'.item_id) AS count';
         } elseif ('id_array' == $mode) {
             $query = 'SELECT '.$this->addDatabasePrefix('items').'.item_id';
         } else {
             $query = 'SELECT '.$this->addDatabasePrefix('items').'.*,label.type AS subtype';
         }
         $query .= ' FROM '.$this->addDatabasePrefix('items');
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';

         if (isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
              and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
         ) {
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.context_id';
         }

         if (isset($this->_list_limit)) {
             if (-1 == $this->_list_limit) {
                 $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="buzzword_for"';
                 $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
             } else {
                 $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="buzzword_for"';
                 $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
             }
         }

         if (isset($this->_tag_limit)) {
             $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('items').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('items').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
         }

         $query .= ' WHERE 1';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.draft != "1"';

         if (isset($this->_list_limit)) {
             if (-1 == $this->_list_limit) {
                 $query .= ' AND (links.to_item_id IS NULL OR links.deletion_date IS NOT NULL)';
             } else {
                 $query .= ' AND buzzwords.item_id="'.encode(AS_DB, $this->_list_limit).'"';
             }
         }

         if (isset($this->_tag_limit)) {
             $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
             $id_string = implode(', ', $tag_id_array);
             if (isset($tag_id_array[0]) and -1 == $tag_id_array[0]) {
                 $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
                 $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
             } else {
                 $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB, $id_string).') OR l41.second_item_id IN ('.encode(AS_DB, $id_string).') )';
                 $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB, $id_string).') OR l42.second_item_id IN ('.encode(AS_DB, $id_string).') ))';
             }
         }

         switch ($this->inactiveEntriesLimit) {
             case self::SHOW_ENTRIES_ONLY_ACTIVATED:
                 $query .= ' AND ('.$this->addDatabasePrefix('items').'.modification_date IS NULL OR '.$this->addDatabasePrefix('items').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
                 break;
             case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
                 $query .= ' AND ('.$this->addDatabasePrefix('items').'.modification_date IS NOT NULL AND '.$this->addDatabasePrefix('items').'.modification_date > "'.getCurrentDateTimeInMySQL().'")';
                 break;
         }

         if (isset($this->_existence_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
         }
         if (isset($this->_type_array_limit) and !empty($this->_type_array_limit)) {
             $query .= ' AND (';
             $first = true;
             foreach ($this->_type_array_limit as $type) {
                 if ($first) {
                     $first = false;
                 } else {
                     $query .= ' OR';
                 }
                 $query .= ' '.$this->addDatabasePrefix('items').'.type = "'.encode(AS_DB, $type).'"';
             }
             $query .= ' )';
         }
         if (isset($this->_room_limit) and empty($this->_room_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         } elseif (empty($this->_room_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
         }
         if (isset($this->_id_array_limit) and !empty($this->_id_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
         }
         if (isset($this->_type_limit) or isset($this->_label_limit)) {
             $query .= ' AND (';
             if (isset($this->_type_limit)) {
                 $first = true;
                 foreach ($this->_type_limit as $type) {
                     if ($first) {
                         $first = false;
                     } else {
                         $query .= ' OR';
                     }
                     $query .= ' '.$this->addDatabasePrefix('items').'.type = "'.encode(AS_DB, $type).'"';
                 }
             }
             if (isset($this->_label_limit)) {
                 $first = true;
                 if (isset($this->_type_limit)) {
                     $query .= ' OR';
                 }
                 foreach ($this->_label_limit as $type) {
                     if ($first) {
                         $first = false;
                     } else {
                         $query .= ' OR';
                     }
                     $query .= ' label.type = "'.encode(AS_DB, $type).'"';
                 }
             }
             $query .= ')';
         }
         if (true == $this->_delete_limit) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
             $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         }
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
         }

         if (isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
              and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
         ) {
             $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB, $this->_user_userid_limit).'"';
             $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB, $this->_user_authsourceid_limit).'"';
             if (isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit) {
                 $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin < '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
             }
         }
         // context array limit
         if (!empty($this->_room_array_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id IN ('.implode(', ', encode(AS_DB, $this->_room_array_limit)).')';
         }
         $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
         if (!isset($this->_id_array_limit)) {
             if ('select' == $mode and !(isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit)
                  and !$this->_no_interval_limit
             ) {
                 $query .= ' LIMIT ';
                 if (isset($this->_interval_limit)) {
                     $query .= $this->_interval_limit;
                 } else {
                     $query .= CS_LIST_INTERVAL;
                 }
             }
         }

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             return $result;
         }
     }

    public function getItemList($id_array)
    {
        return $this->_getItemList('items', $id_array);
    }

    public function getPrivateRoomHomeItemList($id_array)
    {
        $list = null;
        /** cs_list is needed for storage the commsy items.
         */
        $type = 'items';
        if (empty($id_array)) {
            return new cs_list();
        } else {
            if ('discussion' == $type) {
                $type = 'discussions';
            } elseif ('todo' == $type) {
                $type = 'todos';
            }
            $query = 'SELECT * FROM '.encode(AS_DB, $this->addDatabasePrefix($type)).' WHERE '.encode(AS_DB, $this->addDatabasePrefix($type)).".item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
            $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
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
            unset($id_array);
            unset($type);

            return $list;
        }
    }

    public function getPrivateRoomItemList($id_array, $user_ids)
    {
        $list = null;
        /** cs_list is needed for storage the commsy items.
         */
        $type = 'items';
        if (empty($id_array)) {
            return new cs_list();
        } else {
            if ('discussion' == $type) {
                $type = 'discussions';
            } elseif ('todo' == $type) {
                $type = 'todos';
            }
            $query = 'SELECT DISTINCT * FROM '.encode(AS_DB, $this->addDatabasePrefix($type));
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_modifier_item').' AS modifier ON '.$this->addDatabasePrefix('items').'.item_id=modifier.item_id';
            $query .= ' WHERE '.encode(AS_DB, $this->addDatabasePrefix($type)).".item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
            $query .= ' AND modifier.modifier_id IN ('.implode(',', encode(AS_DB, $user_ids)).')';
            $query .= ' GROUP BY '.$this->addDatabasePrefix('items').'.item_id ';
            $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
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
            unset($id_array);
            unset($type);

            return $list;
        }
    }

     public function getAllUsedRubricsOfRoomList($room_ids)
     {
         $rs = [];
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.context_id, '.$this->addDatabasePrefix('items').'.type, label.type AS subtype';
         $query .= ' FROM '.$this->addDatabasePrefix('items');
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
         $query .= ' WHERE 1';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(',', encode(AS_DB, $room_ids)).')';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
         }
         $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.context_id DESC';
         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             $rs = $result;
         }

         return $rs;
     }

     public function getAllNewEntriesOfRoomList($room_ids)
     {
         $rs = [];
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.item_id';
         $query .= ' FROM '.$this->addDatabasePrefix('items');
         $query .= ' WHERE 1';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(',', encode(AS_DB, $room_ids)).')';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
         }
         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             foreach ($result as $query_result) {
                 $rs[] = $query_result['item_id'];
             }
         }

         return $rs;
     }

     public function getAllNewEntriesOfHomeView($room_id)
     {
         $rs = [];
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('items').'.type, label.type AS subtype';
         $query .= ' FROM '.$this->addDatabasePrefix('items');
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
         $query .= ' WHERE 1';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id ="'.encode(AS_DB, $room_id).'"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "discarticle"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "section"';
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "announcement"';
             $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "date"';
         }
         $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
         if (isset($this->_age_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
         }
         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             foreach ($result as $query_result) {
                 if ('label' == $query_result['type']) {
                     $rs[$query_result['subtype']][] = $query_result['item_id'];
                 } else {
                     $rs[$query_result['type']][] = $query_result['item_id'];
                 }
             }
         }
         if (isset($this->_age_limit)) {
             $dates_manager = $this->_environment->getDatesManager();
             $dates_manager->reset();
             $dates_manager->setContextLimit($this->_environment->getCurrentContextID());
             // Get all current dates items
             $dates_manager->setFutureLimit();
             $dates_manager->setDateModeLimit(3);
             $ids = $dates_manager->getIDs(); // saved in session for browsing details
             $rs['date'] = $ids;
             $announcement_manager = $this->_environment->getAnnouncementManager();
             $announcement_manager->reset();
             $announcement_manager->setContextLimit($this->_environment->getCurrentContextID());
             $announcement_manager->setDateLimit(getCurrentDateTimeInMySQL());
             $announcement_manager->setSortOrder('modified');
             $ids = $announcement_manager->getIDs();
             $rs['announcement'] = $ids;
         }

         return $rs;
     }

     public function getCountExistingItemsOfUser($user_id)
     {
         $query = 'SELECT count('.$this->addDatabasePrefix('items').'.item_id) AS count';
         $query .= ' FROM '.$this->addDatabasePrefix('items');
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_modifier_item').' AS l1 ON '.$this->addDatabasePrefix('items').'.item_id=l1.item_id AND l1.modifier_id="'.encode(AS_DB, $user_id).'"';
         $query .= ' WHERE 1';

         if (isset($this->_room_limit)) {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
         } else {
             $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
         }
         $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or empty($result[0])) {
             trigger_error('Problems selecting items from query: "'.$query.'"', E_USER_WARNING);
         } else {
             return $result[0]['count'];
         }
     }

    /** get a type of an item.
     *
     * @param int item_id id of the item
     *
     * @return string type of an item
     */
    public function getItemType($iid)
    {
        $type = '';
        $query = 'SELECT '.$this->addDatabasePrefix('items').'.type';
        $query .= ' FROM '.$this->addDatabasePrefix('items');
        $query .= ' WHERE item_id = "'.encode(AS_DB, $iid).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting an item from query: "'.$query.'"', E_USER_WARNING);
            $success = false;
        } else {
            foreach ($result as $query_result) {
                $type = $query_result['type'];
            }
        }

        return $type;
    }

     /** build a new item
      * this method returns a new EMTPY material item.
      *
      * @return object cs_item a new EMPTY material
      *
      * @author CommSy Development Group
      */
     public function getNewItem()
     {
         return new cs_item($this->_environment);
     }

      /** get an item.
       *
       * @param int item_id id of the item
       *
       * @return \cs_item an item
       */
      public function getItem($iid, $vid = null)
      {
          if (!is_numeric($iid)) {
              return null;
          }

          if (isset($vid) && !is_numeric($vid)) {
              return null;
          }

          if (!isset($this->_cache_object[$iid])) {
              $query = 'SELECT *';
              $query .= ' FROM '.$this->addDatabasePrefix('items');
              $query .= ' WHERE item_id="'.encode(AS_DB, $iid).'"';
              $result = $this->_db_connector->performQuery($query);
              if (isset($result) and !empty($result)) {
                  $item = $this->_buildItem($result[0]);
                  $this->_cache_object[$iid] = $item;
              } else {
                  return null;
              }
          }

          return $this->_cache_object[$iid];
      }

      /**
       * @throws \Doctrine\DBAL\Exception
       */
      public function getExternalViewerForItem(int $itemId, string $username): bool
      {
          $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

          $queryBuilder
              ->select('e.user_id')
              ->from($this->addDatabasePrefix('external_viewer'), 'e')
              ->where('e.item_id = :itemId')
              ->andWhere('e.user_id = :username')
              ->setParameter('itemId', $itemId)
              ->setParameter('username', $username);

          $result = $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());

          if (isset($result) and !empty($result)) {
              return true;
          }

          return false;
      }

      public function getExternalViewerUserStringForItem(int $iid): string
      {
          $externalViewer = $this->getExternalViewerUserArrayForItem($iid);

          return empty($externalViewer) ? '' : implode(' ', $externalViewer);
      }

      public function getExternalViewerUserArrayForItem(int $iid): array
      {
          $query = 'SELECT user_id';
          $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
          $query .= ' WHERE item_id="'.$iid.'"';

          $result = $this->_db_connector->performQuery($query);
          if (isset($result) and !empty($result)) {
              return array_column($result, 'user_id');
          }

          return [];
      }

     public function deleteExternalViewerEntry($iid, $user_id)
     {
         $query = 'DELETE';
         $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
         $query .= ' WHERE item_id="'.$iid.'" and user_id = "'.$user_id.'"';
         $result = $this->_db_connector->performQuery($query);
     }

     public function setExternalViewerEntry($iid, $user_id)
     {
         $query = 'INSERT INTO '.$this->addDatabasePrefix('external_viewer').' SET '.
                    'item_id="'.encode(AS_DB, $iid).'",'.
                    'user_id="'.encode(AS_DB, $user_id).'"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems creating external_view entry from query: "'.$query.'"', E_USER_WARNING);
         }
     }

      /**
       * @throws \Doctrine\DBAL\Exception
       */
      public function getExternalViewerEntriesForRoom(int $room_id): array
      {
          $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

          $queryBuilder
              ->select('e.item_id')
              ->distinct()
              ->from($this->addDatabasePrefix($this->_db_table), 'i')
              ->innerJoin('i', $this->addDatabasePrefix('external_viewer'), 'e', 'i.item_id = e.item_id')
              ->where('i.context_id = :contextId')
              ->andWhere('i.deleter_id IS NULL')
              ->andWhere('i.deletion_date IS NULL')
              ->setParameter('contextId', $room_id);

          $result = $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());

          return array_column($result, 'item_id');
      }

      /**
       * @throws \Doctrine\DBAL\Exception
       */
      public function getExternalViewerEntriesForUser(string $userId): array
      {
          $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

          $queryBuilder
              ->select('e.item_id')
              ->distinct()
              ->from($this->addDatabasePrefix($this->_db_table), 'i')
              ->innerJoin('i', $this->addDatabasePrefix('external_viewer'), 'e', 'i.item_id = e.item_id')
              ->where('e.user_id = :userId')
              ->andWhere('i.deleter_id IS NULL')
              ->andWhere('i.deletion_date IS NULL')
              ->setParameter('userId', $userId);

          $result = $this->_db_connector->performQuery($queryBuilder->getSQL(), $queryBuilder->getParameters());

          return array_column($result, 'item_id');
      }

    /** Prepares the db_array for the item.
     *
     * @param $db_array Contains the data from the database
     *
     * @return array Contains prepared data ( textfunctions applied etc. )
     */
    public function _buildItemArray($db_array)
    {
        return $db_array;
    }

    public function setCommunityHomeLimit()
    {
        $this->_type_limit = [0 => 'materials', 1 => CS_MATERIAL_TYPE];
        $this->_label_limit = [0 => CS_TOPIC_TYPE];
    }

     public function deleteSpecialItems($context_id, $type)
     {
         $current_user = $this->_environment->getCurrentUserItem();
         $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id='.encode(AS_DB, $current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB, $context_id).' AND type="'.encode(AS_DB, $type).'"';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problems deleting items from query: "'.$query.'"', E_USER_WARNING);
         }
     }

     public function deleteReallyOlderThan($days)
     {
         $retour = false;
         $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
         $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'" AND type != "'.CS_DISCARTICLE_TYPE.'" AND type != "'.CS_USER_TYPE.'";'; // user und discarticle werden noch gebraucht
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result) or !$result) {
             trigger_error('Problem deleting items.', E_USER_ERROR);
         } else {
             unset($result);
             $retour = true;
         }

         return $retour;
     }

     // #######################################################
     // statistic functions
     // #######################################################

     public function getCountItems($start, $end)
     {
         $retour = 0;

         $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as number FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB, $this->_room_limit)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date > '".encode(AS_DB, $start)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date < '".encode(AS_DB, $end)."';";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems counting items with query: '.$query, E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $retour = $rs['number'];
             }
             unset($result);
         }

         return $retour;
     }

     public function getItemsForNewsletter($room_id_array, $user_id_array, $age_limit)
     {
         $query1 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('items').'.context_id, '.$this->addDatabasePrefix('items').'.type FROM '.$this->addDatabasePrefix('items');
         $query1 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(', ', encode(AS_DB, $room_id_array)).')';
         $query1 .= ' AND modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $age_limit).' day) AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         $query1 .= ' AND '.$this->addDatabasePrefix('items').'.type !="task" AND '.$this->addDatabasePrefix('items').'.type !="link_item"';
         $query1 .= ' ORDER BY context_id, type';
         // perform query
         $result1 = [];
         $result1 = $this->_db_connector->performQuery($query1);
         if (!isset($result1)) {
             trigger_error('Problems selecting items from query: "'.$query1.'"', E_USER_WARNING);
         }
         $query2 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id,'.$this->addDatabasePrefix('noticed').'.read_date,'.$this->addDatabasePrefix('noticed').'.user_id FROM '.$this->addDatabasePrefix('items');
         $query2 .= ' INNER JOIN '.$this->addDatabasePrefix('noticed').' ON '.$this->addDatabasePrefix('noticed').'.item_id = '.$this->addDatabasePrefix('items').'.item_id';
         $query2 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(', ', encode(AS_DB, $room_id_array)).')';
         $query2 .= ' AND '.$this->addDatabasePrefix('noticed').'.user_id IN ('.implode(', ', encode(AS_DB, $user_id_array)).')';
         $query2 .= ' AND '.$this->addDatabasePrefix('items').'.modification_date <= '.$this->addDatabasePrefix('noticed').'.read_date';
         $query2 .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
         // perform query
         $r2 = $this->_db_connector->performQuery($query2);
         if (!isset($r2)) {
             trigger_error('Problems selecting items from query: "'.$query2.'"', E_USER_WARNING);
         }
         $result2 = [];
         $read_date_array = [];
         foreach ($r2 as $r) {
             $result2[] = $r['item_id'];
         }

         $query3 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('noticed').'.read_date,'.$this->addDatabasePrefix('noticed').'.user_id FROM '.$this->addDatabasePrefix('items');
         $query3 .= ' INNER JOIN '.$this->addDatabasePrefix('noticed').' ON '.$this->addDatabasePrefix('noticed').'.item_id = '.$this->addDatabasePrefix('items').'.item_id';
         $query3 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(', ', encode(AS_DB, $room_id_array)).')';
         $query3 .= ' AND '.$this->addDatabasePrefix('noticed').'.user_id IN ('.implode(', ', encode(AS_DB, $user_id_array)).')';
         $query3 .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';

         $r3 = $this->_db_connector->performQuery($query3);
         foreach ($r3 as $r) {
             $read_date_array[$r['user_id']][$r['item_id']] = $r['read_date'];
         }

         $tmp_result = [];
         $annotation_manager = $this->_environment->getAnnotationManager();
         $discarticle_manager = $this->_environment->getDiscussionArticlesManager();
         $section_manager = $this->_environment->getSectionManager();
         $step_manager = $this->_environment->getStepManager();
         // #####################################################
         $label_manager = $this->_environment->getLabelManager();
         // #####################################################
         $result = [];
         foreach ($result1 as $r) {
             if (!in_array($r['item_id'], $result2)) {
                 if (isset($r['type']) and 'annotation' == $r['type']) {
                     $anno_item = $annotation_manager->getItem($r['item_id']);
                     $linked_item = $anno_item->getLinkedItem();
                     $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'annotation';

                     if (empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
                     }

                     unset($anno_item);
                     unset($linked_item);
                 } elseif (isset($r['type']) and 'discarticle' == $r['type']) {
                     $discarticle_item = $discarticle_manager->getItem($r['item_id']);
                     $linked_item = $discarticle_item->getLinkedItem();
                     $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'discarticle';

                     if (empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
                     }

                     unset($discarticle_item);
                     unset($linked_item);
                 } elseif (isset($r['type']) and 'section' == $r['type']) {
                     $section_item = $section_manager->getItem($r['item_id']);
                     $linked_item = $section_item->getLinkedItem();
                     $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'section';

                     if (empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
                     }

                     unset($section_item);
                     unset($linked_item);
                 } elseif (isset($r['type']) and 'step' == $r['type']) {
                     $step_item = $step_manager->getItem($r['item_id']);
                     $linked_item = $step_item->getLinkedItem();
                     $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'step';

                     if (empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])) {
                         $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
                     }

                     unset($step_item);
                     unset($linked_item);
                 } elseif (isset($r['type']) and 'label' == $r['type']) {
                     $label_item = $label_manager->getItem($r['item_id']);
                     $result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()][] = $label_item->getLabelType();

                     if (empty($read_date_array[$user_id_array[0]][$label_item->getItemID()])) {
                         $result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$label_item->getItemID()])) {
                         $result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()]['noticed'] = 'changed';
                     }

                     unset($label_item);
                 } else {
                     $result[$r['context_id']][$r['type']][$r['item_id']][] = 'entry';
                     $linked_item = $this->getItem($r['item_id']);

                     if (empty($read_date_array[$user_id_array[0]][$r['item_id']])) {
                         $result[$r['context_id']][$r['type']][$r['item_id']]['noticed'] = 'new';
                     } elseif (isset($read_date_array[$user_id_array[0]][$r['item_id']])) {
                         $result[$r['context_id']][$r['type']][$r['item_id']]['noticed'] = 'changed';
                     }

                     unset($linked_item);
                 }
             }
         }
         // ########################
         unset($label_manager);
         // ########################
         unset($step_manager);
         unset($section_manager);
         unset($discarticle_manager);
         unset($annotation_manager);
         return $result;
     }

    public function isItemMarkedAsWorkflowRead($item_id, $user_id)
    {
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.' and user_id = '.$user_id.';';
        $result = $this->_db_connector->performQuery($query);
        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }

    public function getUsersMarkedAsWorkflowReadForItem($item_id)
    {
        $result = [];
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.';';
        $result = $this->_db_connector->performQuery($query);

        return $result;
    }

    public function markItemAsWorkflowRead($item_id, $user_id)
    {
        if (!$this->isItemMarkedAsWorkflowRead($item_id, $user_id)) {
            $query = 'INSERT INTO '.$this->addDatabasePrefix('workflow_read').' (item_id, user_id) VALUES ('.$item_id.', '.$user_id.');';
            $result = $this->_db_connector->performQuery($query);
        }
    }

    public function markItemAsWorkflowNotRead($item_id, $user_id)
    {
        if ($this->isItemMarkedAsWorkflowRead($item_id, $user_id)) {
            $query = 'DELETE FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.' AND user_id = '.$user_id.';';
            $result = $this->_db_connector->performQuery($query);
        }
    }

    public function markItemAsWorkflowNotReadForAllUsers($item_id)
    {
        $query = 'DELETE FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.';';
        $result = $this->_db_connector->performQuery($query);
    }

      public function getAllDraftItems()
      {
          $query = 'SELECT * FROM '.$this->addDatabasePrefix('items').' WHERE draft = 1 AND deletion_date IS NULL AND deleter_id IS NULL;';
          $result = $this->_db_connector->performQuery($query);

          return $result;
      }
}
