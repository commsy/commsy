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

use App\Account\AccountManager;
use App\Entity\Account;
use App\Entity\Room;
use App\Entity\User;
use App\Room\RoomStatus;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;

/** class for database connection to the database table "user"
 * this class implements a database manager for the table "user".
 */
class cs_user_manager extends cs_manager
{
    public $_last_query = '';

    /**
     * integer - containing the age of user as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing a start point for the select user.
     */
    public ?int $_from_limit = null;

    /**
     * integer - containing how many user the select statement should get.
     */
    public ?int $_interval_limit = null;

    public $_room_limit = null;

    public array $_is_user_in_context_cache = [];

    /**
     * integer - containing a status limit: 0 rejected, 1 registered, 2 normal user, 3 moderator.
     */
    public ?int $_status_limit = null;

    public ?int $_status_select_limit = null;

    /**
     * string - containing a string: name of a user -> search method.
     */
    public ?string $_name_limit = null;

    /**
     * boolean - containing a flag: load only user that has login in already (true) or all (false).
     */
    public $_lastlogin_limit = false;

    /**
     *  array - containing an id-array as search limit.
     */
    public $_id_array_limit = [];

    /**
     * array - containing the cached items already loaded from the database.
     */
    public array $_cache = [];

    /**
     * string - containing an order limit for the select users.
     */
    public ?string $_order = null;

    /**
     * document this limit (TBD).
     */
    public $_user_limit = null;

    /**
     * document this limit (TBD).
     */
    public $_contact_moderator_limit = null;

    /**
     * document this limit (TBD).
     */
    public $_group_limit = null;

    /**
     * integer - containing the id of a institution as a limit for the selected contacts.
     */
    public $_institution_limit = null;
    public $_topic_limit = null;

    public $_sort_order = null;

    public cs_user_item $rootUser;

    public $_context_array_limit = null;

    /**
     * When set, limits selects to `user.account_id = <value>`. Since
     * `Version20250514125210` the account_id FK is the identity key —
     * filtering by it is the safe alternative to the (user_id,
     * auth_source) tuple, which can collide across deprovisioned and
     * freshly-registered accounts.
     */
    public ?int $_account_id_limit = null;

    public $_cache_sql = [];

    private ?array $_group_array_limit = null;

    /**
     * @var mixed|null
     */
    private $_limit_email = null;

    /** constructor
     * the only available constructor, initial values for internal variables<br />
     * NOTE: the constructor must never be called directly, instead the cs_environment must
     * be used to access this manager.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_db_table = 'user';
    }

    /** reset limits
     * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class.
     */
    public function resetLimits()
    {
        parent::resetLimits();
        $this->_user_limit = null;
        $this->_age_limit = null;
        $this->_from_limit = null;
        $this->_interval_limit = null;
        $this->_status_limit = null;
        $this->_status_select_limit = null;
        $this->_lastlogin_limit = false;
        $this->_name_limit = null;
        $this->_group_limit = null;
        $this->_institution_limit = null;
        $this->_topic_limit = null;
        $this->_group_array_limit = null;
        $this->_order = null;
        $this->_sort_order = null;
        $this->_delete_limit = true;
        $this->_id_array_limit = [];
        $this->_context_array_limit = null;
        $this->_contact_moderator_limit = null;
        $this->_account_id_limit = null;
        $this->_limit_email = null;
    }

    public function setEMailLimit($value)
    {
        $this->_limit_email = $value;
    }

    /**
     * Limits selects to a single account id. This is the identity-safe
     * counterpart to the deprecated (user_id, auth_source) tuple lookup —
     * the account_id FK was added in `Version20250514125210` and the
     * `auth_source` column was dropped in the user-consistency refactor.
     */
    public function setAccountIDLimit(int $value): void
    {
        $this->_account_id_limit = $value;
    }

    /** set age limit
     * this method sets an age limit for user.
     *
     * @param int limit age limit for user
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int)$limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected user
     * @param int interval interval limit for selected user
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int)$interval;
        $this->_from_limit = (int)$from;
    }

    /** set status limit to "rejected"
     * this method sets the status limit to "rejected".
     */
    public function setRejectedLimit()
    {
        $this->_status_limit = 0;
    }

    /** set status limit to "registered"
     * this method sets the status limit to "registered".
     */
    public function setRegisteredLimit()
    {
        $this->_status_limit = 1;
    }

    /** set status limit to "normal user"
     * this method sets the status limit to "normal user".
     */
    public function setUserLimit($limit = null)
    {
        $this->_status_limit = 2;
    }

    /** set status limit to "moderator"
     * this method sets the status limit to "moderator".
     */
    public function setModeratorLimit()
    {
        $this->_status_limit = 3;
    }

    /**
     * set status limit to "readonly"
     * this method sets the status limit to "readonly".
     */
    public function setReadonlyLimit()
    {
        $this->_status_limit = 4;
    }

    public function setStatusLimit($limit)
    {
        if (6 == $limit) {
            $this->_status_select_limit = (int)0;
        } elseif (7 != $limit) {
            $this->_status_select_limit = (int)$limit;
        }
    }

