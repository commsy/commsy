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

/**
 * implements a database manager for items in table "room" with type "userroom".
 *
 * a user room gets used inside project rooms for bilateral exchange between a single user and the room's moderators
 */
class cs_userroom_manager extends cs_room2_manager
{
    // TODO: remove any unused limits

    /**
     * integer - containing the age of project as a limit.
     */
    public $_age_limit = null;

    /**
     * string - enthält die USERID eines Benutzers.
     */
    public $_user_id_limit = null;

    public $_time_limit = null;

    public $_template_limit = null;

    private ?int $_project_room_limit = null;

    /**
     * constructor.
     *
     * @param object cs_environment the environment
     */
    public function __construct(cs_environment $environment)
    {
        $this->_db_table = CS_ROOM_TYPE;
        $this->_room_type = cs_userroom_item::ROOM_TYPE_USER;
        cs_context_manager::__construct($environment);
    }

    /** reset limits
     * reset limits of this class: local limits and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_age_limit = null;
        $this->_user_id_limit = null;
        $this->_time_limit = null;
        $this->_template_limit = null;
        $this->_project_room_limit = null;
    }

    /** set age limit
     * this method sets an age limit for user room.
     *
     * @param int limit age limit for user room
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set user id limit.
     *
     * @param string limit userid limit for selected user rooms
     */
    public function setUserIDLimit($limit)
    {
        $this->_user_id_limit = (string) $limit;
    }

    public function setAuthSourceLimit($limit)
    {
        $this->_auth_source_limit = (int) $limit;
    }

    public function setGetAllRoomLimit()
    {
        $this->_all_room_limit = true;
    }

    public function setProjectRoomLimit($limit)
    {
        $this->_project_room_limit = (int) $limit;
    }

    public function unsetRoomLimit()
    {
        $this->_room_limit = null;
    }

    /** set time limit
     * this method sets an clock pulses limit for rooms.
     *
     * @param int limit time limit for rooms (item id of clock pulses)
     */
    public function setTimeLimit($limit)
    {
        $this->_time_limit = $limit;
    }

    public function setTemplateLimit()
    {
        $this->_template_limit = 1;
    }

    public function setNotTemplateLimit()
    {
        $this->_template_limit = -1;
    }

    public function unsetTemplateLimit()
    {
        $this->_template_limit = null;
    }

    /**
     * Returns all user rooms containing a user who represents the given user in that room.
     *
     * @param cs_user_item $userItem the user item for which related user rooms shall be returned
     *
     * @return cs_list list of user room items connected to the given user item
     */
    public function getRelatedUserroomListForUser(cs_user_item $userItem): cs_list
    {
        return $this->getRelatedContextListForUserInt($userItem->getUserID(), $userItem->getAuthSource(), $this->_environment->getCurrentPortalID());
    }

