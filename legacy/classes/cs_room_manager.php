<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** upper class of the room manager
 */
include_once('classes/cs_context_manager.php');

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community"
 */
class cs_room_manager extends cs_context_manager
{

    /**
     * integer - containing a start point for the select community
     */
    var $_from_limit = NULL;

    /**
     * integer - containing how many communities the select statement should get
     */
    var $_interval_limit = NULL;

    /**
     * string - containing USERID of an user
     */
    var $_user_id_limit = NULL;

    var $_all_room_limit = false;

    var $_time_limit = NULL;

    var $_continuous_limit = NULL;

    var $_template_limit = NULL;

    /**
     * string - containing an order limit for the select community
     */
    var $_order = NULL;

    var $_deleted_limit = NULL;

    private $_logarchive_limit = NULL;

    private $_limit_with_grouproom = false;

    private $_archive_limit = false;

    private $_limit_only_grouproom = false;

    /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param object cs_environment the environment
     */
    function __construct($environment)
    {
        cs_context_manager::__construct($environment);
        $this->_db_table = CS_ROOM_TYPE;
        $this->_room_type = '';
    }

    /** reset limits
     * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
     */
    function resetLimits()
    {
        parent::resetLimits();
        $this->_from_limit = NULL;
        $this->_interval_limit = NULL;
        $this->_user_id_limit = NULL;
        $this->_all_room_limit = false;
        $this->_order = NULL;
        $this->_deleted_limit = NULL;
        $this->_time_limit = NULL;
        $this->_continuous_limit = NULL;
        $this->_template_limit = NULL;
        $this->_logarchive_limit = NULL;
        $this->_limit_with_grouproom = false;
        $this->_limit_only_grouproom = false;
        $this->_archive_limit = false;
    }

    public function setWithGrouproom()
    {
        $this->_limit_with_grouproom = true;
    }

    /**
     * Select only grouprooms
     */
    public function setOnlyGrouproom()
    {
        $this->_limit_only_grouproom = true;
    }

    /** set interval limit
     * this method sets a interval limit
     *
     * @param integer from     from limit for selected communities
     * @param integer interval interval limit for selected communities
     */
    function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (integer)$interval;
        $this->_from_limit = (integer)$from;
    }

    function setArchiveLimit()
    {
        $this->_archive_limit = true;
    }

    function setRoomTypeLimit($value)
    {
        $this->_room_type = $value;
    }

    /** set user id limit
     *
     * @param string limit userid limit for selected rooms
     */
    function setUserIDLimit($limit)
    {
        $this->_user_id_limit = (string)$limit;
    }

    function setAuthSourceLimit($limit)
    {
        $this->_auth_source_limit = (int)$limit;
    }

    function setGetAllRoomLimit()
    {
        $this->_all_room_limit = true;
    }

    function setDeletedLimit()
    {
        $this->_deleted_limit = true;
    }

    /** set time limit
     * this method sets an clock pulses limit for rooms
     *
     * @param integer limit time limit for rooms (item id of clock pulses)
     */
    function setTimeLimit($limit)
    {
        $this->_time_limit = $limit;
    }

    function setContinuousLimit()
    {
        $this->_continuous_limit = 1;
    }

    function setNotContinuousLimit()
    {
        $this->_continuous_limit = -1;
    }

    function unsetContinuousLimit()
    {
        $this->_continuous_limit = NULL;
    }

    function setTemplateLimit()
    {
        $this->_template_limit = 1;
    }

    function setNotTemplateLimit()
    {
        $this->_template_limit = -1;
    }

    function unsetTemplateLimit()
    {
        $this->_template_limit = NULL;
    }

    public function setLogArchiveLimit()
    {
        $this->_logarchive_limit = array();
        $this->_logarchive_limit[] = 'LOGARCHIVE";i:1';
        $this->_logarchive_limit[] = 'LOGARCHIVE";s:1';
    }

    /** set order limit
     * this method sets an order limit for the select statement
     *
     * @param string limit order limit for selected communities
     */
    function setOrder($limit)
    {
        $this->_order = (string)$limit;
    }

    /** select rooms limited by limits
     * this method returns a list (cs_list) of rooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information
     */
    function _performQuery($mode = 'select')
    {
        $query = '';
        if ($mode == 'count') {
            $query .= 'SELECT count(DISTINCT ' . $this->addDatabasePrefix($this->_db_table) . '.item_id) as count';
        } elseif ($mode == 'id_array') {
            $query .= 'SELECT DISTINCT ' . $this->addDatabasePrefix($this->_db_table) . '.item_id';
        } elseif (!$this->_sql_with_extra) {
            $query .= 'SELECT DISTINCT ' . $this->addDatabasePrefix($this->_db_table) . '.item_id';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.context_id';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.creator_id';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.modifier_id';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.deleter_id';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.creation_date';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.deletion_date';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.title';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.status';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.activity';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.type';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.public';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.is_open_for_guests';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.continuous';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.template';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.contact_persons';
            $query .= ', ' . $this->addDatabasePrefix($this->_db_table) . '.room_description';
        } else {
            $query .= 'SELECT DISTINCT ' . $this->addDatabasePrefix($this->_db_table) . '.*';
        }

        $query .= ' FROM ' . $this->addDatabasePrefix($this->_db_table);

        // user id limit
        if (isset($this->_user_id_limit)) {
            $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('user') . ' ON ' . $this->addDatabasePrefix('user') . '.context_id=' . $this->addDatabasePrefix($this->_db_table) . '.item_id AND ' . $this->addDatabasePrefix('user') . '.deletion_date IS NULL';
            if (!$this->_all_room_limit) {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.status >= "2"';
            }
        }

#     if (isset($this->_search_array) AND !empty($this->_search_array)) {
#        $query .= ' LEFT JOIN user AS user2 ON user2.context_id='.$this->_db_table.'.item_id';
#     }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if ($this->_time_limit != -1) {
                $query .= ' INNER JOIN ' . $this->addDatabasePrefix('links') . ' AS room_time ON room_time.from_item_id=' . $this->addDatabasePrefix($this->_db_table) . '.item_id AND room_time.link_type="in_time"';
                $query .= ' INNER JOIN ' . $this->addDatabasePrefix('labels') . ' AS time_label ON room_time.to_item_id=time_label.item_id AND time_label.type="time"';
            } else {
                $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('links') . ' AS room_time ON room_time.from_item_id=' . $this->addDatabasePrefix($this->_db_table) . '.item_id AND room_time.link_type="in_time"';
            }
        }

        $query .= ' WHERE 1';