    /** set group limit
     * this method sets a group limit for selected user.
     *
     * @param int limit id of the group
     */
    public function setGroupLimit($limit)
    {
        $this->_group_limit = (int)$limit;
        $this->_group_array_limit = null; // there can be only one
    }

    /** set group array limit
     * this method sets a group array limit for selected user.
     *
     * @param int limit id of the group
     */
    public function setGroupArrayLimit($limit)
    {
        $this->_group_array_limit = (array)$limit;
        $this->_group_limit = null; // there can be only one
    }

    /** set name limit
     * this method sets the name limit.
     */
    public function setNameLimit($name)
    {
        $this->_name_limit = \App\Legacy\SqlStringEscaper::escape($name);
    }

    public function setTopicLimit($limit)
    {
        $this->_topic_limit = (int)$limit;
    }

    public function setSortOrder($order)
    {
        $this->_sort_order = (string)$order;
    }

    /** set lastlogin limit
     * this method sets the last login limit.
     *
     * @param int days in the past user has not logged in or empty: user has logged in
     */
    public function setLastLoginLimit($value = '')
    {
        if (empty($value)) {
            $this->_lastlogin_limit = 'empty';
        } else {
            $this->_lastlogin_limit = \App\Utils\MysqlDateTime::nowMinusDays($value);
        }
    }

    /** set user id limit
     * this method sets a user id limit for user.
     *
     * @param string value user id limit for selected user
     */
    public function setUserIDLimit($value)
    {
        $this->_user_limit = (string)$value;
    }

    public function setContactModeratorLimit()
    {
        $this->_contact_moderator_limit = true;
    }