    /** select user rooms limited by limits
     * this method returns a list (cs_list) of user rooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function _performQuery($mode = 'select')
    {
        $query = 'SELECT DISTINCT';
        if ('count' == $mode) {
            $query .= ' count(DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
        } elseif ('id_array' == $mode) {
            $query .= ' '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        } else {
            $query .= ' '.$this->addDatabasePrefix($this->_db_table).'.*';
        }
        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table).'';

        // user id limit
        if (isset($this->_user_id_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            if (!$this->_all_room_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            }
        }
        if (isset($this->_topic_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l41.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l42.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
        }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if (-1 != $this->_time_limit) {
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
                $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS time_label ON room_time.to_item_id=time_label.item_id AND time_label.type="time"';
            } else {
                $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
            }
        }

        $query .= ' WHERE 1';
        if (isset($this->_room_type)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB, $this->_room_type).'"';
        }
        if (isset($this->_topic_limit)) {
            if (-1 == $this->_topic_limit) {
                $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
                $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l41.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l41.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'")';
                $query .= ' OR (l42.first_item_id = "'.encode(AS_DB, $this->_topic_limit).'" OR l42.second_item_id = "'.encode(AS_DB, $this->_topic_limit).'"))';
            }
        }

        // insert limits into the select statement
        if (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }

        if (isset($this->_status_limit)) {
            if (5 != $this->_status_limit) {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB, $this->_status_limit).'"';
            } elseif (5 == $this->_status_limit) {
                $query .= ' AND ( '.$this->addDatabasePrefix($this->_db_table).'.status = "1" OR '.$this->addDatabasePrefix($this->_db_table).'.status = "2")';
            }
        }

        if (!empty($this->_user_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB, $this->_user_id_limit).'"';
        }
        if (!empty($this->_auth_source_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB, $this->_auth_source_limit).'"';
        }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if (-1 != $this->_time_limit) {
                $query .= ' AND time_label.item_id = "'.encode(AS_DB, $this->_time_limit).'"';
            } else {
                $query .= ' AND room_time.to_item_id IS NULL';
            }
        }

        // template
        if (isset($this->_template_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.template = "'.encode(AS_DB, $this->_template_limit).'"';
        }

        // id_array_limit
        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }

        // project room limit
        if (isset($this->_project_room_limit) and !empty($this->_project_room_limit)) {
            // Fixed: There were no PROJECT_ROOM_ITEM_ID - Tags in extras column
            // $query .= ' AND extras LIKE "%<PROJECT_ROOM_ITEM_ID>'.encode(AS_DB,$this->_project_room_limit).'</PROJECT_ROOM_ITEM_ID>%"';
            $query .= ' AND extras LIKE "%s:20:\"PROJECT_ROOM_ITEM_ID\";i:'.encode(AS_DB, $this->_project_room_limit).';%"';
        }

        // archive
        // lastlogin_limit
        if (!empty($this->_lastlogin_limit)) {
            if ('NULL' == $this->_lastlogin_limit) {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin = '.encode(AS_DB, $this->_lastlogin_limit);
            }
        }

        // lastlogin_newer_limit
        if (!empty($this->_lastlogin_newer_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin >= "'.encode(AS_DB, $this->_lastlogin_newer_limit).'"';
        }

        if (isset($this->_sort_order)) {
            if ('title_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
            } elseif ('activity' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC,'.$this->addDatabasePrefix($this->_db_table).'.title';
            } elseif ('activity_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC,'.$this->addDatabasePrefix($this->_db_table).'.title';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            }
        } elseif (isset($this->_order)) {
            if ('date' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('creator' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
            } elseif ('status' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.status, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('activity' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('activity_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC,'.$this->addDatabasePrefix($this->_db_table).'.title';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);

        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"', E_USER_WARNING);
        } else {
            return $result;
        }
    }

    public function getSortedItemList($id_array, $sortBy)
    {
        $list = null;
        if (empty($id_array)) {
            return new cs_list();
        } else {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ("'.implode('", "', encode(AS_DB, $id_array)).'") AND '.$this->addDatabasePrefix($this->_db_table).'.type LIKE "'.encode(AS_DB, $this->_room_type).'"';
            $query .= ' ORDER BY '.$sortBy;
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of '.$this->_room_type.' items from query: "'.$query.'"', E_USER_WARNING);
            } else {
                $list = new cs_list();
                foreach ($result as $rs) {
                    $list->add($this->_buildItem($rs));
                }
            }

            return $list;
        }
    }

    public function getItemList(array $id_array): cs_list
    {
        return $this->_getItemList(CS_ROOM_TYPE, $id_array);
    }

    /** save a user room item.
     *
     * @param cs_userroom_item
     */
    public function saveItem($item)
    {
        $itemId = $item->getItemID();
        if (!empty($itemId)) {
            $this->_update($item);
        } else {
            $this->_create($item);
        }
        unset($item);
    }

    /** create a project - internal, do not use -> use method save
     * this method creates a project.
     *
     * @param object cs_item project_item the project
     */
    public function _create($item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
            'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
            'modification_date="'.getCurrentDateTimeInMySQL().'",'.
            'type="'.encode(AS_DB, $this->_room_type).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating '.$this->_db_table.' item from query: "'.$query.'"', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $item->setItemID($this->getCreateID());
            $this->_new($item);
        }
        unset($item);
    }
}