#     if (isset($this->_search_array) AND !empty($this->_search_array)) {
#        $query .= ' AND user2.deletion_date IS NULL AND (user2.is_contact="1" OR user2.status="3")';
#     }

        if (!empty($this->_id_array_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.item_id IN (' . implode(',', $this->_id_array_limit) . ')';
        }

        if (!empty($this->_room_type)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.type = "' . encode(AS_DB, $this->_room_type) . '"';
        }

        ###################################
        # FLAG: group room
        ###################################
        if ((empty($this->_room_type) or $this->_room_type != CS_GROUPROOM_TYPE) && !$this->_limit_only_grouproom) {
            if (!isset($this->_logarchive_limit)
                and !isset($this->_id_array_limit)
                and !$this->_limit_with_grouproom
            ) {
                $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.type != "' . CS_GROUPROOM_TYPE . '"';
            }
        } else if ($this->_limit_only_grouproom) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.type = "' . CS_GROUPROOM_TYPE . '"';
        }
        ###################################
        # FLAG: group room
        ###################################

        // insert limits into the select statement
        if (isset($this->_deleted_limit) and $this->_deleted_limit) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.deleter_id IS NOT NULL AND ' . $this->addDatabasePrefix($this->_db_table) . '.deletion_date IS NOT NULL';
        } elseif ($this->_delete_limit == true) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.deleter_id IS NULL AND ' . $this->addDatabasePrefix($this->_db_table) . '.deletion_date IS NULL';
        }
        if (isset($this->_status_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.status = "' . encode(AS_DB, $this->_status_limit) . '"';
        }

        if (isset($this->_room_limit)
            and !empty($this->_room_limit)
            and !isset($this->_id_array_limit)
        ) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.context_id = "' . encode(AS_DB, $this->_room_limit) . '"';
        }
        if (isset($this->_continuous_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.continuous = "' . encode(AS_DB, $this->_continuous_limit) . '"';
        }
        //search limit
        if (isset($this->_search_array) AND !empty($this->_search_array)) {
            $query .= ' AND (';

            if ($this->_existsField($this->_db_table, 'room_description')) {
                $field_array = array($this->addDatabasePrefix($this->_db_table) . '.title', $this->addDatabasePrefix($this->_db_table) . '.contact_persons', $this->addDatabasePrefix($this->_db_table) . '.room_description');
            } else {
                $field_array = array($this->addDatabasePrefix($this->_db_table) . '.title', $this->addDatabasePrefix($this->_db_table) . '.contact_persons', $this->addDatabasePrefix($this->_db_table) . '.description');
            }
            $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
            $query .= $search_limit_query_code;
            $query .= ')';
        }

        if (!empty($this->_user_id_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.user_id="' . encode(AS_DB, $this->_user_id_limit) . '"';
        }
        if (!empty($this->_auth_source_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.auth_source="' . encode(AS_DB, $this->_auth_source_limit) . '"';
        }

        // time (clock pulses)
        if (isset($this->_time_limit)) {
            if ($this->_time_limit != -1) {
                $query .= ' AND time_label.item_id = "' . encode(AS_DB, $this->_time_limit) . '"';
            } else {
                $query .= ' AND room_time.to_item_id IS NULL';
            }
        }

        // template
        if (isset($this->_template_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.template = "' . encode(AS_DB, $this->_template_limit) . '"';
        }
        if (!isset($this->_logarchive_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.type != "privateroom"';
        }

        // log archive
        if (!empty($this->_logarchive_limit)
            and count($this->_logarchive_limit) > 0
        ) {
            $query .= ' AND (';
            $first = true;
            foreach ($this->_logarchive_limit as $log_arg_limit) {
                if ($first) {
                    $first = false;
                } else {
                    $query .= ' OR ';
                }
                $query .= $this->addDatabasePrefix($this->_db_table) . '.extras LIKE "%' . encode(AS_DB, $log_arg_limit) . '%"';
            }
//         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.extras LIKE "%'.encode(AS_DB,$this->_logarchive_limit).'%"';
            $query .= ')';
        }

        if ($mode != 'count') {
            if (isset($this->_order)) {
                if ($this->_order == 'date') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date DESC, ' . $this->addDatabasePrefix($this->_db_table) . '.title ASC';
                } elseif ($this->_order == 'creation_date') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.creation_date ASC, ' . $this->addDatabasePrefix($this->_db_table) . '.title ASC';
                } elseif ($this->_order == 'activity') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.activity ASC, ' . $this->addDatabasePrefix($this->_db_table) . '.title ASC';
                } elseif ($this->_order == 'activity_rev') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.activity DESC, ' . $this->addDatabasePrefix($this->_db_table) . '.title ASC';
                } elseif ($this->_order == 'title') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.title ASC';
                } elseif ($this->_order == 'title_rev') {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.title DESC';
                } else {
                    $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.title, ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date DESC';
                }
            } else {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix($this->_db_table) . '.title, ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date DESC';
            }
        }

        if ($mode == 'select') {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT ' . encode(AS_DB, $this->_from_limit) . ', ' . encode(AS_DB, $this->_interval_limit);
            }
        }

        // archive limit zzz_tables
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');
        $db_prefix = $c_db_backup_prefix . '_';
        if (isset($this->_archive_limit)
            and $this->_archive_limit
            and !strstr($query, $db_prefix)
        ) {
            $query = str_replace(' ' . $this->addDatabasePrefix($this->_db_table), ' ' . $db_prefix . $this->addDatabasePrefix($this->_db_table), $query);
            $query = str_replace('(' . $this->addDatabasePrefix($this->_db_table), '(' . $db_prefix . $this->addDatabasePrefix($this->_db_table), $query);
            $query = str_replace('=' . $this->addDatabasePrefix($this->_db_table), '=' . $db_prefix . $this->addDatabasePrefix($this->_db_table), $query);

            // user
            $table = 'user';
            $query = str_replace(' ' . $this->addDatabasePrefix($table), ' ' . $db_prefix . $this->addDatabasePrefix($table), $query);
            $query = str_replace('(' . $this->addDatabasePrefix($table), '(' . $db_prefix . $this->addDatabasePrefix($table), $query);
            $query = str_replace('=' . $this->addDatabasePrefix($table), '=' . $db_prefix . $this->addDatabasePrefix($table), $query);
        }
        $this->_last_query = $query;

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting ' . $this->_db_table . ' items from query: "' . $query . '"', E_USER_ERROR);
        } else {
            if (isset($this->_archive_limit)
                and $this->_archive_limit
                and $mode == 'select'
            ) {
                $result2 = array();
                foreach ($result as $key => $row) {
                    $row['zzz_table'] = 1;
                    $result2[$key] = $row;
                }
                $result = $result2;
                unset($result2);
            }
            if (!empty($this->_id_array_limit)
                and $this->_order == 'id_array'
            ) {
                // sort result
                $result2 = array();
                foreach ($result as $value) {
                    $result2[$value['item_id']] = $value;
                }
                $result = array();
                foreach ($this->_id_array_limit as $item_id) {
                    if (isset($result2[$item_id])) {
                        $result[] = $result2[$item_id];
                    } else {
                        // separator
                        $temp_array = array();
                        $temp_array['item_id'] = -1;
                        $temp_array['title'] = '----------------------------';
                        $temp_array['type'] = CS_PROJECT_TYPE;
                        $result[] = $temp_array;
                        unset($temp_array);
                    }
                }
            } elseif (!empty($this->_id_array_limit)
                and $this->_cache_on
            ) {
                foreach ($result as $row) {
                    if (!empty($row)
                        and !empty($row['item_id'])
                        and empty($this->_cache_row[$row['item_id']])) {
                        $this->_cache_row[$row['item_id']] = $row;
                    }
                }
            }
            return $result;
        }
    }

    ##########################################################
    # statistic functions
    ##########################################################

    function getActiveRooms($start, $end)
    {
        $list = $this->getUsedRooms($start, $end);

        // delete rooms that are not really active
        $retour_list = new cs_list();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if ($item->isActive($start, $end)) {
                    $retour_list->add($item);
                }
                $item = $list->getNext();
            }
        }

        return $retour_list;
    }

    function getUsedRooms($start, $end)
    {
        $list = new cs_list();

        $query = "SELECT " . $this->addDatabasePrefix($this->_db_table) . ".* FROM " . $this->addDatabasePrefix($this->_db_table) . ", " . $this->addDatabasePrefix("user");
        $query .= " WHERE " . $this->addDatabasePrefix("user") . ".context_id=" . $this->addDatabasePrefix($this->_db_table) . ".item_id AND " . $this->addDatabasePrefix("user") . ".lastlogin > '" . encode(AS_DB, $start) . "' and " . $this->addDatabasePrefix("user") . ".creation_date < '" . encode(AS_DB, $end) . "'";
        $query .= " AND " . $this->addDatabasePrefix($this->_db_table) . ".context_id = '" . encode(AS_DB, $this->_room_limit) . "' AND " . $this->addDatabasePrefix($this->_db_table) . ".status != '4' and " . $this->addDatabasePrefix($this->_db_table) . ".deletion_date IS NULL and " . $this->addDatabasePrefix($this->_db_table) . ".creation_date < '" . encode(AS_DB, $end) . "' and (type = 'project' or type = 'community')";
        $query .= " GROUP BY " . $this->addDatabasePrefix($this->_db_table) . ".item_id";
        $query .= " ORDER BY " . $this->addDatabasePrefix($this->_db_table) . ".type";
        $query .= ", " . $this->addDatabasePrefix($this->_db_table) . ".title";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems counting used rooms ' . $this->_db_table . ' from query: "' . $query . '"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $list->add($this->_buildItem($rs));
            }
            unset($result);
        }

        return $list;
    }

    function getRelatedRoomListForUser($user_item)
    {
        return $this->_getRelatedContextListForUser($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID());
    }

    function getAllRelatedRoomListForUser($user_item)
    {
        $this->setRoomTypeLimit('');
        return $this->_getRelatedContextListForUser($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), true);
    }

    function getAllMaxActivityPoints()
    {
        $retour = 0;
        $query = 'SELECT MAX(activity) AS max FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE deleter_id IS NULL AND deletion_date is NULL and (type = "project" or type = "community");';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting ' . $this->_db_table . ' max activity from query: "' . $query . '"', E_USER_WARNING);
        } else {
            if (!empty($result[0]['max'])) {
                $retour = $result[0]['max'];
            }
        }
        return $retour;
    }

    function getMaxActivityPoints()
    {
        $retour = 0;
        $query = 'SELECT MAX(activity) AS max FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE deleter_id IS NULL AND deletion_date is NULL';
        if (!empty($this->_room_limit)) {
            $query .= ' and context_id = ' . encode(AS_DB, $this->_room_limit);
        }
        $query .= ';';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting ' . $this->_db_table . ' max activity from query: "' . $query . '"', E_USER_WARNING);
        } else {
            if (!empty($result[0]['max'])) {
                $retour = $result[0]['max'];
            }
        }
        return $retour;
    }

    function getLastQuery()
    {
        return $this->_last_query;
    }

    ##########################################################
    # statistic functions - BEGIN
    ##########################################################

    function getCountAllTypeRooms($type, $start, $end)
    {
        $retour = 0;

        $query = "SELECT count(" . $this->addDatabasePrefix($this->_db_table) . ".item_id) as number FROM " . $this->addDatabasePrefix($this->_db_table) . " WHERE context_id = '" . encode(AS_DB, $this->_room_limit) . "' and creation_date < '" . encode(AS_DB, $end) . "' and status != '4' AND deletion_date IS NULL AND deletion_date IS NULL";
        if (!empty($type)) {
            $query .= ' AND type="' . $type . '"';
        }
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems counting all rooms ' . $this->_db_table . ' from query: "' . $query . '"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
        }

        return $retour;
    }

    function getCountUsedTypeRooms($type, $start, $end)
    {
        return $this->_getUsedTypeRooms($type, $start, $end, 'COUNT');
    }

    function getCountActiveTypeRooms($type, $start, $end)
    {
        $list = $this->getActiveTypeRooms($type, $start, $end);
        if ($list->isEmpty()) {
            return 0;
        } else {
            return $list->getCount();
        }
    }

    function getActiveTypeRooms($type, $start, $end)
    {
        $list = $this->getUsedTypeRooms($type, $start, $end);

        // delete rooms that are not really active
        $retour_list = new cs_list();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if ($item->isActive($start, $end)) {
                    $retour_list->add($item);
                }
                $item = $list->getNext();
            }
        }

        return $retour_list;
    }

    function getUsedTypeRooms($type, $start, $end)
    {
        return $this->_getUsedTypeRooms($type, $start, $end, 'SELECT');
    }

    function _getUsedTypeRooms($type, $start, $end, $mode = 'SELECT')
    {
        if ($mode == 'COUNT') {
            $retour = 0;
            $query = "SELECT count(DISTINCT " . $this->addDatabasePrefix($this->_db_table) . ".item_id) as number";
        } else {
            $retour = new cs_list();
            $query = "SELECT DISTINCT " . $this->addDatabasePrefix($this->_db_table) . ".*";
        }
        $query .= " FROM " . $this->addDatabasePrefix($this->_db_table) . ", " . $this->addDatabasePrefix("user");
        $query .= " WHERE " . $this->addDatabasePrefix("user") . ".context_id=" . $this->addDatabasePrefix($this->_db_table) . ".item_id AND " . $this->addDatabasePrefix("user") . ".lastlogin > '" . encode(AS_DB, $start) . "' and " . $this->addDatabasePrefix("user") . ".creation_date < '" . encode(AS_DB, $end) . "'";
        $query .= " AND " . $this->addDatabasePrefix($this->_db_table) . ".context_id = '" . encode(AS_DB, $this->_room_limit) . "' AND " . $this->addDatabasePrefix($this->_db_table) . ".status != '4' AND " . $this->addDatabasePrefix($this->_db_table) . ".deletion_date IS NULL and " . $this->addDatabasePrefix($this->_db_table) . ".creation_date < '" . encode(AS_DB, $end) . "'";
        if (!empty($type)) {
            $query .= ' AND type="' . $type . '"';
        }
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems counting used rooms ' . $this->_db_table . ' from query: "' . $query . '"', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                if ($mode == 'COUNT') {
                    $retour = $rs['number'];
                } else {
                    $retour->add($this->_buildItem($rs));
                }
            }
        }

        return $retour;
    }

    ##########################################################
    # statistic functions - END
    ##########################################################

    function moveFromDbToBackup($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $this->_db_table . ' SELECT * FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $this->deleteFromDb($context_id);
        }
    }

    function moveFromBackupToDb($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $query = 'INSERT INTO ' . $this->_db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $this->deleteFromDb($context_id, true);
        }
    }

    function deleteFromDb($context_id, $from_backup = false)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        $db_prefix = '';
        if ($from_backup) {
            $db_prefix .= $c_db_backup_prefix . '_';
        }
        $query = 'DELETE FROM ' . $db_prefix . $this->_db_table . ' WHERE ' . $db_prefix . $this->_db_table . '.item_id = "' . $context_id . '"';
        $this->_db_connector->performQuery($query);
    }

    function deleteReallyOlderThan($days)
    {
        $retour = false;

        $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);

        $id_array = array();
        $query = 'SELECT item_id, context_id FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE deletion_date IS NOT NULL and deletion_date < "' . $timestamp . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            #include_once('functions/error_functions.php');
            #trigger_error('Problem deleting items.',E_USER_ERROR);
        } else {
            foreach ($result as $rs) {
                $temp_array['item_id'] = $rs['item_id'];
                $temp_array['portal_id'] = $rs['context_id'];
                $id_array[] = $temp_array;
            }
        }

        foreach ($id_array as $room_array) {
            $iid = $room_array['item_id'];
            $portal_id = $room_array['portal_id'];

            // delete files
            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->removeRoomDir($portal_id, $iid);
            unset($disc_manager);

            // delete db content or archive content
            $from_backup = false;
            if ($this->_environment->isArchiveMode()) {
                $from_backup = true;
            }

            // managers need data from other tables
            $hash_manager = $this->_environment->getHashManager();
            $hash_manager->deleteFromDb($iid, $from_backup);
            unset($hash_manager);

            $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
            $link_modifier_item_manager->deleteFromDb($iid, $from_backup);
            unset($link_modifier_item_manager);

            $link_item_file_manager = $this->_environment->getLinkItemFileManager();
            $link_item_file_manager->deleteFromDb($iid, $from_backup);
            unset($link_item_file_manager);

            $noticed_manager = $this->_environment->getNoticedManager();
            $noticed_manager->deleteFromDb($iid, $from_backup);
            unset($noticed_manager);

            $reader_manager = $this->_environment->getReaderManager();
            $reader_manager->deleteFromDb($iid, $from_backup);
            unset($reader_manager);

            // plain deletion of the rest
            $annotation_manager = $this->_environment->getAnnotationManager();
            $annotation_manager->deleteFromDb($iid, $from_backup);
            unset($annotation_manager);

            $announcement_manager = $this->_environment->getAnnouncementManager();
            $announcement_manager->deleteFromDb($iid, $from_backup);
            unset($announcement_manager);

            $dates_manager = $this->_environment->getDatesManager();
            $dates_manager->deleteFromDb($iid, $from_backup);
            unset($dates_manager);

            $discussion_manager = $this->_environment->getDiscussionManager();
            $discussion_manager->deleteFromDb($iid, $from_backup);
            unset($discussion_manager);

            $discussionarticles_manager = $this->_environment->getDiscussionarticleManager();
            $discussionarticles_manager->deleteFromDb($iid, $from_backup);
            unset($discussionarticles_manager);

            $file_manager = $this->_environment->getFileManager();
            $file_manager->deleteFromDb($iid, $from_backup);
            unset($file_manager);

            $item_manager = $this->_environment->getItemManager();
            $item_manager->deleteFromDb($iid, $from_backup);
            unset($item_manager);

            $labels_manager = $this->_environment->getLabelManager();
            $labels_manager->deleteFromDb($iid, $from_backup);
            unset($labels_manager);

            $links_manager = $this->_environment->getLinkManager();
            $links_manager->deleteFromDb($iid, $from_backup);
            unset($links_manager);

            $link_item_manager = $this->_environment->getLinkItemManager();
            $link_item_manager->deleteFromDb($iid, $from_backup);
            unset($link_item_manager);

            $material_manager = $this->_environment->getMaterialManager();
            $material_manager->deleteFromDb($iid, $from_backup);
            unset($material_manager);

            $section_manager = $this->_environment->getSectionManager();
            $section_manager->deleteFromDb($iid, $from_backup);
            unset($section_manager);

            $step_manager = $this->_environment->getStepManager();
            $step_manager->deleteFromDb($iid, $from_backup);
            unset($step_manager);

            $tag_manager = $this->_environment->getTagManager();
            $tag_manager->deleteFromDb($iid, $from_backup);
            unset($tag_manager);

            $tag2tag_manager = $this->_environment->getTag2TagManager();
            $tag2tag_manager->deleteFromDb($iid, $from_backup);
            unset($tag2tag_manager);

            $task_manager = $this->_environment->getTaskManager();
            $task_manager->deleteFromDb($iid, $from_backup);
            unset($task_manager);

            $todo_manager = $this->_environment->getTodoManager();
            $todo_manager->deleteFromDb($iid, $from_backup);
            unset($todo_manager);

            $user_manager = $this->_environment->getUserManager();
            $user_manager->deleteFromDb($iid, $from_backup);
            unset($user_manager);

            $room_manager = $this->_environment->getRoomManager();
            $room_manager->deleteFromDb($iid, $from_backup);
            unset($room_manager);
        }
        return $retour;
    }

    public function getUserRoomsUserIsMemberOf(\cs_user_item $user): \cs_list
    {
        $query = '
            SELECT r.*
            FROM room r
            INNER JOIN user u ON u.context_id = r.item_id
            WHERE r.type = "userroom" AND
                u.auth_source = ' . $user->getAuthSource() . ' AND
                u.deletion_date IS NULL AND
                u.deleter_id IS NULL AND
                r.deletion_date IS NULL AND
                r.deleter_id IS NULL AND
                u.user_id = "' . $user->getUserID() . '"
        ';
        $results = $this->_db_connector->performQuery($query);

        $list = new \cs_list();
        foreach ($results as $result) {
            $list->add($this->_buildItem($result));
        }

        return $list;
    }

    function deleteRoomOfUserAndUserItemsInactivity($uid)
    {
        // create backup of item
        global $symfonyContainer;
        $current_datetime = getCurrentDateTimeInMySQL();

        // list of rooms where user is member
        $query = '
            SELECT
                *
            FROM '
            . $this->addDatabasePrefix('user') . ','
            . $this->addDatabasePrefix('room') . '
            WHERE '
            . $this->addDatabasePrefix('user') . '.user_id = "' . $uid . '" AND '
            . $this->addDatabasePrefix('user') . '.context_id = ' . $this->addDatabasePrefix('room') . '.item_id AND '
            . $this->addDatabasePrefix('room') . '.type != "community" AND '
            . $this->addDatabasePrefix('user') . '.deletion_date IS NULL AND
                1 >= (
                    SELECT
                        COUNT(*)
                    FROM '
            . $this->addDatabasePrefix('user') . '
                    WHERE '
            . $this->addDatabasePrefix('user') . '.context_id = ' . $this->addDatabasePrefix('room') . '.item_id AND '
            . $this->addDatabasePrefix('user') . '.deletion_date IS NULL
                )';

        $result = $this->_db_connector->performQuery($query);
        if (isset($result)) {
            foreach ($result as $rs) {
                $insert_query = 'UPDATE ' . $this->addDatabasePrefix('room') . ' SET';
                $insert_query .= ' modification_date = "' . $current_datetime . '",';
                $insert_query .= ' deletion_date = "' . $current_datetime . '"';
                $insert_query .= ' WHERE item_id = "' . $rs['item_id'] . '"';
                $result2 = $this->_db_connector->performQuery($insert_query);
                if (!isset($result2) or !$result2) {
                    include_once('functions/error_functions.php');
                    trigger_error('Problems automatic deleting materials from query: "' . $insert_query . '"', E_USER_WARNING);
                }
            }
            $user_query = 'UPDATE ' . $this->addDatabasePrefix('user') . ' SET';
            $user_query .= ' modification_date = "' . $current_datetime . '",';
            $user_query .= ' deletion_date = "' . $current_datetime . '"';
            $user_query .= ' WHERE user_id = "' . $rs['user_id'] . '"';
            $result3 = $this->_db_connector->performQuery($user_query);
            if (!isset($result3) or !$result3) {
                include_once('functions/error_functions.php');
                trigger_error('Problems automatic deleting materials from query: "' . $user_query . '"', E_USER_WARNING);
            }
        }
    }

    public function getNumberOfModerators($roomId)
    {
        $query = '
            SELECT COUNT(user.item_id) AS numMods FROM user
            WHERE
                user.deleter_id IS NULL AND
                user.deletion_date IS NULL AND
                user.status = 3 AND
                user.context_id = ' . encode(AS_DB, $roomId) . '
        ';

        $result = $this->_db_connector->performQuery($query);

        if ($result && isset($result[0]['numMods'])) {
            return (int)$result[0]['numMods'];
        }

        return 0;
    }
}