    /** set limit to array of context item_ids.
     *
     * @param array|int $limit ids of contexts user to be loaded from db
     */
    public function setContextArrayLimit($limit)
    {
        $this->_context_array_limit = (array) $limit;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected users
     */
    public function setOrder($limit)
    {
        $this->_order = (string)$limit;
    }

    /** get only the item ids of the selected items - should be deleted
     * (old style).
     */
    public function getIDs()
    {
        return $this->getIDArray();
    }

    public function isUserInContext(int $accountId, int $contextId): bool
    {
        if (isset($this->_is_user_in_context_cache[$accountId])) {
            return isset($this->_is_user_in_context_cache[$accountId][$contextId])
                && 'is_user' === $this->_is_user_in_context_cache[$accountId][$contextId];
        }

        $qb = $this->_db_connector->getConnection()->createQueryBuilder();
        $qb
            ->select('u.context_id')
            ->distinct()
            ->from($this->addDatabasePrefix('user'), 'u')
            ->where('u.account_id = :accountId')
            ->andWhere('u.deleter_id IS NULL')
            ->andWhere('u.deletion_date IS NULL')
            ->andWhere('u.status >= :status')
            ->setParameter('accountId', $accountId)
            ->setParameter('status', 2);

        $result = null;
        try {
            $result = $this->_db_connector->performQuery($qb->getSQL(), $qb->getParameters());
        } catch (\Doctrine\DBAL\Exception) {
            trigger_error('Problems selecting user.', E_USER_WARNING);
        }

        if (isset($result)) {
            foreach ($result as $r) {
                $this->_is_user_in_context_cache[$accountId][$r['context_id']] = 'is_user';
            }
            return isset($this->_is_user_in_context_cache[$accountId][$contextId])
                && 'is_user' === $this->_is_user_in_context_cache[$accountId][$contextId];
        }

        return false;
    }

    /**
     * Perform database query to get user data.
     *
     * @param string $mode
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function _performQuery($mode = 'select'): array
    {
        if (!empty($this->_user_limit)
            and 'GUEST' == mb_strtoupper((string)$this->_user_limit)
        ) {
            return [];
        }

        if ('count' == $mode) {
            $query = 'SELECT count(DISTINCT ' . $this->addDatabasePrefix('user') . '.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT DISTINCT ' . $this->addDatabasePrefix('user') . '.item_id';
        } else {
            $query = 'SELECT DISTINCT ' . $this->addDatabasePrefix('user') . '.*';
        }

        $query .= ' FROM ' . $this->addDatabasePrefix('user');
        if (isset($this->_topic_limit)) {
            $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('link_items') . ' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id=' . $this->addDatabasePrefix('user') . '.item_id AND l41.second_item_type="' . CS_TOPIC_TYPE . '"))) ';
            $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('link_items') . ' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id=' . $this->addDatabasePrefix('user') . '.item_id AND l42.first_item_type="' . CS_TOPIC_TYPE . '"))) ';
        }
        if (isset($this->_group_limit) || (isset($this->_group_array_limit) and !empty($this->_group_array_limit))) {
            $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('link_items') . ' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id=' . $this->addDatabasePrefix('user') . '.item_id AND l31.second_item_type="' . CS_GROUP_TYPE . '"))) ';
            $query .= ' LEFT JOIN ' . $this->addDatabasePrefix('link_items') . ' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id=' . $this->addDatabasePrefix('user') . '.item_id AND l32.first_item_type="' . CS_GROUP_TYPE . '"))) ';
        }

        $query .= ' WHERE 1';

        if (isset($this->_limit_email)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.email = "' . \App\Legacy\SqlStringEscaper::escape($this->_limit_email) . '"';
        }

        // fifth, insert limits into the select statement
        if (isset($this->_user_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.user_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_user_limit) . '"';
        }

        if (isset($this->_account_id_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.account_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_account_id_limit) . '"';
        }

        if (empty($this->_id_array_limit)) {
            if (isset($this->_context_array_limit)
                and !empty($this->_context_array_limit)
                and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
                and !empty($this->_context_array_limit[0])
            ) {
                $id_string = implode(',', $this->_context_array_limit);
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.context_id IN (' . $id_string . ')';
            } elseif (isset($this->_room_limit) and 0 != $this->_room_limit) {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.context_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_room_limit) . '"';
            } else {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.context_id IS NULL';
            }
        }

        if (true == $this->_delete_limit) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.deleter_id IS NULL';
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.deletion_date IS NULL';
        }
        if (true == $this->_contact_moderator_limit) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.is_contact="1"';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.modification_date >= DATE_SUB(CURRENT_DATE,interval ' . \App\Legacy\SqlStringEscaper::escape($this->_age_limit) . ' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.creation_date >= DATE_SUB(CURRENT_DATE,interval ' . \App\Legacy\SqlStringEscaper::escape($this->_existence_limit) . ' day)';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.modification_date >= DATE_SUB(CURRENT_DATE,interval ' . \App\Legacy\SqlStringEscaper::escape($this->_age_limit) . ' day)';
        }
        if (isset($this->_status_limit) and !isset($this->_status_select_limit)) {
            if (2 == $this->_status_limit) {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.status >= "' . \App\Legacy\SqlStringEscaper::escape($this->_status_limit) . '"';
            } else {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.status = "' . \App\Legacy\SqlStringEscaper::escape($this->_status_limit) . '"';
            }
        }
        if (isset($this->_status_select_limit)) {
            if (8 == $this->_status_select_limit) {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.status >= "2"';
            } else {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.status = "' . \App\Legacy\SqlStringEscaper::escape($this->_status_select_limit) . '"';
            }
        }

        if ($this->_lastlogin_limit) {
            if ('empty' != $this->_lastlogin_limit) {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.lastlogin > "' . \App\Legacy\SqlStringEscaper::escape($this->_lastlogin_limit) . '"';
            } else {
                $query .= ' AND ' . $this->addDatabasePrefix('user') . '.lastlogin IS NOT NULL AND user.lastlogin != "00-00-00 00:00:00"';
            }
        }

        if (isset($this->_name_limit)) {
            $name_array = explode(' ', (string)$this->_name_limit);
            if (1 == count($name_array)) {
                $query .= ' AND (' . $this->addDatabasePrefix('user') . '.firstname LIKE "' . \App\Legacy\SqlStringEscaper::escape($name_array[0]) . '" OR ' . $this->addDatabasePrefix('user') . '.lastname LIKE "' . \App\Legacy\SqlStringEscaper::escape($name_array[0]) . '")';
            } else {
                $query .= ' AND (' . $this->addDatabasePrefix('user') . '.firstname LIKE "' . \App\Legacy\SqlStringEscaper::escape($name_array[0]) . '" AND ' . $this->addDatabasePrefix('user') . '.lastname LIKE "' . \App\Legacy\SqlStringEscaper::escape($name_array[1]) . '")';
            }
        }

        if (!empty($this->_id_array_limit)) {
            $query .= ' AND ' . $this->addDatabasePrefix('user') . '.item_id IN (' . implode(', ', $this->_id_array_limit) . ')';
        }

        if (isset($this->_topic_limit)) {
            if (-1 == $this->_topic_limit) {
                $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
                $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l41.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_topic_limit) . '" OR l41.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_topic_limit) . '")';
                $query .= ' OR (l42.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_topic_limit) . '" OR l42.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_topic_limit) . '"))';
            }
        }
        if (isset($this->_institution_limit)) {
            if (-1 == $this->_institution_limit) {
                $query .= ' AND (l11.first_item_id IS NULL AND l11.second_item_id IS NULL)';
                $query .= ' AND (l12.first_item_id IS NULL AND l12.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l11.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_institution_limit) . '" OR l11.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_institution_limit) . '")';
                $query .= ' OR (l12.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_institution_limit) . '" OR l12.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_institution_limit) . '"))';
            }
        }
        if (isset($this->_group_limit)) {
            if (-1 == $this->_group_limit) {
                $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
                $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
            } else {
                $query .= ' AND ((l31.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_group_limit) . '" OR l31.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_group_limit) . '")';
                $query .= ' OR (l32.first_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_group_limit) . '" OR l32.second_item_id = "' . \App\Legacy\SqlStringEscaper::escape($this->_group_limit) . '"))';
            }
        }
        if (isset($this->_group_array_limit) and !empty($this->_group_array_limit)) {
            array_walk($this->_group_array_limit, function (&$v, $k) {
                $v = \App\Legacy\SqlStringEscaper::escape($v);
            });
            $mergedGroupIDs = implode(',', $this->_group_array_limit);
            $query .= ' AND ((l31.first_item_id IN (' . $mergedGroupIDs . ') OR l31.second_item_id IN (' . $mergedGroupIDs . '))';
            $query .= ' OR (l32.first_item_id IN (' . $mergedGroupIDs . ') OR l32.second_item_id IN (' . $mergedGroupIDs . ')))';
        }

        if ($this->modificationNewerThenLimit) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.modification_date >= "' . $this->modificationNewerThenLimit->format('Y-m-d H:i:s') . '"';
        }

        if ($this->creationNewerThenLimit) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.creation_date >= "' . $this->creationNewerThenLimit->format('Y-m-d H:i:s') . '"';
        }

        if ($this->excludedIdsLimit) {
            $query .= ' AND ' . $this->addDatabasePrefix($this->_db_table) . '.item_id NOT IN (' . implode(', ', \App\Legacy\SqlStringEscaper::escape($this->excludedIdsLimit)) . ')';
        }

        if ((isset($this->_search_limit)
                and !empty($this->_search_limit)
            )
            or isset($this->_status_select_limit)
        ) {
            $query .= ' GROUP BY ' . $this->addDatabasePrefix('user') . '.item_id';
        }
        if (isset($this->_sort_order)) {
            if ('name' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.lastname ASC, ' . $this->addDatabasePrefix('user') . '.firstname ASC, ' . $this->addDatabasePrefix('user') . '.user_id';
            } elseif ('name_rev' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.lastname DESC, ' . $this->addDatabasePrefix('user') . '.firstname DESC, ' . $this->addDatabasePrefix('user') . '.user_id';
            } elseif ('email' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.email ASC';
            } elseif ('email_rev' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.email DESC';
            } elseif ('user_id' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.user_id ASC';
            } elseif ('user_id_rev' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.user_id DESC';
            } elseif ('status' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.status ASC, ' . $this->addDatabasePrefix('user') . '.lastname, ' . $this->addDatabasePrefix('user') . '.firstname';
            } elseif ('status_rev' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.status DESC, ' . $this->addDatabasePrefix('user') . '.lastname, ' . $this->addDatabasePrefix('user') . '.firstname';
            } elseif ('date' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.creation_date DESC';
            } elseif ('last_login' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.lastlogin ASC, ' . $this->addDatabasePrefix('user') . '.lastname, ' . $this->addDatabasePrefix('user') . '.firstname';
            } elseif ('last_login_rev' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.lastlogin DESC, ' . $this->addDatabasePrefix('user') . '.lastname, ' . $this->addDatabasePrefix('user') . '.firstname';
            } elseif ('mod_date' == $this->_sort_order) {
                $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.modification_date DESC';
            }
        } else {
            $query .= ' ORDER BY ' . $this->addDatabasePrefix('user') . '.lastname, ' . $this->addDatabasePrefix('user') . '.firstname, ' . $this->addDatabasePrefix('user') . '.user_id ASC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT ' . $this->_from_limit . ', ' . $this->_interval_limit;
            }
        }
        $this->_last_query = $query;

        // perform query
        if (isset($this->_cache_sql[$query])) {
            return $this->_cache_sql[$query];
        } else {
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting user.', E_USER_WARNING);
            } else {
                if ($this->_cache_on) {
                    $this->_cache_sql[$query] = $result;
                }

                return $result;
            }
        }

        return [];
    }

    public function getLastQuery()
    {
        return $this->_last_query;
    }

    /** build a new user item
     * this method returns a new EMTPY user item.
     *
     * @return \cs_user_item a new EMPTY user
     */
    public function getNewItem(): cs_user_item
    {
        return new cs_user_item($this->_environment);
    }

    /** Returns the user item of the given item ID.
     *
     * @param int|null $itemId ID of the item
     */
    public function getItem(?int $itemId): ?cs_user_item
    {
        if (empty($itemId)) {
            return null;
        } elseif (!empty($this->_cache[$itemId])) {
            return $this->_cache[$itemId];
        }

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder
            ->select('u.*', 'i.pinned', 'i.draft')
            ->from($this->addDatabasePrefix($this->_db_table), 'u')
            ->innerJoin('u', 'items', 'i', 'i.item_id = u.item_id')
            ->where('u.item_id = :itemId')
            ->setParameter('itemId', $itemId);

        try {
            $result = $queryBuilder->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems selecting user item (' . $itemId . '): ' . $e->getMessage(), E_USER_WARNING);
        }

        /** @var cs_user_item $user */
        $user = null;
        if (!empty($result[0])) {
            $user = $this->_buildItem($result[0]);
            if ($this->_cache_on) {
                $this->_cache[$itemId] = $user;
            }
        }

        return $user;
    }

    public function getAllUsersByAccountAndRoomIDLimit(int $accountId, array $room_id_array): array
    {
        $retour = [];
        $user_array = $this->getUserArrayByAccountAndRoomIDLimit($accountId, $room_id_array);
        if (!empty($user_array)) {
            foreach ($user_array as $key => $value) {
                $retour[$key] = $this->_buildItem($value);
            }
        }

        return $retour;
    }

    public function getMembershipContextIDArrayByAccountAndRoomIDLimit(int $accountId, array $room_id_array): array
    {
        $retour = [];
        $user_array = $this->getUserArrayByAccountAndRoomIDLimit($accountId, $room_id_array);
        if (!empty($user_array)) {
            $room_id_array2 = [];
            foreach ($user_array as $value) {
                if (!empty($value['context_id']) and $value['context_id'] > 0) {
                    $room_id_array2[] = $value['context_id'];
                }
            }
            foreach ($room_id_array as $value) {
                if (in_array($value, $room_id_array2)) {
                    $retour[] = $value;
                }
            }
        }

        return $retour;
    }

    public function getUserArrayByAccountAndRoomIDLimit(int $accountId, array $room_id_array): array
    {
        $user_array = [];
        if (!empty($room_id_array)) {
            $query = 'SELECT * FROM ' . $this->addDatabasePrefix('user')
                . ' WHERE ' . $this->addDatabasePrefix('user') . '.context_id IN (' . implode(',', $room_id_array) . ')'
                . ' AND ' . $this->addDatabasePrefix('user') . '.account_id = "' . $accountId . '"'
                . ' AND ' . $this->addDatabasePrefix('user') . '.status >= "2"'
                . ' AND ' . $this->addDatabasePrefix('user') . '.deleter_id IS NULL'
                . ' AND ' . $this->addDatabasePrefix('user') . '.deletion_date IS NULL'
                . ' GROUP BY ' . $this->addDatabasePrefix('user') . '.item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of user items.', E_USER_WARNING);
            } else {
                foreach ($result as $rs) {
                    $user_array[$rs['context_id']] = $rs;
                }
                unset($result);
                unset($query);
            }
        }

