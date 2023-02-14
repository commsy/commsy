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

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community".
 */
class cs_privateroom_manager extends cs_room2_manager
{
    /**
     * integer - containing the age of community as a limit.
     */
    public $_age_limit = null;

    public $_query_cache_array = [];

    /**
     * integer - containing a start point for the select community.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many communities the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - containing an order limit for the select community.
     */
    public $_order = null;

    public $_time_limit = null;

    private $_room_home_cache = null;

    private ?int $_template_limit = null;

    private bool $_active_limit = false;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_db_table = 'room_privat';
        $this->_room_type = CS_PRIVATEROOM_TYPE;
    }

    /** reset limits
     * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_order = null;
        $this->_time_limit = null;
        $this->_user_id_limit = null;
        $this->_room_type = CS_PRIVATEROOM_TYPE;
        $this->_template_limit = null;
        $this->_active_limit = false;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected communities
     * @param int interval interval limit for selected communities
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

    public function setActiveLimit()
    {
        $this->_active_limit = true;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected communities
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    public function setTypeLimit($limit)
    {
        $this->_room_type = (string) $limit;
    }

    public function getRelatedCommunityListForUser($user_item)
    {
        return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(),
            $this->_environment->getCurrentPortalID());
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

    /** set user id limit.
     *
     * @param string limit userid limit for selected project rooms
     */
    public function setUserIDLimit($limit)
    {
        $this->_user_id_limit = (string) $limit;
    }

    public function setAuthSourceLimit($limit)
    {
        $this->_auth_source_limit = (int) $limit;
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

    /** select privatrooms limited by limits
     * this method returns a list (cs_list) of privatrooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }

        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
        // user id limit
        if (isset($this->_user_id_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            if (!$this->_all_room_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            }
        }

        if ($this->_active_limit) {
            $query .= ' INNER JOIN user ON user.context_id = room_privat.item_id AND user.deletion_date IS NULL';
            $query .= ' INNER JOIN accounts ON user.user_id = accounts.username AND user.auth_source = accounts.auth_source_id';
        }

        $query .= ' WHERE 1';
        if (isset($this->_user_id_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,
                $this->_user_id_limit).'"';
        }
        if (isset($this->_auth_source_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,
                $this->_auth_source_limit).'"';
        }
        // insert limits into the select statement
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
        }
        if (isset($this->_status_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,
                $this->_status_limit).'"';
        }
        if (!empty($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,
                $this->_room_limit).'"';
        }
        if (isset($this->_room_type)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,
                $this->_room_type).'"';
        }
        if (isset($this->_template_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.template = "1"';
        }

        if ($this->_active_limit) {
            $query .= ' AND accounts.context_id = room_privat.context_id';
            $query .= ' AND accounts.last_login >= "'.getCurrentDateTimeMinusDaysInMySQL(100).'"';
        }

        // archive
        // lastlogin_limit
        if (!empty($this->_lastlogin_limit)) {
            if ('NULL' == $this->_lastlogin_limit) {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin = '.encode(AS_DB,
                    $this->_lastlogin_limit);
            }
        }

        if (isset($this->_order)) {
            if ('date' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('creation_date' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('creator' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
            } elseif ('activity' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } elseif ('activity_rev' == $this->_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
            } else {
                $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',
                E_USER_ERROR);
        } else {
            return $result;
        }
    }

    /** creates a new room - internal, do not use -> use method save
     * this method creates a new room.
     *
     * @param object cs_context_item (upper class) a commsy room
     */
    public function _new($item)
    {
        $current_datetime = getCurrentDateTimeInMySQL();
        $user = $item->getCreatorItem();
        if (empty($user)) {
            $user = $this->_environment->getCurrentUserItem();
        }
        if ($item->getPublic()) {
            $public = $item->getPublic();
        } else {
            $public = 0;
        }
        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
            'item_id="'.encode(AS_DB, $item->getItemID()).'",'.
            'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
            'creator_id="'.encode(AS_DB, $user->getItemID()).'",'.
            'modifier_id="'.encode(AS_DB, $user->getItemID()).'",'.
            'creation_date="'.$current_datetime.'",'.
            'modification_date="'.$current_datetime.'",'.
            'title="'.encode(AS_DB, $item->getTitle()).'",'.
            'extras="'.encode(AS_DB, serialize($item->getExtraInformation())).'",'.
            'public="'.encode(AS_DB, $public).'",'.
            'type="'.encode(AS_DB, $item->getRoomType()).'",'.
            'continuous="1",'.
            'status="'.encode(AS_DB, $item->getStatus()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating new '.$this->_room_type.' item from query: "'.$query.'"',
                E_USER_ERROR);
        }
    }

    /** update a room - internal, do not use -> use method save
     * this method updates a room.
     *
     * @param object cs_context_item a commsy room
     */
    public function _update($item)
    {
        if ($this->_update_with_changing_modification_information) {
            parent::_update($item);
        }
        $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET ';
        if ($this->_update_with_changing_modification_information) {
            $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",';
            $modifier_id = $this->_current_user->getItemID();
            if (!empty($modifier_id)) {
                $query .= 'modifier_id="'.encode(AS_DB, $modifier_id).'",';
            }
        }

        if ($item->isOpenForGuests()) {
            $open_for_guests = 1;
        } else {
            $open_for_guests = 0;
        }
        if ($item->isContinuous()) {
            $continuous = 1;
        } else {
            $continuous = -1;
        }
        if ($item->isTemplate()) {
            $template = 1;
        } else {
            $template = -1;
        }

        if ($item->getActivityPoints()) {
            $activity = $item->getActivityPoints();
        } else {
            $activity = '0';
        }

        if ($item->getPublic()) {
            $public = '1';
        } else {
            $public = '0';
        }

        if ($item->isTemplate()) {
            $title = $item->getTitlePure();
        } else {
            $title = $item->getTitle();
        }

        $query .= 'title="'.encode(AS_DB, $title).'",'.
            "extras='".encode(AS_DB, serialize($item->getExtraInformation()))."',".
            "status='".encode(AS_DB, $item->getStatus())."',".
            "activity='".encode(AS_DB, $activity)."',".
            "public='".encode(AS_DB, $public)."',".
            "continuous='".$continuous."',".
            "template='".$template."',".
            "is_open_for_guests='".$open_for_guests."'".
            ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';

        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating '.$this->_db_table.' item from query: "'.$query.'"',
                E_USER_WARNING);
        }
    }

    /**
     * @return array|cs_community_item|cs_grouproom_item|cs_portal_item|cs_privateroom_item|cs_project_item|cs_server_item|cs_userroom_item|mixed|object|null
     */
    public function getRelatedOwnRoomForUser(cs_user_item $user_item, int $context_id)
    {
        if (!empty($user_item)) {
            if (isset($this->_private_room_array[$user_item->getItemID()])
                && !empty($this->_private_room_array[$user_item->getItemID()])
            ) {
                return $this->_private_room_array[$user_item->getItemID()];
            } else {
                $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

                $queryBuilder
                    ->select('r.*')
                    ->from($this->addDatabasePrefix($this->_db_table), 'r')
                    ->innerJoin('r', $this->addDatabasePrefix('user'), 'u', 'u.context_id = r.item_id')
                    ->andWhere('r.deleter_id IS NULL')
                    ->andWhere('r.deletion_date IS NULL')
                    ->andWhere('u.auth_source = :authSource')
                    ->andWhere('u.deleter_id IS NULL')
                    ->andWhere('u.deletion_date IS NULL')
                    ->andWhere('u.user_id = :userId')
                    ->andWhere('r.type = :type')
                    ->andWhere('r.context_id = :contextId')
                    ->setParameter('authSource', $user_item->getAuthSource())
                    ->setParameter('userId', $user_item->getUserID())
                    ->setParameter('type', 'privateroom')
                    ->setParameter('contextId', $context_id);

                try {
                    $result = $this->_db_connector->performQuery($queryBuilder->getSQL(),
                        $queryBuilder->getParameters());

                    if (isset($result[0])) {
                        $item = $this->_buildItem($result[0]);
                        if (isset($item)) {
                            $item->setType(CS_PRIVATEROOM_TYPE);
                            $this->_private_room_array[$user_item->getItemID()] = $item;

                            return $this->_private_room_array[$user_item->getItemID()];
                        }
                    }
                } catch (\Doctrine\DBAL\Exception $e) {
                    trigger_error('Problems selecting '.$this->_db_table.' items.', E_USER_WARNING);
                }
            }
        }

        return null;
    }
}
