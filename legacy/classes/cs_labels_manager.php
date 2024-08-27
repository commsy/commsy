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

/** class for database connection to the database table "labels"
 * this class implements a database manager for the table "labels". Labels are groups, topics, labels, ...
 */
class cs_labels_manager extends cs_manager
{
    /**
     * integer - containing the age of last change as a limit in days.
     */
    public $_age_limit = null;

    /**
     * string - containing a type as a limit for select labels (e.g. group, topic, ...).
     */
    public $_type_limit = null;

    /**
     * string - containing a name as a limit for select labels.
     */
    public $_name_limit = null;

    /**
     * string - containing a name as a limit for select labels - exact name limit.
     */
    public $_exact_name_limit = null;

    /**
     * @var mixed|null
     */
    private $excludeNameLimit = null;

    /**
     * integer - containing a start point for the select statement.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many labels the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * integer - containing a id for a dossier.
     */
    public $_dossier_limit = null;

    /**
     * integer - containing the id of a institution as a limit for the selected labels.
     */
    public $_institution_limit = null;

    /**
     * integer - containing the id of a topic as a limit for the selected labels.
     */
    public $_topic_limit = null;

    /**
     * integer - containing the id of a group as a limit for the selected labels.
     */
    public $_group_limit = null;

    public $_sort_order = null;

    /**
     * string - containing an order limit for the select statement.
     */
    public $_order = null;

    /**
     * array - containing the data from the database -> cache data.
     */
    public $_internal_data = [];

    /**
     * string - containing the context of the CommSy: default = uni.
     */
    public $_commsy_context = 'uni';

    public $_count_links = false;

    /*
     * Translation Object
     */
    private $_translator = null;

    /** constructor: cs_labels_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'labels';
        $this->_translator = $environment->getTranslationObject();
    }

  /** reset limits
   * reset limits of this class: type limit, from limit, interval limit, order limit and all limits from upper class.
   */
  public function resetLimits()
  {
      parent::resetLimits();
      $this->_type_limit = null;
      $this->_age_limit = null;
      $this->_name_limit = null;
      $this->_from_limit = null;
      $this->_interval_limit = null;
      $this->_institution_limit = null;
      $this->_topic_limit = null;
      $this->_group_limit = null;
      $this->_dossier_limit = null;
      $this->_order = null;
      $this->_sort_order = null;
      $this->_exact_name_limit = null;
      $this->_count_links = false;
      $this->excludeNameLimit = null;
  }

  public function setGetCountLinks()
  {
      $this->_count_links = true;
  }