        return $user_array;
    }

    public function getItemList(array $id_array): cs_list
    {
        return $this->_getItemList('user', $id_array);
    }

    public function getItemsForRoomIDChangedWithinDays(int $contextId, int $dayLimit): array
    {
        $this->reset();
        $this->setContextLimit($contextId);

        // NOTE: we only include newly created users (i.e., when they have requested room membership)
        $this->setExistenceLimit($dayLimit);

        $this->setUserLimit();

        $this->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $this->select();

        $itemsList = $this->get();

        return !$itemsList ? [] : $itemsList->to_array();
    }

    public function getRootUser(): cs_user_item
    {
        if (!isset($this->rootUser)) {
            $query = 'SELECT * FROM ' . $this->addDatabasePrefix('user') . ' WHERE ' . $this->addDatabasePrefix('user') . ".user_id = 'root' AND context_id = '" . \App\Legacy\SqlStringEscaper::escape($this->_environment->getServerID()) . "'";
            $result = $this->_db_connector->performQuery($query);
            $this->rootUser = $this->_buildItem($result[0]);
        }

        return $this->rootUser;
    }

    /** Prepares the db_array for the item.
     *
     * @param array $db_array Contains the data from the database
     */
    public function _buildItem(array $db_array): object
    {
        if (isset($db_array['extras'])) {
            $db_array['extras'] = unserialize($db_array['extras']);
        }

        return parent::_buildItem($db_array);
    }

    /** update a user - internal, do not use -> use method save
     * this method updates a user.
     *
     * @param cs_user_item $item the user
     */
    public function _update($item, $with_creator_id = false)
    {
        parent::_update($item);

        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();
        $queryBuilder->update('user');

        if ($item->isChangeModificationOnSave()) {
            $modifier_id = $item->getModificatorItem()?->getItemID();
            if (!empty($modifier_id)) {
                $queryBuilder->set('modifier_id', ':modifierId');
                $queryBuilder->setParameter('modifierId', $modifier_id);
            }

            $queryBuilder->set('modification_date', ':modificationDate');
            $queryBuilder->setParameter('modificationDate', \App\Utils\MysqlDateTime::now());
        }

        // if user was entered by system (creator_id == 0) then creator_id must change from 0 to item_id of the user_item
        // see methode _create()
        if ($with_creator_id) {
            $queryBuilder->set('creator_id', ':creatorId');
            $queryBuilder->setParameter('creatorId', $item->getCreatorID());
        }

        $contact_status = $item->getContactStatus() ?: 0;
        $usePortalEmail = $item->getUsePortalEmail() ?: 0;
        $portalId = ($item->isRoot()) ? null : $item->getPortalID();

        $queryBuilder
            ->set('context_id', ':contextId')
            ->set('portal_id', ':portalId')
            ->set('status', ':status')
            ->set('is_contact', ':isContact')
            ->set('account_id', ':accountId')
            ->set('user_id', ':userId')
            ->set('firstname', ':firstname')
            ->set('lastname', ':lastname')
            ->set('email', ':email')
            ->set('city', ':city')
            ->set('visible', ':visible')
            ->set('description', ':description')
            ->set('use_portal_email', ':usePortalEmail')
            ->set('extras', ':extras')
            ->where('item_id = :itemId')
            ->setParameter('contextId', $item->getContextID())
            ->setParameter('portalId', $portalId)
            ->setParameter('status', $item->getStatus())
            ->setParameter('isContact', $contact_status)
            ->setParameter('accountId', $item->getAccountID())
            ->setParameter('userId', $item->getUserID())
            ->setParameter('firstname', $item->getFirstname())
            ->setParameter('lastname', $item->getLastname())
            ->setParameter('email', $item->getRoomEmail())
            ->setParameter('city', $item->getCity())
            ->setParameter('visible', $item->getVisible())
            ->setParameter('description', $item->getDescription())
            ->setParameter('usePortalEmail', $usePortalEmail)
            ->setParameter('extras', empty($item->getExtraInformation()) ? null : serialize($item->getExtraInformation()))
            ->setParameter('itemId', $item->getItemID());

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            throw new RuntimeException('Problems updating user item.', 0, $e);
        }
    }

    /**
     * This method updates the last login of the user given user in the db.
     * The lastLogin will be setted to the current DateTime.
     *
     * @param cs_user_item $user_item user that will be updated
     */
    public function updateLastLoginOf($user_item)
    {
        $datetime = \App\Utils\MysqlDateTime::now();
        $query = 'UPDATE ' . $this->addDatabasePrefix('user') . ' SET ';
        $query .= 'lastlogin="' . $datetime . '" ';
        $query .= 'WHERE item_id="' . \App\Legacy\SqlStringEscaper::escape($user_item->getItemID()) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating users last login.', E_USER_ERROR);
        }
    }

    /** create a new item in the items table - internal, do not use -> use method save
     * this method creates a new item of type 'user' in the database and sets the dates user item id.
     * it then calls the private method _newUser to store the dates item itself.
     */
    public function _create($item): void
    {
        $query = 'INSERT INTO ' . $this->addDatabasePrefix('items') . ' SET ';
        $query .= 'context_id="' . \App\Legacy\SqlStringEscaper::escape($item->getContextID()) . '", ';
        $query .= 'modification_date="' . \App\Utils\MysqlDateTime::now() . '",' .
            'type="user"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating user.', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $item->setItemID($this->_create_id);
            $this->_newUser($item);
        }
    }

    /** creates a new user - internal, do not use -> use method save.
     *
     * @param object cs_item user_item the user
     */
    private function _newUser(cs_user_item $item): void
    {
        $queryBuilder = $this->_db_connector->getConnection()->createQueryBuilder();

        $now = \App\Utils\MysqlDateTime::now();

        /** @var AccountManager $accountManager */
        $accountManager = $this->_environment->getSymfonyContainer()->get(AccountManager::class);
        $account = $accountManager->getAccountForUser($item);

        $portalId = $item->getPortalID();
        if (!$item->isRoot() && $portalId == null) {
            $portalId = $account->getAuthSource()->getPortal()->getID();
        }

        $queryBuilder
            ->insert('user')
            ->setValue('item_id', ':itemId')
            ->setValue('context_id', ':contextId')
            ->setValue('portal_id', ':portalId')
            ->setValue('creator_id', ':creatorId')
            ->setValue('creation_date', ':creationDate')
            ->setValue('modification_date', ':modificationDate')
            ->setValue('account_id', ':accountId')
            ->setValue('user_id',  ':userId')
            ->setValue('status', ':status')
            ->setValue('firstname', ':firstname')
            ->setValue('lastname', ':lastname')
            ->setValue('email', ':email')
            ->setValue('city', ':city')
            ->setValue('visible', ':visible')
            ->setValue('description', ':description')
            ->setValue('extras', ':extras')
            ->setParameter('itemId', $item->getItemID())
            ->setParameter('contextId', $item->getContextID())
            ->setParameter('portalId', $portalId ?: null)
            ->setParameter('creatorId', !empty($item->getCreatorID()) ? $item->getCreatorID() : $item->getItemId())
            ->setParameter('creationDate', $now)
            ->setParameter('modificationDate', $now)
            ->setParameter('accountId', $account->getID())
            ->setParameter('userId', $item->getUserID())
            ->setParameter('status', $item->getStatus())
            ->setParameter('firstname', $item->getFirstname())
            ->setParameter('lastname', $item->getLastname())
            ->setParameter('email', $item->getEmail())
            ->setParameter('city', $item->getCity())
            ->setParameter('visible', $item->getVisible())
            ->setParameter('description', $item->getDescription())
            ->setParameter('extras', empty($item->getExtraInformation()) ? null : serialize($item->getExtraInformation()));

        try {
            $queryBuilder->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems insert new user item.', E_USER_WARNING);
        }
    }

    #[Override]
    public function deleteFromDb($context_id)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->_environment->getSymfonyContainer()->get('doctrine.orm.entity_manager');

        // grab all ids for the given context
        $query = $em->createQuery('SELECT u.itemId FROM App\Entity\User u WHERE u.room = :contextId');
        $query->setParameter('contextId', $context_id);
        $userIds = $query->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);

        $schemasWithReference = [User::class, Room::class];
        foreach ($schemasWithReference as $schema) {
            // Remove all references to this user object
            $updateCreatorQuery = $em->createQuery("UPDATE $schema t SET t.creator = NULL WHERE t.creator IN (:userIds)");
            $updateCreatorQuery->setParameter('userIds', $userIds);
            $updateCreatorQuery->execute();
            $updateModifierQuery = $em->createQuery("UPDATE $schema t SET t.modifier = NULL WHERE t.modifier IN (:userIds)");
            $updateModifierQuery->setParameter('userIds', $userIds);
            $updateModifierQuery->execute();
        }

        parent::deleteFromDb($context_id);
    }

    /** save a commsy item
     * this method saves a commsy item.
     *
     * @param cs_user_item $item
     */
    public function saveItem($item): void
    {
        $setCreatorID2ItemID = false;
        $item_id = $item->getItemID();

        if (!empty($item_id)) {
            $this->_update($item);
        } else {
            $creator_id = $item->getCreatorID();
            if (empty($creator_id)) {
                $current_user = $this->_environment->getCurrentUser();
                $creator_id = $current_user->getItemID();
                if (!empty($creator_id)) {
                    $item->setCreatorID($creator_id);
                } else {
                    $setCreatorID2ItemID = true;
                }
            }
            $this->_create($item);
            if ($setCreatorID2ItemID) {
                $this->setCreatorID2ItemID($item);
            }

            $context_id = $item->getContextID();
            $portal_id = $this->_environment->getCurrentPortalID();
            if ($context_id == $portal_id) {
                // initiation of private room
                $room_manager = $this->_environment->getPrivateRoomManager();
                $room_item = $room_manager->getNewItem();
                $room_item->setCreatorItem($item);
                $room_item->setCreationDate(\App\Utils\MysqlDateTime::now());
                $room_item->setContextID($this->_environment->getCurrentPortalID());
                $room_item->setPortalID($this->_environment->getCurrentPortalID());
                $room_item->setShowTitle();
                $room_item->setStatus(RoomStatus::OPEN->value);
                $room_item->setTitle('PRIVATE_ROOM');
                $room_item->setCheckNewMemberAlways();
                $room_item->setClosedForGuests();
                $room_item->setContinuous();
                $room_item->save();
            }
        }

        // customized room list
        if (empty($item_id)
            or ($item->getLastStatus() != $item->getStatus()
                and $item->isUser()
                and $item->getLastStatus() < 2
            )
        ) {
            $private_room = $item->getOwnRoom();
            if (isset($private_room)) {
                $customized_room_id_array = $private_room->getCustomizedRoomIDArray();
                if (!empty($customized_room_id_array)
                    and !in_array($item->getContextID(), $customized_room_id_array)
                ) {
                    $new_array = [];
                    $new_array[] = $item->getContextID();
                    $new_array = array_merge($new_array, $customized_room_id_array);
                    $private_room->setCustomizedRoomIDArray($new_array);
                    $private_room->save();
                }
            }
        }

        // Add modifier to all users who ever edited this user
        if ($this->_link_modifier) {
            $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
            $mod_id = $item->getModificatorID();
            if (!empty($mod_id) && is_numeric($mod_id) && $mod_id > 99) {
                $link_modifier_item_manager->markEdited($item->getItemID(), $mod_id);
            } else {
                $link_modifier_item_manager->markEdited($item->getItemID());
            }
        }
    }

    /**
     * Updates a new user - internal, do not use -> use method save
     * this method sets the creator id to the item id for new user at the portal.
     *
     * @param cs_user_item $item user_item the user
     * @throws \Doctrine\DBAL\Exception
     */
    public function setCreatorID2ItemID(cs_user_item $item): void
    {
        $query = 'UPDATE ' . $this->addDatabasePrefix('user') . ' SET ' .
            'creator_id="' . \App\Legacy\SqlStringEscaper::escape($item->getItemID()) . '"' .
            ' WHERE item_id="' . \App\Legacy\SqlStringEscaper::escape($item->getItemID()) . '"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems set creator id to item id.', E_USER_WARNING);
        } else {
            unset($result);
        }
    }

    public function changeUserID(string $username, Account $account): void
    {
        $qb = $this->_db_connector->getConnection()->createQueryBuilder();

        $qb
            ->update($this->addDatabasePrefix('user'), 'u')
            ->set('u.user_id', ':newUserId')
            ->set('u.modifier_id', 'u.creator_id')
            ->set('u.modification_date', ':modificationDate')
            ->where('u.user_id = :oldUserId')
            ->andWhere('u.portal_id = :portalId')
            ->setParameter('newUserId', $username)
            ->setParameter('modificationDate', \App\Utils\MysqlDateTime::now())
            ->setParameter('oldUserId', $account->getUsername())
            ->setParameter('portalId', $account->getContextId());

        try {
            $qb->executeStatement();
        } catch (\Doctrine\DBAL\Exception $e) {
            trigger_error('Problems updating user_id: ' . $e->getMessage(), E_USER_WARNING);
        }
    }

    // #########################################################
    // statistic functions
    // #########################################################

    public function getCountUsedAccounts($start, $end)
    {
        $retour = 0;

        $query = 'SELECT count(DISTINCT ' . $this->addDatabasePrefix('user') . '.email) as number FROM ' . $this->addDatabasePrefix('user') . ' WHERE';
        if (!empty($this->_context_array_limit)
            and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN (' . implode(',', \App\Legacy\SqlStringEscaper::escape($this->_context_array_limit)) . ')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '" . \App\Legacy\SqlStringEscaper::escape($this->_room_limit) . "'";
        }
        $query .= " and lastlogin > '" . \App\Legacy\SqlStringEscaper::escape($start) . "' and creation_date < '" . \App\Legacy\SqlStringEscaper::escape($end) . "'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting used accounts.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
            unset($result);
        }

        return $retour;
    }

    public function getCountOpenAccounts($start, $end)
    {
        $retour = 0;

        $query = 'SELECT count(DISTINCT ' . $this->addDatabasePrefix('user') . '.email) as number FROM ' . $this->addDatabasePrefix('user') . ' WHERE';
        if (!empty($this->_context_array_limit)
            and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN (' . implode(',', \App\Legacy\SqlStringEscaper::escape($this->_context_array_limit)) . ')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '" . \App\Legacy\SqlStringEscaper::escape($this->_room_limit) . "'";
        }
        $query .= " and status >= 2 and (deletion_date IS NULL or deletion_date > '" . \App\Legacy\SqlStringEscaper::escape($end) . "') and creation_date < '" . \App\Legacy\SqlStringEscaper::escape($end) . "'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting open accounts.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
            unset($result);
        }

        return $retour;
    }

    public function getCountAllAccounts($start, $end)
    {
        $retour = 0;

        $query = 'SELECT count(DISTINCT ' . $this->addDatabasePrefix('user') . '.email) as number FROM ' . $this->addDatabasePrefix('user') . ' WHERE';
        if (!empty($this->_context_array_limit)
            and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN (' . implode(',', \App\Legacy\SqlStringEscaper::escape($this->_context_array_limit)) . ')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '" . \App\Legacy\SqlStringEscaper::escape($this->_room_limit) . "'";
        }
        $query .= ' and ' . $this->addDatabasePrefix('user') . ".creation_date < '" . \App\Legacy\SqlStringEscaper::escape($end) . "'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting all accounts.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
        }

        return $retour;
    }

    public function getCountPlugin($plugin, $start, $end)
    {
        $retour = 0;

        $query = 'SELECT ' . $this->addDatabasePrefix($this->_db_table) . '.email,' . $this->addDatabasePrefix($this->_db_table) . '.extras FROM ' . $this->addDatabasePrefix($this->_db_table) . ' WHERE';
        if (!empty($this->_context_array_limit)
            and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN (' . implode(',', \App\Legacy\SqlStringEscaper::escape($this->_context_array_limit)) . ')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '" . \App\Legacy\SqlStringEscaper::escape($this->_room_limit) . "'";
        }
        $query .= ' and ' . $this->addDatabasePrefix($this->_db_table) . ".extras LIKE '%LASTLOGIN_" . mb_strtoupper((string)$plugin) . "%' and user.creation_date < '" . \App\Legacy\SqlStringEscaper::escape($end) . "'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting all accounts.', E_USER_WARNING);
        } else {
            $retour_array = [];
            foreach ($result as $rs) {
                $extra_array = [];
                if (!empty($rs['extras'])) {
                    $extra_array = unserialize($rs['extras']);
                    if (!empty($extra_array['LASTLOGIN_' . mb_strtoupper((string)$plugin)])
                        and $extra_array['LASTLOGIN_' . mb_strtoupper((string)$plugin)] > $start
                    ) {
                        $retour_array[] = $rs['email'];
                    }
                }
            }
            unset($result);

            if (!empty($retour_array)) {
                $retour_array = array_unique($retour_array);
                $retour = count($retour_array);
            }
        }

        return $retour;
    }

    public function resetCacheSQL()
    {
        $this->_cache_sql = [];
    }

    public function getUserTempLoginExpired(): array
    {
        $user_array = [];
        $query = 'SELECT * FROM ' . $this->addDatabasePrefix('user') . ' WHERE ' . $this->addDatabasePrefix('user') . ".status = '3' AND " . $this->addDatabasePrefix('user') . '.deletion_date IS NULL AND ' . $this->addDatabasePrefix('user') . ".extras LIKE '%LOGIN_AS_TMSP%'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems selecting list of user items.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $user_array[] = $this->_buildItem($rs);
            }
        }

        return $user_array;
    }

    /**
     * @param int[] $contextIds List of context ids
     * @param array Limits for buzzwords / categories
     * @param int $size Number of items to get
     * @param \DateTime $newerThen The oldest creation date to consider
     * @param int[] $excludedIds Ids to exclude
     *
     * @return \cs_list
     */
    public function getNewestItems($contextIds, $limits, $size, ?DateTime $newerThen = null, $excludedIds = [])
    {
        // return nothing in case of a set buzzword/category limit
        // (since buzzwords & categories currently can't be assigned to users)
        if (isset($limits['buzzword']) || isset($limits['categories'])) {
            return new cs_list();
        }

        // NOTE: we ignore the modificationNewerThenLimit here and instead set creationNewerThenLimit below
        parent::setGenericNewestItemsLimits($contextIds, $limits, null, $excludedIds);

        // NOTE: in case of user items (and opposed to all other item types), we consider the creation date (instead
        // of the modification date) when assembling lists of "newest items"; a user item gets created when a person
        // requests a room membership, and only in this case the user item will get included in any "newest items" feed;
        // this is done in order to avoid flooding the feeds with user items that were modified just for technical reasons
        if ($newerThen) {
            $this->setCreationNewerThenLimit($newerThen);
        }

        if ($size > 0) {
            $this->setIntervalLimit(0, $size);
        }

        $this->setUserLimit();
        $this->setSortOrder('date');

        $this->select();

        return $this->get();
    }
}
