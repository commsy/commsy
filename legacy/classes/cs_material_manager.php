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
class cs_material_manager extends cs_manager
{
    /**
     * integer - containing the age of material as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing the world_public of material as a limit.
     */
    public $_public_limit = null;

    /**
     * array - containing the id's of materials as a limit.
     */
    public $_id_limit = null;

    /**
     * integer - containing the id of a group as a limit for the selected material.
     */
    public $_group_limit = null;

    /**
     * integer - containing a start point for the select material.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many material the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * integer - containing the item id of the intstitution as a limit.
     */
    public $_institution_limit = null;

    /**
     * integer - containing the item id of the topic as a limit.
     */
    public $_topics_limit = null;

    /**
     * integer - containing the item id of the ref item as a limit.
     */
    public $_ref_id_limit = null;

    /**
     * integer - containing the item id of the user as a limit.
     */
    public $_ref_user_limit = null;

    /**
     * string - containing an order limit for the select material.
     */
    public $_order = null;

    /**
     * array - containing the cached items already loaded from the database.
     */
    public $_cache = null;

    /**
     * array - containing the selected ids.
     */
    public $_id_array = [];

    public $_limit_only_files_mode = null;

    public $_handle_tmp_manual = false;

    public $_sql_create_temp_material_table = 'CREATE TEMPORARY TABLE temp_material (
  item_id int(11) NOT NULL default "0",
  version_id int(11) NOT NULL default "0",
  context_id int(11) default NULL,
  creator_id int(11) NOT NULL default "0",
  deleter_id int(11) default NULL,
  creation_date datetime NOT NULL default "0000-00-00 00:00:00",
  modifier_id int(11) default NULL,
  modification_date datetime default NULL,
  deletion_date datetime default NULL,
  title varchar(255) NOT NULL,
  description text,
  author varchar(200) default NULL,
  publishing_date varchar(20) default NULL,
  public tinyint(11) NOT NULL default "0",
  world_public smallint(2) NOT NULL default "0",
  extras text,
  new_hack tinyint(1) NOT NULL default "0",
  copy_of int(11) default NULL,
  PRIMARY KEY  (item_id,version_id),
  KEY version_id (version_id),
  KEY room_id (context_id),
  KEY creator_id (creator_id),
  KEY modificator (modifier_id)
) ENGINE=MyISAM;';

    /*
     * Translation Object
     */
    private $_translator = null;

    /** constructor: cs_material_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'materials';
        $this->_translator = $environment->getTranslationObject();
    }

    /** reset data
     * reset data of this class: reset list of items and id_array.
     */
    public function resetData()
    {
        parent::resetData();
        $this->_id_array = [];
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit, type limit, id-array limit, dossier limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_public_limit = null;
        $this->_age_limit = null;
        $this->_group_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_institution_limit = null;
        $this->_topics_limit = null;
        $this->_ref_id_limit = null;
        $this->_ref_user_limit = null;
        $this->_order = null;
        $this->_limit_only_files_mode = null;
        $this->reset_id_limit();
        $this->_handle_tmp_manual = false;
    }

    /** reset id-array limit
     * reset the limit of this class: id-array limit.
     */
    public function reset_id_limit()
    {
        $this->_id_limit = null;
    }

    /** set age limit
     * this method sets an age limit for material.
     *
     * @param int limit age limit for material
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set public limit
     * this method sets an public limit for material.
     *
     * @param int limit public limit for material
     *
     * @author CommSy Development Group
     */
    public function setPublicLimit($value)
    {
        $this->_public_limit = (int) $value;
    }

    /** set id-array limit
     * this method sets an id-array limit for material.
     *
     * @param array limit id-array limit for material
     *
     * @author CommSy Development Group
     */
    public function setIDLimit($limit)
    {
        $this->_id_limit = (array) $limit;
    }

    /** set type limit
     * this method sets a type limit for material
     * This function should be deleted it's of no use anymore ...
     *
     * @param string limit type limit for material
     *
     * @author CommSy Development Group
     */
    public function setTypLimit($limit)
    {
    }

    /** set Announcements limit
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

    /** set dossier limit
     * this method sets a dossier limit for material.
     *
     * @param string limit dossier limit for material
     *
     * @author CommSy Development Group
     */
    public function setDossierLimit()
    {
        $this->_dossier_limit = 'dossier';
    }

    /** set group limit
     * this method sets a group limit for material.
     *
     * @param int limit id of the group
     *
     * @author CommSy Development Group
     */
    public function setGroupLimit($limit)
    {
        $this->_group_limit = (int) $limit;
    }

    public function setTopicLimit($limit)
    {
        $this->_topics_limit = (int) $limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected material
     * @param int interval interval limit for selected material
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected material
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    /** Returns the material item of the given item ID in its newest version.
     *
     * @param int|null itemId ID of the item
     */
    public function getItem(?int $itemId): ?cs_material_item
    {
        if (empty($itemId)) {
            return null;
        } elseif (!empty($this->_cache_object[$itemId])) {
            return $this->_cache_object[$itemId];
        } elseif (array_key_exists($itemId, $this->_cached_items)) {
            return $this->_buildItem($this->_cached_items[$itemId]);
        }

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('m.*', 'i.pinned')
            ->from($this->addDatabasePrefix($this->_db_table), 'm')
            ->innerJoin('m', 'items', 'i', 'i.item_id = m.item_id')
            ->where('m.item_id = :itemId');

        if (true == $this->_delete_limit) {
            $queryBuilder->andWhere('m.deleter_id IS NULL');
        }

        $queryBuilder
            ->orderBy('m.version_id', 'DESC')
            ->setParameter('itemId', $itemId);

        try {
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems selecting materials item (' . $itemId . '): ' . $e->getMessage(), E_USER_WARNING);
        }

        $material = null;
        if (!empty($result[0])) {
            $material = $this->_buildItem($result[0]);
            if ($this->_cache_on) {
                $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
        }

        return $material;
    }

    /** get a list of items (newest version)
     * this method returns a list of items.
     *
     * @param array id_array ids of the items items
     *
     * @return cs_list list of cs_items
     */
    public function getItemList(array $id_array): cs_list
    {
        $list = null;
        if (empty($id_array)) {
            return new cs_list();
        } else {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').".item_id IN ('".implode("', '", encode(AS_DB, $id_array))."')";
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.item_id, '.$this->addDatabasePrefix('materials').'.version_id DESC';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of '.$this->_type.' items from query: "'.$query.'"', E_USER_WARNING);
            } else {
                $list = new cs_list();
                // filter items with highest version_id, doing this in MySQL would be too expensive
                $last_item_id = 0;
                foreach ($result as $rs) {
                    if ($last_item_id != $rs['item_id']) {
                        $last_item_id = $rs['item_id'];
                        $list->add($this->_buildItem($rs));
                    }
                }
            }

            return $list;
        }
    }

    /**
     * documentation TBD.
     */
    public function getItemByVersion($item_id, $version_id)
    {
        $material = null;
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').".item_id = '".encode(AS_DB, $item_id)."'";
        $query .= ' AND '.$this->addDatabasePrefix('materials').".version_id = '".encode(AS_DB, $version_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
            trigger_error('Problems selecting one materials item from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $material = $this->_buildItem($result[0]);
        }

        return $material;
    }

    /** select all versions of a material
     * this method returns a list (cs_list) of materials in specific versions.
     *
     * @param int material_id item-id of material
     *
     * @return cs_list version_list of versions of the material
     */
    public function getVersionList($material_id)
    {
        $version_list = new cs_list();
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('materials');
        $query .= ' WHERE '.$this->addDatabasePrefix('materials').'.item_id="'.encode(AS_DB, $material_id).'"';
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.deleter_id IS NULL';
        }
        $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.version_id DESC';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting versions of a material from query: "'.$query.'"', E_USER_WARNING);
        } else {
            foreach ($result as $query_result) {
                $material_item = $this->_buildItem($query_result);
                $version_list->add($material_item);
            }
        }

        return $version_list;
    }

    public function _performQuery($mode = 'select')
    {
        return $this->_performQuery2($mode);
    }

    /** perform query for material: select and count
     * this method perform query for selecting and counting materials.
     *
     * @param bool count true: count materials
     *                      false: select materials
     *
     * @return int num of materials if count = true
     */
    public function _performQuery2($mode = 'select')
    {
        $this->_data = new cs_list();
        $current_time = getCurrentDateTimeInMySQL();
        $randum_number = random_int(0, 999999);
        $uid = 'cron_job';
        $temp_number = '';
        for ($i = 0; $i < mb_strlen($current_time); ++$i) {
            $temp_number .= mb_substr($current_time, $i, 1).mb_substr($uid, $i, 1).mb_substr($randum_number, $i, 1);
        }
        $temp_number = md5($temp_number);
        $cancel = false;
        if (!$this->_handle_tmp_manual) {
            $query = 'CREATE TEMPORARY TABLE tmp3'.$temp_number.' (item_id INT(11) NOT NULL, version_id INT(11) NOT NULL, PRIMARY KEY (item_id, version_id));';
            $this->_db_connector->performQuery($query);

            $query = 'INSERT INTO tmp3'.$temp_number.' (item_id,version_id) SELECT item_id,MAX(version_id) FROM '.$this->addDatabasePrefix('materials');

            if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
                $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id IN ('.implode(', ', $this->_room_array_limit).')';
            } elseif (isset($this->_room_limit)) {
                $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
            } else {
                $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentContextID()).'"';
            }

            $query .= ' GROUP BY item_id;';
            $this->_db_connector->performQuery($query);
        }
        $query = '';

        if (isset($this->_limit_only_files_mode)) {
            $query = 'INSERT INTO f'.$temp_number.' ';
        }

        if ('count' == $mode) {
            $query .= 'SELECT count(DISTINCT '.$this->addDatabasePrefix('materials').'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.item_id';
        } elseif ('distinct' == $mode) {
            $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.*';
        } else {
            $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.*';
        }

        if (isset($this->_order) && ('assessment' == $this->_order || 'assessment_rev' == $this->_order)) {
            $query .= ', AVG(assessments.assessment) AS assessments_avg';
        }

        $query .= ' FROM '.$this->addDatabasePrefix('materials');
        $query .= ' INNER JOIN tmp3'.$temp_number.' ON '.$this->addDatabasePrefix('materials').'.item_id=tmp3'.$temp_number.'.item_id AND '.$this->addDatabasePrefix('materials').'.version_id=tmp3'.$temp_number.'.version_id';
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id = '.$this->addDatabasePrefix('materials').'.item_id AND '.$this->addDatabasePrefix('items').'.draft != "1"';

        if (isset($this->_topics_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l21 ON ( l21.deletion_date IS NULL AND ((l21.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l21.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l22 ON ( l22.deletion_date IS NULL AND ((l22.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l22.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
        }
        if (isset($this->_group_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
        }

        if (isset($this->_tag_limit)) {
            $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'") ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'") ';
        }

        // restrict materials by buzzword (la4)
        if (isset($this->_buzzword_limit)) {
            if (-1 == $this->_buzzword_limit) {
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS l5 ON l5.from_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.from_version_id='.$this->addDatabasePrefix('materials').'.version_id AND l5.link_type="buzzword_for"';
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l5.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
            } else {
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS l5 ON l5.from_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.from_version_id='.$this->addDatabasePrefix('materials').'.version_id AND l5.link_type="buzzword_for"';
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON l5.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
            }
        }

        // restrict material by ref item
        if (isset($this->_ref_id_limit)) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l5 ON ( (l5.first_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.second_item_id="'.encode(AS_DB, $this->_ref_id_limit).'")
                     OR (l5.second_item_id='.$this->addDatabasePrefix('materials').'.item_id AND l5.first_item_id="'.encode(AS_DB, $this->_ref_id_limit).'") ) AND l5.deletion_date IS NULL';
        }

        if (isset($this->_order) and
             ('modificator' == $this->_order || 'modificator_rev' == $this->_order || 'creator' == $this->_order || 'creator_rev' == $this->_order)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS creator ON (creator.item_id='.$this->addDatabasePrefix('materials').'.creator_id )';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS modificator ON (modificator.item_id='.$this->addDatabasePrefix('materials').'.modifier_id )';
        } elseif (isset($this->_order) && ('assessment' == $this->_order || 'assessment_rev' == $this->_order)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('assessments').' ON '.$this->addDatabasePrefix('materials').'.item_id=assessments.item_link_id AND assessments.deletion_date IS NULL';
        }

        // only files limit -> entries with files (material)
        if (isset($this->_limit_only_files_mode)
             and 'item' == $this->_limit_only_files_mode) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf2 ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf2.item_iid';
        }

        // only files limit -> entries with files (sections)
        elseif (isset($this->_limit_only_files_mode)
             and 'subitem' == $this->_limit_only_files_mode) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf1 ON '.$this->addDatabasePrefix('section').'.item_id = lf1.item_iid';
        }

        // only files limit -> entries with files (sections and material)
        elseif (isset($this->_limit_only_files_mode)
             and 'both' == $this->_limit_only_files_mode) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf2 ON '.$this->addDatabasePrefix($this->_db_table).'.item_id = lf2.item_iid';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('item_link_file').' AS lf1 ON '.$this->addDatabasePrefix('section').'.item_id = lf1.item_iid';
        }

        $query .= ' WHERE 1';

        if (isset($this->_room_array_limit) and !empty($this->_room_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.context_id IN ('.implode(', ', $this->_room_array_limit).')';
        } elseif (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }

        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.deletion_date IS NULL';
        }

        switch ($this->inactiveEntriesLimit) {
            case self::SHOW_ENTRIES_ONLY_ACTIVATED:
                $query .= ' AND ('.$this->addDatabasePrefix('materials').'.activation_date  IS NULL OR '.$this->addDatabasePrefix('materials').'.activation_date  <= "'.getCurrentDateTimeInMySQL().'")';
                break;
            case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
                $query .= ' AND ('.$this->addDatabasePrefix('materials').'.activation_date  IS NOT NULL AND '.$this->addDatabasePrefix('materials').'.activation_date  > "'.getCurrentDateTimeInMySQL().'")';
                break;
        }

        if (isset($this->_ref_user_limit)) {
            $query .= ' AND ('.$this->addDatabasePrefix('materials').'.creator_id = "'.encode(AS_DB, $this->_ref_user_limit).'" )';
        }
        if (isset($this->_public_limit)) {
            if (6 == $this->_public_limit) {
                $query .= ' AND ('.$this->addDatabasePrefix('materials').'.world_public >= "1" )';
            } else {
                $query .= ' AND ('.$this->addDatabasePrefix('materials').'.world_public = "'.encode(AS_DB, $this->_public_limit).'" )';
            }
        }
        if (isset($this->_topics_limit)) {
            if (-1 == $this->_topics_limit) {
                $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
                $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB, $this->_topics_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_topics_limit).'")';
                $query .= ' OR (l22.first_item_id = "'.encode(AS_DB, $this->_topics_limit).'" OR l22.second_item_id = "'.encode(AS_DB, $this->_topics_limit).'"))';
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

        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }

        if (isset($this->_tag_limit)) {
            $query .= ' AND l41.deletion_date IS NULL ';
            $query .= ' AND l42.deletion_date IS NULL ';

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
        if (isset($this->_buzzword_limit)) {
            if (-1 == $this->_buzzword_limit) {
                $query .= ' AND (l5.to_item_id IS NULL OR l5.deletion_date IS NOT NULL)';
            } else {
                $query .= ' AND buzzwords.item_id="'.encode(AS_DB, $this->_buzzword_limit).'"';
            }
        }
        if (isset($this->_id_limit)) {
            $id_string = implode(', ', $this->_id_limit);
            $query .= ' AND '.$this->addDatabasePrefix('materials').'.item_id IN ('.encode(AS_DB, $id_string).')';
        }

        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }

        // only entries with files
        if (isset($this->_limit_not_item_id_array)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(',', encode(AS_DB, $this->_limit_not_item_id_array)).')';
        }

        // only files limit -> entries with files (material)
        if (isset($this->_limit_only_files_mode)
             and 'item' == $this->_limit_only_files_mode) {
            $query .= ' AND lf2.deleter_id IS NULL AND lf2.deletion_date IS NULL';
        }

        // only files limit -> entries with files (sections)
        elseif (isset($this->_limit_only_files_mode)
             and 'subitem' == $this->_limit_only_files_mode) {
            $query .= ' AND lf1.deleter_id IS NULL AND lf1.deletion_date IS NULL';
        }

        // only files limit -> entries with files (sections and material)
        elseif (isset($this->_limit_only_files_mode)
             and 'both' == $this->_limit_only_files_mode) {
            $query .= ' AND lf2.deleter_id IS NULL AND lf2.deletion_date IS NULL';
            $query .= ' AND lf1.deleter_id IS NULL AND lf1.deletion_date IS NULL';
        }

        if ($this->modificationNewerThenLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
        }

        if ($this->excludedIdsLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
        }

        if (isset($this->_order) && ('assessment' == $this->_order || 'assessment_rev' == $this->_order)) {
            $query .= ' GROUP BY '.$this->addDatabasePrefix('materials').'.item_id';
        }

        if (isset($this->_order)) {
            if ('date_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date ASC, '.$this->addDatabasePrefix('materials').'.title DESC';
            } elseif ('publishing_date' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.publishing_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC';
            } elseif ('publishing_date_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.publishing_date ASC, '.$this->addDatabasePrefix('materials').'.title DESC';
            } elseif ('author' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.author ASC, '.$this->addDatabasePrefix('materials').'.title ASC';
            } elseif ('author_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.author DESC, '.$this->addDatabasePrefix('materials').'.title DESC';
            } elseif ('creator' == $this->_order) {
                $query .= ' ORDER BY creator.lastname';
            } elseif ('creator_rev' == $this->_order) {
                $query .= ' ORDER BY creator.lastname DESC';
            } elseif ('modificator' == $this->_order) {
                $query .= ' ORDER BY modificator.lastname';
            } elseif ('modificator_rev' == $this->_order) {
                $query .= ' ORDER BY modificator.lastname DESC';
            } elseif ('assessment' == $this->_order) {
                $query .= ' ORDER BY assessments_avg DESC';
            } elseif ('assessment_rev' == $this->_order) {
                $query .= ' ORDER BY assessments_avg ASC';
            } elseif ('title' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.title ASC, '.$this->addDatabasePrefix('materials').'.modification_date DESC';
            } elseif ('title_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.title DESC, '.$this->addDatabasePrefix('materials').'.modification_date ASC';
            } elseif ('workflow_status' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.workflow_status ASC, '.$this->addDatabasePrefix('materials').'.modification_date DESC';
            } elseif ('workflow_status_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.workflow_status DESC, '.$this->addDatabasePrefix('materials').'.modification_date ASC';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC'; // default: sort by date
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC'; // default: sort by date
        }
        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // perform query
        if (!$cancel or 'select' != $mode) {
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                if ('count' == $mode) {
                    trigger_error('Problems counting material from query: "'.$query.'"', E_USER_WARNING);
                } else {
                    trigger_error('Problems selecting material from query: "'.$query.'"', E_USER_WARNING);
                }
            }
            if (!$this->_handle_tmp_manual) {
                $query = 'DROP TABLE tmp3'.$temp_number.';';
                $this->_db_connector->performQuery($query);
            }
            if ($result) {
                return $result;
            }
        } // end of if (cancel)
    } // end of methode _performQuery

    /**
       get latest version id for a material item
     */
    public function getLatestVersionID($item_id)
    {
        $latest_version = null;
        $query = 'SELECT MAX('.$this->addDatabasePrefix('materials').'.version_id) AS version_id FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').".item_id = '".encode(AS_DB, $item_id)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or empty($result[0])) {
            trigger_error('Problems selecting one material item from query: "'.$query.'"', E_USER_WARNING);
        } else {
            $rs = $result[0];
            $latest_version = $rs['version_id'];
        }

        return $latest_version;
    }

    /** Prepares the db_array for the item.
     *
     * @param array $db_array Contains the data from the database
     */
    public function _buildItem(array $db_array)
    {
        if (isset($db_array['extras'])) {
            $db_array['extras'] = unserialize($db_array['extras']);
        }

        return parent::_buildItem($db_array);
    }

    /** build a new material item
     * this method returns a new EMTPY material item.
     *
     * @return object cs_item a new EMPTY material
     */
    public function getNewItem()
    {
        return new cs_material_item($this->_environment);
    }

     /** update a material - internal, do not use -> use method save
      * this method updates a material.
      *
      * @param object cs_item material_item the material
      */
     public function _update($material_item)
     {
         /* @var cs_material_item $material_item */
         parent::_update($material_item);
         $modificator = $material_item->getModificatorItem();

         if (!isset($modificator)) {
             trigger_error('Problems creating new material: Modificator is not set', E_USER_ERROR);
         } else {
             $public = $material_item->isPublic() ? '1' : '0';
             $copy_id = null;
             $copy_item = $material_item->getCopyItem();
             if (isset($copy_item)) {
                 $copy_id = $copy_item->getItemID();
             } else {
                 $copy_id = '0';
             }
             if ($material_item->getWorldPublic()) {
                 $world_public = $material_item->getWorldPublic();
             } else {
                 $world_public = '0';
             }

             $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

             $workflowResubmissionDate = $material_item->getWorkflowResubmissionDate();
             $workflowResubmissionDate = empty($workflowResubmissionDate) ? null : $workflowResubmissionDate;

             $workflowValidityDate = $material_item->getWorkflowValidityDate();
             $workflowValidityDate = empty($workflowValidityDate) ? null : $workflowValidityDate;

             $queryBuilder
                 ->update($this->addDatabasePrefix('materials'))
                 ->set('modifier_id', ':modifierId')
                 ->set('modification_date', ':modificationDate')
                 ->set('activation_date', ':activationDate')
                 ->set('title', ':title')
                 ->set('description', ':description')
                 ->set('publishing_date', ':publishingDate')
                 ->set('author', ':author')
                 ->set('public', ':public')
                 ->set('world_public', ':worldPublic')
                 ->set('copy_of', ':copyOf')
                 ->set('extras', ':extras')
                 ->set('workflow_status', ':workflowStatus')
                 ->set('workflow_resubmission_date', ':workflowResubmissionDate')
                 ->set('workflow_validity_date', ':workflowValidityDate')
                 ->set('license_id', ':licenseId')
                 ->where('item_id = :itemId')
                 ->andWhere('version_id = :versionId')
                 ->setParameter('itemId', $material_item->getItemID())
                 ->setParameter('versionId', $material_item->getVersionID())
                 ->setParameter('modifierId', $modificator->getItemID())
                 ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
                 ->setParameter('activationDate', $material_item->isNotActivated() ? $material_item->getActivatingDate() : null)
                 ->setParameter('title', $material_item->getTitle())
                 ->setParameter('description', $material_item->getDescription())
                 ->setParameter('publishingDate', $material_item->getPublishingDate())
                 ->setParameter('author', $material_item->getAuthor())
                 ->setParameter('public', $public)
                 ->setParameter('worldPublic', $world_public)
                 ->setParameter('copyOf', $copy_id)
                 ->setParameter('extras', serialize($material_item->getExtraInformation()))
                 ->setParameter('workflowStatus', $material_item->getWorkflowTrafficLight())
                 ->setParameter('workflowResubmissionDate', $workflowResubmissionDate)
                 ->setParameter('workflowValidityDate', $workflowValidityDate)
                 ->setParameter('licenseId', $material_item->getLicenseId())
                 ->setParameter('itemId', $material_item->getItemID())
                 ->setParameter('versionId', $material_item->getVersionID())
             ;

             try {
                 $queryBuilder->executeStatement();
             } catch (\Doctrine\DBAL\Exception $e) {
                 trigger_error($e->getMessage(), E_USER_WARNING);
             }
         }
     }

     /**
      * create a material - internal, do not use -> use method save
      * this method creates a material.
      */
     public function _create(cs_material_item $material_item)
     {
         $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

         $queryBuilder
             ->insert($this->addDatabasePrefix('items'))
             ->setValue('context_id', ':contextId')
             ->setValue('modification_date', ':modificationDate')
             ->setValue('activation_date', ':activationDate')
             ->setValue('type', ':type')
             ->setValue('draft', ':draft')
             ->setParameter('contextId', $material_item->getContextID())
             ->setParameter('modificationDate', getCurrentDateTimeInMySQL())
             ->setParameter('activationDate',
                 $material_item->isNotActivated() ? $material_item->getActivatingDate() : null)
             ->setParameter('type', 'material')
             ->setParameter('draft', $material_item->isDraft());

         try {
             $queryBuilder->executeStatement();

             $this->_create_id = $queryBuilder->getConnection()->lastInsertId();
             $material_item->setItemID($this->getCreateID());
             $this->_newmaterial($material_item);
         } catch (\Doctrine\DBAL\Exception $e) {
             trigger_error($e->getMessage(), E_USER_WARNING);
             $this->_create_id = null;
         }
     }

     /** creates a new material - internal, do not use -> use method save
      * this method creates a new material.
      *
      * @param object cs_item material_item the material
      *
      * @throws \Doctrine\DBAL\Exception
      */
     public function _newmaterial($material_item)
     {
         /** @var cs_material_item $material_item */
         $user = $material_item->getCreatorItem();
         $modificator = $material_item->getModificatorItem();
         $context_id = $material_item->getContextID();
         if (!isset($user)) {
             trigger_error('Problems creating new material: Creator is not set', E_USER_ERROR);
         } elseif (!isset($modificator)) {
             trigger_error('Problems creating new material: Modificator is not set', E_USER_ERROR);
         } elseif (!isset($context_id)) {
             trigger_error('Problems creating new material: ContextID is not set', E_USER_ERROR);
         } else {
             $current_datetime = getCurrentDateTimeInMySQL();
             $copy_id = null;
             $copy_item = $material_item->getCopyItem();
             if (isset($copy_item)) {
                 $copy_id = $copy_item->getItemID();
             } else {
                 $copy_id = '0';
             }
             $public = $material_item->isPublic() ? '1' : '0';
             if ($material_item->getWorldPublic()) {
                 $world_public = $material_item->getWorldPublic();
             } else {
                 $world_public = '0';
             }
             $modification_date = getCurrentDateTimeInMySQL();

             $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

             $workflowResubmissionDate = $material_item->getWorkflowResubmissionDate();
             $workflowResubmissionDate = empty($workflowResubmissionDate) ? null : $workflowResubmissionDate;

             $workflowValidityDate = $material_item->getWorkflowValidityDate();
             $workflowValidityDate = empty($workflowValidityDate) ? null : $workflowValidityDate;

             $queryBuilder
                 ->insert($this->addDatabasePrefix('materials'))
                 ->setValue('item_id', ':itemId')
                 ->setValue('version_id', ':versionId')
                 ->setValue('context_id', ':contextId')
                 ->setValue('creator_id', ':creatorId')
                 ->setValue('creation_date', ':creationDate')
                 ->setValue('modifier_id', ':modifierId')
                 ->setValue('modification_date', ':modificationDate')
                 ->setValue('activation_date', ':activationDate')
                 ->setValue('title', ':title')
                 ->setValue('description', ':description')
                 ->setValue('publishing_date', ':publishingDate')
                 ->setValue('author', ':author')
                 ->setValue('public', ':public')
                 ->setValue('world_public', ':worldPublic')
                 ->setValue('copy_of', ':copyOf')
                 ->setValue('extras', ':extras')
                 ->setValue('workflow_status', ':workflowStatus')
                 ->setValue('workflow_resubmission_date', ':workflowResubmissionDate')
                 ->setValue('workflow_validity_date', ':workflowValidityDate')
                 ->setValue('license_id', ':licenseId')
                 ->setParameter('itemId', $material_item->getItemID())
                 ->setParameter('versionId', $material_item->getVersionID())
                 ->setParameter('contextId', $context_id)
                 ->setParameter('creatorId', $user->getItemID())
                 ->setParameter('creationDate', $current_datetime)
                 ->setParameter('modifierId', $modificator->getItemID())
                 ->setParameter('modificationDate', $modification_date)
                 ->setParameter('activationDate', $material_item->isNotActivated() ? $material_item->getActivatingDate() : null)
                 ->setParameter('title', $material_item->getTitle())
                 ->setParameter('description', $material_item->getDescription())
                 ->setParameter('publishingDate', $material_item->getPublishingDate())
                 ->setParameter('author', $material_item->getAuthor())
                 ->setParameter('public', $public)
                 ->setParameter('worldPublic', $world_public)
                 ->setParameter('copyOf', $copy_id)
                 ->setParameter('extras', serialize($material_item->getExtraInformation()))
                 ->setParameter('workflowStatus', $material_item->getWorkflowTrafficLight())
                 ->setParameter('workflowResubmissionDate', $workflowResubmissionDate)
                 ->setParameter('workflowValidityDate', $workflowValidityDate)
                 ->setParameter('licenseId', $material_item->getLicenseId())
             ;

             try {
                 $queryBuilder->executeStatement();
             } catch (\Doctrine\DBAL\Exception $e) {
                 trigger_error($e->getMessage(), E_USER_WARNING);
             }
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
      if (!empty($item_id)) {
          if ($item->_version_id_changed) {
              $this->_newmaterial($item);
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

      // Add modifier to all users who ever edited this section
      $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
      $link_modifier_item_manager->markEdited($item->getItemID());
      unset($item);
      unset($link_modifier_item_manager);
  }

  /** save a new version of a material.
   *
   * @param object cs_item material_item the material
   */
  public function _save_version($material_item)
  {
      $context_id = $material_item->getContextID();
      if (isset($context_id) and ($context_id != $this->_environment->getCurrentContextID())) {
          trigger_error('Context ID is not equal: ', E_USER_WARNING);
      }
      $this->_newmaterial($material_item);
      unset($material_item);
  }

  /**
   * documentation TBD.
   */
  public function delete(int $itemId, $version_id = null): void
  {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID() ?: 0;
      if (!isset($current_user)) {
          trigger_error('Problems deleting material: Deleter is not set', E_USER_ERROR);
      } else {
          $query = 'UPDATE '.$this->addDatabasePrefix('materials').' SET '.
                   'deletion_date="'.$current_datetime.'",'.
                   'deleter_id="'.encode(AS_DB, $user_id).'"'.
                   ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
          if ($version_id) {
              $query .= ' AND version_id="'.encode(AS_DB, $version_id).'"';
          }
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result) or !$result) {
              trigger_error('Problems deleting material: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_WARNING);
          } else {
              if (is_null($version_id)) {
                  parent::delete($itemId);
              }
          }
      }
  }

    /**
     * checks if label type is supported in the current context
     * so far only groups are checked within contexts, since they can be "switched off".
     *
     * @return bool TRUE if supported, FALSE otherwise
     */
    public function _isAvailable()
    {
        // check if materials are available in the context
        trigger_error('n i y', E_USER_ERROR);
        if ($this->_environment->inProjectRoom()) {
            if (!empty($this->_room_limit)) {
                $room_manager = $this->_environment->getProjectManager();
                $room_item = $room_manager->getItem($this->_room_limit);
                unset($room_manager);
            } else {
                $room_item = $this->_environment->getCurrentRoomItem();
            }

            return $room_item->withRubric(CS_MATERIAL_TYPE);
        } else {
            return true;
        }
    }

    public function mergeAccount($new_id, $old_id)
    {
        parent::mergeAccounts($new_id, $old_id);
        $query = 'UPDATE '.$this->addDatabasePrefix('material_link_file').' SET deleter_id = "'.encode(AS_DB, $new_id).'" WHERE deleter_id = "'.encode(AS_DB, $old_id).'";';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems creating material_link_file from query: "'.$query.'"', E_USER_WARNING);
        }
    }

    // #######################################################
    // statistic functions
    // #######################################################

     public function deleteMaterialsOfUser($uid)
     {
         global $symfonyContainer;
         $disableOverwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');

         if (null !== $disableOverwrite && 'TRUE' !== $disableOverwrite) {
             $currentDatetime = getCurrentDateTimeInMySQL();
             $query = 'SELECT '.$this->addDatabasePrefix('materials').'.* FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').'.creator_id = "'.encode(AS_DB, $uid).'"';
             $result = $this->_db_connector->performQuery($query);

             if (!empty($result)) {
                 foreach ($result as $rs) {
                     $updateQuery = 'UPDATE '.$this->addDatabasePrefix('materials').' SET';

                     /* flag */
                     if ('FLAG' === $disableOverwrite) {
                         $updateQuery .= ' public = "-1",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'"';
                     }

                     /* disabled */
                     if ('FALSE' === $disableOverwrite) {
                         $updateQuery .= ' title = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE')).'",';
                         $updateQuery .= ' description = "'.encode(AS_DB, $this->_translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION')).'",';
                         $updateQuery .= ' modification_date = "'.$currentDatetime.'",';
                         $updateQuery .= ' author = "",';
                         $updateQuery .= ' publishing_date = "",';
                         $updateQuery .= ' extras = "",';
                         $updateQuery .= ' public = "1"';
                     }

                     $updateQuery .= ' WHERE item_id = "'.encode(AS_DB, $rs['item_id']).'"';
                     $result2 = $this->_db_connector->performQuery($updateQuery);
                     if (!$result2) {
                         trigger_error('Problems automatic deleting materials from query: "'.$updateQuery.'"', E_USER_WARNING);
                     }
                 }
             }
         }
     }

     public function getResubmissionItemIDsByDate($year, $month, $day)
     {
         $query = 'SELECT item_id, version_id FROM '.$this->addDatabasePrefix('materials').' WHERE workflow_resubmission_date = "'.$year.'-'.$month.'-'.$day.'" AND deletion_date IS NULL';

         return $this->_db_connector->performQuery($query);
     }

     public function setWorkflowStatus($item_id, $status, $version_id)
     {
         $query = 'UPDATE '.$this->addDatabasePrefix('materials').' SET workflow_status = "'.$status.'" WHERE item_id = '.$item_id.' AND version_id = '.$version_id;

         return $this->_db_connector->performQuery($query);
     }

    public function getValidityItemIDsByDate($year, $month, $day)
    {
        $query = 'SELECT item_id, version_id FROM '.$this->addDatabasePrefix('materials').' WHERE workflow_validity_date = "'.$year.'-'.$month.'-'.$day.'" AND deletion_date IS NULL';

        return $this->_db_connector->performQuery($query);
    }

 /**
  * Resets license links to null.
  *
  * @return mixed
  */
 public function unsetLicenses(App\Entity\License $license)
 {
     $query = '
            UPDATE
                '.$this->addDatabasePrefix('materials').'
            SET
                license_id = NULL
            WHERE
                license_id = '.encode(AS_DB, $license->getId()).'
        ';

     return $this->_db_connector->performQuery($query);
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

         $this->setOrder('date');

         $this->select();

         return $this->get();
     }
}