  /** set context of the CommSy
   * this method sets a context of the CommSy: uni or school.
   *
   * @param string limit context of the CommSy
   */
  public function setCommSyContext($limit)
  {
      if ('uni' == $limit) {
          $this->_commsy_context = (string) $limit;
      } elseif ('school' == $limit) {
          $this->_commsy_context = (string) $limit;
      } elseif ('none' == $limit or 'project' == $limit) {
          $this->_commsy_context = 'project';
      } else {
          trigger_error('Problems setting CommSy context: use "school", "uni" or "project"', E_USER_WARNING);
      }
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

  /** set type limit
   * this method sets a type limit.
   *
   * @param string limit type limit for labels
   */
  public function setTypeLimit($limit)
  {
      $this->_type_limit = (string) $limit;
  }

  /** set dossier limit
   * this method sets a dosiier limit.
   *
   * @param string limit dossier limit for labels
   */
  public function setDossierLimit()
  {
      $this->_dossier_limit = 'Dossier';
  }

  /** set name limit
   * this method sets a name limit.
   *
   * @param string limit name limit for labels
   */
  public function setNameLimit($limit)
  {
      $this->_name_limit = (string) $limit;
  }

  public function setExcludeNameLimit($excludeName)
  {
      $this->excludeNameLimit = $excludeName;
  }

  /** set exact name limit
   * this method sets a name limit - exact.
   *
   * @param string limit name limit (exact) for labels
   */
  public function setExactNameLimit($limit)
  {
      $this->_exact_name_limit = (string) $limit;
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

    public function setTopicLimit($limit)
    {
        $this->_topic_limit = (int) $limit;
    }

    public function setGroupLimit($limit)
    {
        $this->_group_limit = (int) $limit;
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

     /** select labels limited by limits
      * this method returns a list (cs_list) of labels within the database limited by the limits.
      */
     public function select()
     {
         $data = new cs_list();

         if ($this->_isAvailable()) {
             $result = $this->_performQuery();
             $result = is_array($result) ? $result : [];

             // count links
             $count_array = [];
             if ($this->_count_links && !empty($this->_type_limit)) {
                 $item_id_array = [];
                 foreach ($result as $query_result) {
                     $item_id_array[] = $query_result['item_id'];
                 }
                 $links_manager = $this->_environment->getLinkManager();
                 $count_array = $links_manager->getCountLinksFromItemIDArray($item_id_array, $this->_type_limit);
             }

             foreach ($result as $query_result) {
                 $label_item = $this->_buildItem($query_result);
                 if (!empty($count_array)) {
                     if (!empty($count_array[$label_item->getItemID()])) {
                         $label_item->setCountLinks($count_array[$label_item->getItemID()]);
                     }
                 }
                 $data->add($label_item);
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
          $query = 'SELECT DISTINCT count('.$this->addDatabasePrefix('labels').'.item_id) as count';
      } else {
          if ('id_array' == $mode) {
              $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('labels').'.item_id';
          } else {
              $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('labels').'.*';
          }
      }
      $query .= ' FROM '.$this->addDatabasePrefix('labels');
      $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('labels').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';
      if (!isset($this->_attribute_limit) || (isset($this->_attribute_limit) and ('modificator' == $this->_attribute_limit)) || (isset($this->_attribute_limit) and ('all' == $this->_attribute_limit))) {
          if (isset($this->_sort_order) and ('modificator' == $this->_sort_order or 'modificator_rev' == $this->_sort_order)) {
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('labels').'.creator_id = '.$this->addDatabasePrefix('user').'.item_id';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON (l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l41.second_item_type="'.CS_USER_TYPE.'")))';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON (l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l42.first_item_type="'.CS_USER_TYPE.'")))';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user1 ON user1.item_id = l41.second_item_id';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user2 ON user2.item_id = l42.first_item_id';

              // look in filenames of linked files for the search_limit
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('item_link_file').' ON '.$this->addDatabasePrefix('labels').'.item_id = '.$this->addDatabasePrefix('item_link_file').'.item_iid'.
                        ' LEFT JOIN '.$this->addDatabasePrefix('files').' ON '.$this->addDatabasePrefix('item_link_file').'.file_id = '.$this->addDatabasePrefix('files').'.files_id';
          // look in filenames of linked files for the search_limit
          } elseif (isset($this->_order) and 'creator' == $this->_order) {
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('labels').'.creator_id = '.$this->addDatabasePrefix('user').'.item_id';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON (l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l41.second_item_type="'.CS_USER_TYPE.'")))';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON (l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l42.first_item_type="'.CS_USER_TYPE.'")))';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user1 ON user1.item_id = l41.second_item_id';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user2 ON user2.item_id = l42.first_item_id';

              // look in filenames of linked files for the search_limit
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('item_link_file').' ON '.$this->addDatabasePrefix('labels').'.item_id = '.$this->addDatabasePrefix('item_link_file').'.item_iid'.
                        ' LEFT JOIN '.$this->addDatabasePrefix('files').' ON '.$this->addDatabasePrefix('item_link_file').'.file_id = '.$this->addDatabasePrefix('files').'.files_id';
              // look in filenames of linked files for the search_limit
          }
      }

      // This would be much better to have in cs_group_manager, but requires a lot of legacy code duplication
      if ($this instanceof cs_group_manager && isset($this->_user_limit)) {
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l51 ON ( l51.deletion_date IS NULL AND ((l51.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l51.second_item_type="user"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l52 ON ( l52.deletion_date IS NULL AND ((l52.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l52.first_item_type="user"))) ';
      }

      if (isset($this->_topic_limit)) {
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
      }
      if (isset($this->_group_limit)) {
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
      }

      if (isset($this->_tag_limit)) {
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
      }

      if (isset($this->_buzzword_limit)) {
          if (-1 == $this->_buzzword_limit) {
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l6.link_type="buzzword_for"';
              $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
          } else {
              $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l6 ON l6.from_item_id='.$this->addDatabasePrefix('labels').'.item_id AND l6.link_type="buzzword_for"';
              $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l6.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
          }
      }

      if (!empty($this->_type_limit)) {
          $query .= ' WHERE '.$this->addDatabasePrefix('labels').'.type="'.encode(AS_DB, $this->_type_limit).'"';
      } else {
          $query .= ' WHERE 1';
      }
      if (!empty($this->_dossier_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.name="'.encode(AS_DB, $this->_dossier_limit).'"';
      }

      // insert limits into the select statement
      if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.context_id IN ('.implode(', ', $this->_room_array_limit).')';
      } elseif (isset($this->_room_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
      }

      switch ($this->inactiveEntriesLimit) {
          case self::SHOW_ENTRIES_ONLY_ACTIVATED:
              $query .= ' AND ('.$this->addDatabasePrefix('labels').'.activation_date IS NULL OR '.$this->addDatabasePrefix('labels').'.activation_date <= "'.getCurrentDateTimeInMySQL().'")';
              break;
          case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
              $query .= ' AND ('.$this->addDatabasePrefix('labels').'.activation_date IS NOT NULL AND '.$this->addDatabasePrefix('labels').'.activation_date > "'.getCurrentDateTimeInMySQL().'")';
              break;
      }

      if ($this->_delete_limit) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.deleter_id IS NULL';
      }
      if (isset($this->_name_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.name like "%'.encode(AS_DB, $this->_name_limit).'%"';
      }
      if (isset($this->_exact_name_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.name = "'.encode(AS_DB, $this->_exact_name_limit).'"';
      }
      if (isset($this->_age_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
      }
      if (isset($this->_existence_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
      }

      // This would be much better to have in cs_group_manager, but requires a lot of legacy code duplication
      if ($this instanceof cs_group_manager && isset($this->_user_limit)) {
          $query .= ' AND ((l51.first_item_id = "'.encode(AS_DB, $this->_user_limit).'" OR l51.second_item_id = "'.encode(AS_DB, $this->_user_limit).'")';
          $query .= ' OR (l52.first_item_id = "'.encode(AS_DB, $this->_user_limit).'" OR l52.second_item_id = "'.encode(AS_DB, $this->_user_limit).'"))';
      }

      if (isset($this->_topic_limit)) {
          if (-1 == $this->_topic_limit) {
              $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
              $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
          } else {
              $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'")';
              $query .= ' OR (l22.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l22.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'"))';
          }
      }
      if (isset($this->_institution_limit)) {
          if (-1 == $this->_institution_limit) {
              $query .= ' AND (l11.first_item_id IS NULL AND l11.second_item_id IS NULL)';
              $query .= ' AND (l12.first_item_id IS NULL AND l12.second_item_id IS NULL)';
          } else {
              $query .= ' AND ((l11.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l11.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'")';
              $query .= ' OR (l12.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l12.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'"))';
          }
      }
      if (isset($this->_group_limit)) {
          if (-1 == $this->_group_limit) {
              $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
              $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
          } else {
              $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_group_limit).'")';
              $query .= ' OR (l32.first_item_id = "'.encode(AS_DB, $this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB, $this->_group_limit).'"))';
          }
      }

      if (isset($this->_tag_limit)) {
          $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
          $id_string = implode(', ', $tag_id_array);
          if (isset($tag_id_array[0]) && -1 == $tag_id_array[0]) {
              $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
              $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
          } else {
              $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB, $id_string).') OR l41.second_item_id IN ('.encode(AS_DB, $id_string).') )';
              $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB, $id_string).') OR l42.second_item_id IN ('.encode(AS_DB, $id_string).') ))';
          }
      }

      if (isset($this->_buzzword_limit)) {
          if (-1 == $this->_buzzword_limit) {
              $query .= ' AND (l6.to_item_id IS NULL OR l6.deletion_date IS NOT NULL)';
          } else {
              $query .= ' AND buzzwords.item_id="'.encode(AS_DB, $this->_buzzword_limit).'"';
          }
      }

      if (!empty($this->_id_array_limit)) {
          $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
      }

      if ($this->modificationNewerThenLimit) {
          $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
      }

      if ($this->excludedIdsLimit) {
          $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
      }

      if ($this->excludeNameLimit) {
          $query .= ' AND '.$this->addDatabasePrefix('labels').'.name != "'.encode(AS_DB, $this->excludeNameLimit).'"';
      }

      if (isset($this->_sort_order)) {
          if ('title' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name ASC';
          } elseif ('title_rev' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name DESC';
          } elseif ('name' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name ASC';
          } elseif ('name_rev' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name DESC';
          } elseif ('modificator' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname ASC';
          } elseif ('modificator_rev' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname DESC';
          } elseif ('date' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.modification_date DESC';
          } elseif ('date_rev' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.modification_date ASC';
          }
      } elseif (isset($this->_order)) {
          if ('date' == $this->_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.modification_date DESC, '.$this->addDatabasePrefix('labels').'.name ASC';
          } elseif ('creator' == $this->_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('labels').'.name';
          } else {
              $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name, '.$this->addDatabasePrefix('labels').'.modification_date DESC';
          }
      } else {
          $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name, '.$this->addDatabasePrefix('labels').'.modification_date DESC';
      }
      if ('select' == $mode) {
          if (isset($this->_interval_limit) and isset($this->_from_limit)) {
              $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
          }
      }
      // sixth, perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
          if ('count' == $mode) {
              trigger_error('Problems counting labels.', E_USER_WARNING);
          } elseif ('id_array' == $mode) {
              trigger_error('Problems selecting labels ids.', E_USER_WARNING);
          } else {
              trigger_error('Problems selecting labels.', E_USER_WARNING);
          }
      } else {
          return $result;
      }
  }

    /** get all labels and save it - INTERNAL
     * this method gets all labels for the context and caches it in this class.
     *
     * @param string type type of the label
     */
    public function _getAllLabels($type): void
    {
        $data = [];
        if (isset($this->_room_limit)) {
            $currentContextId = $this->_room_limit;
        } else {
            $currentContextId = $this->_environment->getCurrentContextID();
        }
        if ($this->_isAvailable()) {
            $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
            $queryBuilder
                ->select('l.*', 'i.pinned')
                ->from($this->addDatabasePrefix($this->_db_table), 'l')
                ->innerJoin('l', 'items', 'i', 'i.item_id = l.item_id')
                ->where('l.type = :type')
                ->andWhere('l.context_id = :contextId')
                ->setParameter('type', $type)
                ->setParameter('contextId', $currentContextId);

            try {
                $result = $queryBuilder->executeQuery()->fetchAllAssociative();
            } catch (\Doctrine\DBAL\Exception $e) {
                trigger_error('Problems selecting all labels of type ' . $type . ' from context ' . $currentContextId . ': ' . $e->getMessage(), E_USER_WARNING);
            }

            foreach ($result as $queryResult) {
                $data[] = $queryResult;
            }
        }
        $this->_internal_data[$currentContextId][$type] = $data;
    }

  /** get one label without type information - INTERNAL
   * this method gets one label without type information.
   *
   * @param int|null labelId item ID of the label
   */
  public function _getLabelWithoutType(?int $labelId): ?cs_label_item
  {
      if (empty($labelId)) {
          return null;
      }

      $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
      $queryBuilder
          ->select('l.*', 'i.pinned')
          ->from($this->addDatabasePrefix($this->_db_table), 'l')
          ->innerJoin('l', 'items', 'i', 'i.item_id = l.item_id')
          ->where('l.item_id = :itemId')
          ->setParameter('itemId', $labelId);

      try {
          $result = $queryBuilder->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
          trigger_error('Problems selecting labels item (' . $labelId . '): ' . $e->getMessage(), E_USER_WARNING);
      }

      $label = null;
      if (!empty($result[0])) {
          $label = $this->_buildItem($result[0]);
      }

      return $label;
  }

    /**
      get empty label_item
      @return cs_label_item a label
     */
    public function getNewItem($label_type = '')
    {
        return new cs_label_item($this->_environment, $label_type);
    }

  /** get a label in newest version.
   *
   * @param string  type    type of the label
   * @param int item_id id of the item
   *
   * @return object cs_item a label
   */
  public function getItem(?int $item_id)
  {
      if ($this->_cache_on) {
          if (isset($this->_room_limit)) {
              $current_context = $this->_room_limit;
          } else {
              $current_context = $this->_environment->getCurrentContextID();
          }
          if (isset($this->_type_limit)) {
              $current_module = $this->_environment->getCurrentModule();
              $current_function = $this->_environment->getCurrentFunction();
              if (!isset($this->_internal_data[$current_context][$this->_type_limit])) {
                  $this->_getAllLabels($this->_type_limit);
              }
              reset($this->_internal_data[$current_context][$this->_type_limit]);
              $line = current($this->_internal_data[$current_context][$this->_type_limit]);
              $label = null;
              while ($line and empty($label)) {
                  if ($line['item_id'] == $item_id) {
                      $label = $this->_buildItem($line);
                  }
                  $line = next($this->_internal_data[$current_context][$this->_type_limit]);
              }
              if (!isset($label)) {
                  $label = $this->_getLabelWithoutType($item_id);
              }
          } else {
              $label = $this->_getLabelWithoutType($item_id);
          }
      } else {
          $label = $this->_getLabelWithoutType($item_id);
      }

      return $label;
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
        return $this->_getItemList('labels', $id_array);
    }

  public function getItemByName($name)
  {
      $label = null;
      if (isset($this->_room_limit)) {
          $current_context = $this->_room_limit;
      } else {
          $current_context = $this->_environment->getCurrentContextID();
      }
      if (isset($this->_type_limit)) {
          if (!isset($this->_internal_data[$current_context][$this->_type_limit])) {
              $this->_getAllLabels($this->_type_limit);
          }
          reset($this->_internal_data[$current_context][$this->_type_limit]);
          $line = current($this->_internal_data[$current_context][$this->_type_limit]);
          while ($line and is_null($label)) {
              if ($line['name'] == $name) {
                  $label = $this->_buildItem($line);
              }
              $line = next($this->_internal_data[$current_context][$this->_type_limit]);
          }
      }

      return $label;
  }

  /** Prepares the db_array for the item.
   *
   * @param array $db_array Contains the data from the database
   */
  public function _buildItem(array $db_array)
  {
      if ('ALL' == $db_array['name']) {
          $translator = $this->_environment->getTranslationObject();
          $db_array['name'] = $translator->getMessage('ALL_MEMBERS');
          if ('GROUP_ALL_DESC' == $db_array['description']) {
              $db_array['description'] = $translator->getMessage('GROUP_ALL_DESC');
          }
      }

      if (isset($db_array['extras'])) {
          $db_array['extras'] = unserialize($db_array['extras']);
      }

      return parent::_buildItem($db_array);
  }

     /**
      * update a label - internal, do not use -> use method save
      * this method updates a label.
      *
      * @param cs_label_item $item
      *
      * @author CommSy Development Group
      */
     public function _update($item)
     {
         parent::_update($item);

         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->update($this->addDatabasePrefix('labels'))
             ->set('modifier_id', ':modifierId')
             ->set('modification_date', ':modificationDate')
             ->set('activation_date', ':activationDate')
             ->set('description', ':description')
             ->set('extras', ':extras')
             ->set('public', ':public')
             ->where('item_id = :itemId')
             ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
             ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
             ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
             ->setParameter('description', $item->getDescription())
             ->setParameter('extras', serialize($item->getExtraInformation()))
             ->setParameter('public', $item->isPublic() ? 1 : 0)
             ->setParameter('itemId', $item->getItemID());

         if (!(CS_GROUP_TYPE == $item->getLabelType() && $item->isSystemLabel())) {
             $queryBuilder
                 ->set('name', ':name')
                 ->setParameter('name', $item->getTitle());
         }

         try {
             $queryBuilder->executeStatement();
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
         }
     }

     /**
      * create a label - internal, do not use -> use method save
      * this method creates a label.
      */
     public function _create(cs_label_item $item)
     {
         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->insert($this->addDatabasePrefix('items'))
             ->setValue('context_id', ':contextId')
             ->setValue('modification_date', ':modificationDate')
             ->setValue('activation_date', ':activationDate')
             ->setValue('type', ':type')
             ->setValue('draft', ':draft')
             ->setParameter('contextId', $item->getContextID())
             ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
             ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
             ->setParameter('type', 'label')
             ->setParameter('draft', $item->isDraft());

         try {
             $queryBuilder->executeStatement();

             $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
             $item->setItemID($this->getCreateID());
             $this->_newLabel($item);
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
             $this->_create_id = null;
         }
     }

  /** creates a new label - internal, do not use -> use method save
   * this method creates a new version of a label.
   *
   * @param object cs_item label_item the label
   *
   * @author CommSy Development Group
   */
  public function _newLabel(cs_label_item $item)
  {
      $currentDateTime = getCurrentDateTimeInMySQL();

      $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

      $queryBuilder
          ->insert($this->addDatabasePrefix('labels'))
          ->setValue('item_id', ':itemId')
          ->setValue('context_id', ':contextId')
          ->setValue('creator_id', ':creatorId')
          ->setValue('creation_date', ':creationDate')
          ->setValue('modifier_id', ':modifierId')
          ->setValue('modification_date', ':modificationDate')
          ->setValue('activation_date', ':activationDate')
          ->setValue('name', ':name')
          ->setValue('public', ':public')
          ->setValue('description', ':description')
          ->setValue('extras', ':extras')
          ->setValue('type', ':type')
          ->setParameter('itemId', $item->getItemID())
          ->setParameter('contextId', $item->getContextID())
          ->setParameter('creatorId', $item->getCreatorItem()->getItemID())
          ->setParameter('creationDate', $currentDateTime)
          ->setParameter('modifierId', $item->getModificatorItem()->getItemID())
          ->setParameter('modificationDate', $item->getModificationDate() ?: $currentDateTime)
          ->setParameter('activationDate', $item->isNotActivated() ? $item->getActivatingDate() : null)
          ->setParameter('name', $item->getTitle())
          ->setParameter('public', $item->isPublic() ? 1 : 0)
          ->setParameter('description', $item->getDescription())
          ->setParameter('extras', serialize($item->getExtraInformation()))
          ->setParameter('type', $item->getLabelType());

      try {
          $queryBuilder->executeStatement();
      } catch (\Doctrine\DBAL\Exception) {
          trigger_error('Problems creating announcement.', E_USER_WARNING);
      }
  }

  /** save a label.
   *
   * @param object cs_item label_item the label
   *
   * @author CommSy Development Group
   */
  public function saveItem($label_item)
  {
      $item_id = $label_item->getItemID();
      if (!empty($item_id)) {
          $this->_update($label_item);
      } else {
          $creator_id = $label_item->getCreatorID();
          if (empty($creator_id)) {
              $user = $this->_environment->getCurrentUser();
              $label_item->setCreatorItem($user);
          }
          $this->_create($label_item);
      }

      // Add modifier to all users who ever edited this item
      $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
      $link_modifier_item_manager->markEdited($label_item->getItemID());
  }

    /** update a label, with new informations, e.g. creator and modificator
     * this method updates a label initially.
     *
     * @param object cs_item label_item the label
     */
    public function saveItemNew($item)
    {
        $user = $item->getCreatorItem();
        $modificator = $item->getModificatorItem();
        $current_datetime = getCurrentDateTimeInMySQL();

        if ($item->isPublic()) {
            $public = 1;
        } else {
            $public = 0;
        }

        $query = 'UPDATE '.$this->addDatabasePrefix('labels').' SET '.
                  'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
                  'creator_id="'.encode(AS_DB, $user->getItemID()).'",'.
                  'creation_date="'.$current_datetime.'",'.
                  'modifier_id="'.encode(AS_DB, $modificator->getItemID()).'",'.
                  'modification_date="'.$current_datetime.'",';
        if (!(CS_GROUP_TYPE == $item->getLabelType() and $item->isSystemLabel())) {
            $query .= 'name="'.encode(AS_DB, $item->getTitle()).'",';
        }
        $query .= 'description="'.encode(AS_DB, $item->getDescription()).'",'.
                  'public="'.encode(AS_DB, $public).'",'.
                  "extras='".encode(AS_DB, serialize($item->getExtraInformation()))."'".
                  ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating label.', E_USER_WARNING);
        }
        unset($item);
    }

  public function delete(int $itemId): void
  {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID() ?: 0;
      $query = 'UPDATE '.$this->addDatabasePrefix('labels').' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB, $user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or !$result) {
          trigger_error('Problems deleting label.', E_USER_WARNING);
      } else {
          $link_manager = $this->_environment->getLinkManager();
          $link_manager->deleteLinksBecauseItemIsDeleted($itemId);
          unset($link_manager);
          parent::delete($itemId);
      }
  }

    /*
   checks if label type is supported in the current context
   so far only groups are checked within contexts, since they can be "switched off"
   @return boolean TRUE if supported, FALSE otherwise
   */
    public function _isAvailable()
    {
        return true;
    }

     public function copyDataFromRoomToRoom($old_id, $new_id, $user_id = '', $id_array = '')
     {
         $retour = parent::copyDataFromRoomtoRoom($old_id, $new_id, $user_id, $id_array);

         // group all
         $this->reset();
         $this->setContextLimit($old_id);
         $this->setExactNameLimit('ALL');
         $this->select();
         $old_list = $this->get();
         if ($old_list->isNotEmpty() and 1 == $old_list->getCount()) {
             $old_group_all = $old_list->getFirst();

             $this->reset();
             $this->setContextLimit($new_id);
             $this->setExactNameLimit('ALL');
             $this->select();
             $new_list = $this->get();
             if ($new_list->isNotEmpty() and 1 == $new_list->getCount()) {
                 $new_group_all = $new_list->getFirst();
                 $retour[$old_group_all->getItemID()] = $new_group_all->getItemID();
             }
         }

         // images of labels
         $query = '';
         $query .= 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id="'.encode(AS_DB,
             $new_id).'" AND deleter_id IS NULL AND deletion_date IS NULL';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems getting data "'.$this->_db_table.'".', E_USER_WARNING);
         } else {
             foreach ($result as $query_result) {
                 $extra_array = xml2Array($query_result['extras']);
                 if (isset($extra_array['LABELPICTURE']) and !empty($extra_array['LABELPICTURE'])) {
                     $disc_manager = $this->_environment->getDiscManager();
                     $disc_manager->setPortalID($this->_environment->getCurrentPortalID());
                     if ($disc_manager->copyImageFromRoomToRoom($extra_array['LABELPICTURE'], $new_id)) {
                         $value_array = explode('_', (string) $extra_array['LABELPICTURE']);
                         $value_array[0] = 'cid'.$new_id;
                         $extra_array['LABELPICTURE'] = implode('_', $value_array);

                         $update_query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET extras="'.encode(AS_DB,
                             serialize($extra_array)).'" WHERE item_id="'.$query_result['item_id'].'"';
                         $update_result = $this->_db_connector->performQuery($update_query);
                         if (!isset($update_result) or !$update_result) {
                             trigger_error('Problems updating data "'.$this->_db_table.'".', E_USER_WARNING);
                         }
                     }
                 }
             }
         }

         return $retour;
     }

     public function deleteLabelsOfUser($uid)
     {
         global $symfonyContainer;
         $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

         if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
             $currentDatetime = getCurrentDateTimeInMySQL();
             $query = 'SELECT '.$this->addDatabasePrefix('labels').'.* FROM '.$this->addDatabasePrefix('labels').' WHERE '.$this->addDatabasePrefix('labels').'.creator_id = "'.encode(AS_DB, $uid).'"';
             $result = $this->_db_connector->performQuery($query);

             if (!empty($result)) {
                 foreach ($result as $rs) {
                     // do not delete group "ALL"
                     if (!(CS_GROUP_TYPE == $rs['type'] && 'ALL' == $rs['name'])) {
                         $updateQuery = 'UPDATE '.$this->addDatabasePrefix('labels').' SET';

                         /* flag */
                         if ('FLAG' === $disableOverwrite) {
                             $updateQuery .= ' public = "-1",';
                             $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                         }

                         /* disabled */
                         if ('FALSE' === $disableOverwrite) {
                             $updateQuery .= ' name = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                             $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                             $updateQuery .= ' modification_date = "'.$currentDatetime.'",';
                             $updateQuery .= ' public = "1"';
                         }

                         $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                         $result2 = $this->_db_connector->performQuery($updateQuery);
                         if (!$result2) {
                             trigger_error('Problems automatic deleting labels:.', E_USER_WARNING);
                         }
                     }
                 }
             }
         }

         if (!empty($result)) {
             foreach ($result as $rs) {
                 // Never delete any group "ALL"
                 if (!(CS_GROUP_TYPE == $rs['type'] and 'ALL' == $rs['name'])) {
                 }
             }
         }
     }

    public function resetCache()
    {
        $this->_internal_data = [];
    }

     /**
      * @param int[] $contextIds List of context ids
      * @param array Limits for buzzwords / categories
      * @param int $size Number of items to get
      * @param \DateTime $newerThen The oldest modification date to consider
      * @param int[] $excludedIds Ids to exclude
      *
      * @return \cs_list
      */
     public function getNewestItems($contextIds, $limits, $size, DateTime $newerThen = null, $excludedIds = [])
     {
         parent::setGenericNewestItemsLimits($contextIds, $limits, $newerThen, $excludedIds);

         if ($size > 0) {
             $this->setIntervalLimit(0, $size);
         }

         $this->setExcludeNameLimit('ALL');
         $this->setOrder('date');

         $this->select();

         return $this->get();
     }
}
