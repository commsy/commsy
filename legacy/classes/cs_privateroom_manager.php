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

    private bool $_active_limit = false;

    private array $roomArrayCache = [];

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_db_table = 'room';
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
        $this->_room_type = CS_PRIVATEROOM_TYPE;
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

    #[Deprecated('private rooms are now using the room table too, type is set implicitly and not meant overwritten.')]
    public function setTypeLimit($limit)
    {
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

        if ($this->_active_limit) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id = '.$this->addDatabasePrefix($this->_db_table).'.item_id AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('accounts').' ON '.$this->addDatabasePrefix('user').'.account_id = '.$this->addDatabasePrefix('accounts').'.id';
        }

        $query .= ' WHERE 1';

        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,
                $this->_room_type).'"';

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

        if ($this->_active_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('accounts').'.portal_id = '.$this->addDatabasePrefix($this->_db_table).'.portal_id';
            $query .= ' AND '.$this->addDatabasePrefix('accounts').'.last_login >= "'.getCurrentDateTimeMinusDaysInMySQL(100).'"';
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
        $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
            'item_id="'.encode(AS_DB, $item->getItemID()).'",'.
            'context_id="'.encode(AS_DB, $item->getContextID()).'",'.
            'portal_id="'.encode(AS_DB, $item->getContextID()).'",'.
            'creator_id="'.encode(AS_DB, $user->getItemID()).'",'.
            'modifier_id="'.encode(AS_DB, $user->getItemID()).'",'.
            'creation_date="'.$current_datetime.'",'.
            'modification_date="'.$current_datetime.'",'.
            'title="'.encode(AS_DB, $item->getTitle()).'",'.
            'extras="'.encode(AS_DB, serialize($item->getExtraInformation())).'",'.
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
            $continuous = 0;
        }
        if ($item->isTemplate()) {
            $template = 1;
        } else {
            $template = 0;
        }

        if ($item->getActivityPoints()) {
            $activity = $item->getActivityPoints();
        } else {
            $activity = '0';
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

    public function getRelatedOwnRoomForUser(cs_user_item $user_item, int $context_id): cs_privateroom_item|null
    {
        if (!empty($user_item)) {
            if (isset($this->roomArrayCache[$user_item->getItemID()])
                && !empty($this->roomArrayCache[$user_item->getItemID()])
            ) {
                return $this->roomArrayCache[$user_item->getItemID()];
            } else {
                $accountId = $user_item->getAccountID();
                if ($accountId === null) {
                    return null;
                }

                $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

                $queryBuilder
                    ->select('r.*')
                    ->from($this->addDatabasePrefix($this->_db_table), 'r')
                    ->innerJoin('r', $this->addDatabasePrefix('user'), 'u', 'u.context_id = r.item_id')
                    ->andWhere('r.deleter_id IS NULL')
                    ->andWhere('r.deletion_date IS NULL')
                    ->andWhere('u.account_id = :accountId')
                    ->andWhere('u.deleter_id IS NULL')
                    ->andWhere('u.deletion_date IS NULL')
                    ->andWhere('r.type = :type')
                    ->andWhere('r.context_id = :contextId')
                    ->setParameter('accountId', $accountId)
                    ->setParameter('type', 'privateroom')
                    ->setParameter('contextId', $context_id);

                try {
                    $result = $this->_db_connector->performQuery($queryBuilder->getSQL(),
                        $queryBuilder->getParameters());

                    if (isset($result[0])) {
                        $item = $this->_buildItem($result[0]);
                        if (isset($item)) {
                            $item->setType(CS_PRIVATEROOM_TYPE);
                            $this->roomArrayCache[$user_item->getItemID()] = $item;

                            return $this->roomArrayCache[$user_item->getItemID()];
                        }
                    }
                } catch (\Doctrine\DBAL\Exception) {
                    trigger_error('Problems selecting '.$this->_db_table.' items.', E_USER_WARNING);
                }
            }
        }

        return null;
    }
}
