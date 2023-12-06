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

use App\Repository\HashRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
    public $_from_limit = null;

    public $_isset_room_user_cache = false;

    /**
     * integer - containing how many user the select statement should get.
     */
    public $_interval_limit = null;

    public $_room_limit = null;

    public $_is_user_in_context_cache = [];

    /**
     * string - containing a string as a search limit for accounts.
     */
    public $_account_search_limit = null;

    /**
     * integer - containing a status limit: 0 rejected, 1 registered, 2 normal user, 3 moderator.
     */
    public $_status_limit = null;

    public $_status_select_limit = null;

    /**
     * integer - containing 0 for not public, 0 - none (not visible), 1 - Commsy only visible if logged in, 2 - All always visible, >= 1 AllandCommsy.
     */
    public $_visible_limit = null;

    /**
     * string - containing a string: name of a user -> search method.
     */
    public $_name_limit = null;

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
    public $_cache = [];

    /**
     * string - containing an order limit for the select users.
     */
    public $_order = null;

    /**
     * document this limit (TBD).
     */
    public $_user_limit = null;

    public $_user_limit_binary = null;

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

    public $_root_user = null;

    public $_context_array_limit = null;

    public $_status_project_limit = null;

    public $_auth_source_limit = null;

    public $_limit_community = null;

    public $_limit_project = null;

    public $_limit_portal_id = null;

    public $_cache_sql = [];

    private bool $_only_from_portal = false;

    private ?array $_group_array_limit = null;

    /**
     * @var mixed|null
     */
    private $_limit_email = null;

    /**
     * @var mixed|null
     */
    private $_limit_connection_key = null;
    /**
     * @var mixed|null
     */
    private $_limit_connection_server_key = null;
    /**
     * @var mixed|null
     */
    private $_limit_connection_own_key = null;

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
        $this->_visible_limit = null;
        $this->_status_limit = null;
        $this->_status_project_limit = null;
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
        $this->_auth_source_limit = null;
        $this->_limit_community = null;
        $this->_limit_project = null;
        $this->_limit_portal_id = null;
        $this->_only_from_portal = false;
        $this->_limit_email = null;
        $this->_user_limit_binary = null;
        $this->_limit_connection_key = null;
        $this->_limit_connection_server_key = null;
        $this->_limit_connection_own_key = null;
    }

    public function setExternalConnectionUserKeyLimit($value)
    {
        $this->_limit_connection_key = $value;
    }

    public function setExternalConnectionServerKeyLimit($value)
    {
        $this->_limit_connection_server_key = $value;
    }

    public function setOwnConnectionUserKeyLimit($value)
    {
        $this->_limit_connection_own_key = $value;
    }

    public function setEMailLimit($value)
    {
        $this->_limit_email = $value;
    }

    public function setOnlyUserFromPortal()
    {
        $this->_only_from_portal = true;
    }

    public function setAuthSourceLimit($value)
    {
        $this->_auth_source_limit = (int) $value;
    }

    /** set age limit
     * this method sets an age limit for user.
     *
     * @param int limit age limit for user
     */
    public function setAgeLimit($limit)
    {
        $this->_age_limit = (int) $limit;
    }

    /** set interval limit
     * this method sets a interval limit.
     *
     * @param int from     from limit for selected user
     * @param int interval interval limit for selected user
     */
    public function setIntervalLimit($from, $interval)
    {
        $this->_interval_limit = (int) $interval;
        $this->_from_limit = (int) $from;
    }

  /** set visible limit, internal -> do not use.
   *
   * @param int limit visible limit for selected user
   */
  public function _setVisibleLimit($limit)
  {
      $this->_visible_limit = (string) $limit;
  }

  /** set order limit to name
   * this method sets an order limit for the select statement to name.
   */
  public function setVisibleToCommsy()
  {
      $this->_setVisibleLimit('= "1"');
  }

  /** set order limit to name
   * this method sets an order limit for the select statement to name.
   */
  public function setVisibleToAll()
  {
      $this->_setVisibleLimit('= "2"');
  }

  /** set order limit to name
   * this method sets an order limit for the select statement to name.
   */
  public function setVisibleToAllAndCommsy()
  {
      $this->_setVisibleLimit(' >= "1"');
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
          $this->_status_select_limit = (int) 0;
      } elseif (7 != $limit) {
          $this->_status_select_limit = (int) $limit;
      }
  }

    public function setUserInProjectLimit()
    {
        $this->_status_project_limit = 'user';
    }

    public function setContactModeratorInProjectLimit()
    {
        $this->_status_project_limit = 'contact_moderator';
    }

  /** set group limit
   * this method sets a group limit for selected user.
   *
   * @param int limit id of the group
   */
  public function setGroupLimit($limit)
  {
      $this->_group_limit = (int) $limit;
      $this->_group_array_limit = null; // there can be only one
  }

  /** set group array limit
   * this method sets a group array limit for selected user.
   *
   * @param int limit id of the group
   */
  public function setGroupArrayLimit($limit)
  {
      $this->_group_array_limit = (array) $limit;
      $this->_group_limit = null; // there can be only one
  }

  /** set name limit
   * this method sets the name limit.
   */
  public function setNameLimit($name)
  {
      $this->_name_limit = encode(AS_DB, $name);
  }

    public function setTopicLimit($limit)
    {
        $this->_topic_limit = (int) $limit;
    }

    public function setSortOrder($order)
    {
        $this->_sort_order = (string) $order;
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
          $this->_lastlogin_limit = getCurrentDateTimeMinusDaysInMySQL($value);
      }
  }

  /** set user id limit
   * this method sets a user id limit for user.
   *
   * @param string value user id limit for selected user
   */
  public function setUserIDLimit($value)
  {
      $this->_user_limit = (string) $value;
  }

  /** set user id limit with mysql binary (case sensitive)
   *  this method sets a user id limit for user (case sensitive).
   *
   *  @param string value user id limit for selected user
   */
  public function setUserIDLimitBinary($value)
  {
      $this->_user_limit_binary = (string) $value;
  }

  public function setContactModeratorLimit()
  {
      $this->_contact_moderator_limit = true;
  }

    /** set limit to array of context item_ids.
     *
     * @param array array of ids of contexts user to be loaded from db
     */
    public function setContextArrayLimit($id_array)
    {
        $this->_context_array_limit = (array) $id_array;
    }

    public function setPortalIDLimit($value)
    {
        $this->_limit_portal_id = (int) $value;
    }

    public function setCommunityLimit()
    {
        $this->_limit_community = true;
    }

    public function setProjectLimit()
    {
        $this->_limit_project = true;
    }

    /** set order limit
     * this method sets an order limit for the select statement.
     *
     * @param string limit order limit for selected users
     */
    public function setOrder($limit)
    {
        $this->_order = (string) $limit;
    }

    /** get only the item ids of the selected items - should be deleted
     * (old style).
     */
    public function getIDs()
    {
        return $this->getIDArray();
    }

     public function isUserInContext($user_id, $context_id, $auth_source): bool
     {
         if (isset($this->_is_user_in_context_cache[$user_id.$auth_source])) {
             if (isset($this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) and 'is_user' == $this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) {
                 return true;
             } else {
                 return false;
             }
         } else {
             $qb = $this->_db_connector->getConnection()->createQueryBuilder();

             $qb
                 ->select('u.context_id')
                 ->distinct()
                 ->from($this->addDatabasePrefix('user'), 'u')
                 ->where('u.user_id = :userId')
                 ->andWhere('u.auth_source = :authSource')
                 ->andWhere('u.deleter_id IS NULL')
                 ->andWhere('u.deletion_date IS NULL')
                 ->andWhere('u.status >= :status')
                 ->setParameter('userId', $user_id)
                 ->setParameter('authSource', $auth_source)
                 ->setParameter('status', 2);

             try {
                 $result = $this->_db_connector->performQuery($qb->getSQL(), $qb->getParameters());
             } catch (\Doctrine\DBAL\Exception) {
                 trigger_error('Problems selecting user.', E_USER_WARNING);
             }

             if (isset($result)) {
                 foreach ($result as $r) {
                     $this->_is_user_in_context_cache[$user_id.$auth_source][$r['context_id']] = 'is_user';
                 }
                 if (isset($this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) &&
                     'is_user' == $this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) {
                     return true;
                 }
             }
         }

         return false;
     }

    /** INTERNAL: perform database query to get user data.
     *
     */
    public function _performQuery($mode = 'select')
    {
        if (!empty($this->_user_limit)
             and 'GUEST' == mb_strtoupper((string) $this->_user_limit)
        ) {
            return [];
        }

        if ('count' == $mode) {
            $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.item_id) AS count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('user').'.item_id';
        } else {
            $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('user').'.*';
        }

        $query .= ' FROM '.$this->addDatabasePrefix('user');
        if (isset($this->_topic_limit)) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('user').'.item_id AND l41.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('user').'.item_id AND l42.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
        }
        if (isset($this->_group_limit) || (isset($this->_group_array_limit) and !empty($this->_group_array_limit))) {
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('user').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
            $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('user').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
        }
        if ($this->_status_project_limit) {
            // links over link_items to room
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l91 ON ( l91.deletion_date IS NULL AND l91.second_item_id='.$this->addDatabasePrefix('user').'.context_id AND l91.first_item_type="'.CS_PROJECT_TYPE.'") ';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('room').' ON ( '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND l91.first_item_id='.$this->addDatabasePrefix('room').'.item_id ) ';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS l92 ON ( '.$this->addDatabasePrefix('room').'.item_id=l92.context_id AND l92.user_id='.$this->addDatabasePrefix('user').'.user_id) ';
        }
        if ($this->_only_from_portal) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS user2 ON ( user2.user_id='.$this->addDatabasePrefix('user').'.user_id AND user2.auth_source='.$this->addDatabasePrefix('user').'.auth_source) ';
        }

        if (isset($this->_limit_portal_id)
             and (isset($this->_limit_community)
                   or isset($this->_limit_project)
             )
        ) {
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS user2 ON ( '.$this->addDatabasePrefix('user').'.user_id=user2.user_id and '.$this->addDatabasePrefix('user').'.auth_source=user2.auth_source ) ';
            $query .= ' INNER JOIN '.$this->addDatabasePrefix('room').' ON ( '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND user2.context_id=room.item_id ) ';
        }

        $query .= ' WHERE 1';

        if (isset($this->_limit_email)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.email = "'.encode(AS_DB, $this->_limit_email).'"';
        }

        // fifth, insert limits into the select statement
        if (isset($this->_user_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB, $this->_user_limit).'"';
        }
        if (isset($this->_user_limit_binary)) {
            $query .= ' AND BINARY '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB, $this->_user_limit_binary).'"';
        }

        if (empty($this->_id_array_limit)) {
            if (isset($this->_context_array_limit)
                 and !empty($this->_context_array_limit)
                 and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
                 and !empty($this->_context_array_limit[0])
            ) {
                $id_string = implode(',', $this->_context_array_limit);
                if ($this->_only_from_portal) {
                    $query .= ' AND user2.context_id IN ('.encode(AS_DB, $id_string).')';
                    $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB, $this->_environment->getCurrentPortalID()).'"';
                } else {
                    $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id IN ('.$id_string.')';
                }
            } elseif (isset($this->_room_limit) and 0 != $this->_room_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id IS NULL';
            }
        }

        if (isset($this->_auth_source_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source = "'.encode(AS_DB, $this->_auth_source_limit).'"';
        }

        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
        }
        if (true == $this->_contact_moderator_limit) {
            if (isset($this->_limit_portal_id)
                 and (isset($this->_limit_community)
                       or isset($this->_limit_project)
                 )
            ) {
                $query .= ' AND user2.is_contact="1"';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.is_contact="1"';
            }
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_existence_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_existence_limit).' day)';
        }
        if (isset($this->_age_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB, $this->_age_limit).' day)';
        }
        if (isset($this->_status_limit) and !isset($this->_status_select_limit)) {
            if (2 == $this->_status_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "'.encode(AS_DB, $this->_status_limit).'"';
            } else {
                if (isset($this->_limit_portal_id)
                     and (isset($this->_limit_community)
                           or isset($this->_limit_project)
                     )
                ) {
                    $query .= ' AND user2.status = "'.encode(AS_DB, $this->_status_limit).'"';
                } else {
                    $query .= ' AND '.$this->addDatabasePrefix('user').'.status = "'.encode(AS_DB, $this->_status_limit).'"';
                }
            }
        }
        if (isset($this->_status_select_limit)) {
            if (8 == $this->_status_select_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.status = "'.encode(AS_DB, $this->_status_select_limit).'"';
            }
        }
        if ($this->_status_project_limit) {
            if ('user' == $this->_status_project_limit) {
                $query .= ' AND l92.is_contact="0" AND l92.status >= "2"';
            } elseif ('contact_moderator' == $this->_status_project_limit) {
                $query .= ' AND l92.is_contact="1" AND l92.status >= "2"';
            }
            $query .= ' AND l92.deleter_id IS NULL';
            $query .= ' AND l92.deletion_date IS NULL';
        }
        if ($this->_lastlogin_limit) {
            if ('empty' != $this->_lastlogin_limit) {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin > "'.encode(AS_DB, $this->_lastlogin_limit).'"';
            } else {
                $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin IS NOT NULL AND user.lastlogin != "00-00-00 00:00:00"';
            }
        }

        if (isset($this->_visible_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.visible '.$this->_visible_limit;
        }

        if (isset($this->_name_limit)) {
            $name_array = explode(' ', (string) $this->_name_limit);
            if (1 == count($name_array)) {
                $query .= ' AND ('.$this->addDatabasePrefix('user').'.firstname LIKE "'.encode(AS_DB, $name_array[0]).'" OR '.$this->addDatabasePrefix('user').'.lastname LIKE "'.encode(AS_DB, $name_array[0]).'")';
            } else {
                $query .= ' AND ('.$this->addDatabasePrefix('user').'.firstname LIKE "'.encode(AS_DB, $name_array[0]).'" AND '.$this->addDatabasePrefix('user').'.lastname LIKE "'.encode(AS_DB, $name_array[1]).'")';
            }
        }

        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.item_id IN ('.implode(', ', $this->_id_array_limit).')';
        }

        // portal2Portal: connection key limit
        if (!empty($this->_limit_connection_key)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%CONNECTION_EXTERNAL_KEY_ARRAY%"';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%'.encode(AS_DB, $this->_limit_connection_key).'%"';
        }
        // portal2Portal: connection server key limit
        if (!empty($this->_limit_connection_server_key)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%CONNECTION_ARRAY%"';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%'.encode(AS_DB, $this->_limit_connection_server_key).'%"';
        }
        // portal2Portal: connection own key limit
        if (!empty($this->_limit_connection_own_key)) {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%s:17:\"CONNECTION_OWNKEY\";s:32:\"'.$this->_limit_connection_own_key.'\"%"';
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
        if (isset($this->_group_array_limit) and !empty($this->_group_array_limit)) {
            array_walk($this->_group_array_limit, function (&$v, $k) { $v = encode(AS_DB, $v); });
            $mergedGroupIDs = implode(',', $this->_group_array_limit);
            $query .= ' AND ((l31.first_item_id IN ('.$mergedGroupIDs.') OR l31.second_item_id IN ('.$mergedGroupIDs.'))';
            $query .= ' OR (l32.first_item_id IN ('.$mergedGroupIDs.') OR l32.second_item_id IN ('.$mergedGroupIDs.')))';
        }

        if (isset($this->_limit_portal_id)
            and (isset($this->_limit_community)
                  or isset($this->_limit_project)
            )
        ) {
            $query .= ' AND '.$this->addDatabasePrefix('room').'.context_id='.encode(AS_DB, $this->_limit_portal_id);
            if (isset($this->_limit_community)
                 and isset($this->_limit_project)
            ) {
                $query .= ' AND ('.$this->addDatabasePrefix('room').'.type="'.CS_COMMUNITY_TYPE.'" OR '.$this->addDatabasePrefix('room').'.type="'.CS_PROJECT_TYPE.'")';
            } elseif (isset($this->_limit_community)) {
                $query .= ' AND '.$this->addDatabasePrefix('room').'.type="'.CS_COMMUNITY_TYPE.'"';
            } elseif (isset($this->_limit_project)) {
                $query .= ' AND '.$this->addDatabasePrefix('room').'.type="'.CS_PROJECT_TYPE.'"';
            }
        }

        if ($this->modificationNewerThenLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date >= "'.$this->modificationNewerThenLimit->format('Y-m-d H:i:s').'"';
        }

        if ($this->creationNewerThenLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date >= "'.$this->creationNewerThenLimit->format('Y-m-d H:i:s').'"';
        }

        if ($this->excludedIdsLimit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id NOT IN ('.implode(', ', encode(AS_DB, $this->excludedIdsLimit)).')';
        }

        if (isset($this->_limit_portal_id)
             and (isset($this->_limit_community)
                  or isset($this->_limit_project)
             )
        ) {
            $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.user_id,'.$this->addDatabasePrefix('user').'.auth_source';
        }
        if ((isset($this->_search_limit)
               and !empty($this->_search_limit)
        )
        or isset($this->_status_select_limit)
        ) {
            $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
        }
        if (isset($this->_sort_order)) {
            if ('name' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname ASC, '.$this->addDatabasePrefix('user').'.firstname ASC, '.$this->addDatabasePrefix('user').'.user_id';
            } elseif ('name_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname DESC, '.$this->addDatabasePrefix('user').'.firstname DESC, '.$this->addDatabasePrefix('user').'.user_id';
            } elseif ('email' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.email ASC';
            } elseif ('email_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.email DESC';
            } elseif ('user_id' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.user_id ASC';
            } elseif ('user_id_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.user_id DESC';
            } elseif ('status' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.status ASC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname';
            } elseif ('status_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.status DESC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname';
            } elseif ('date' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.creation_date DESC';
            } elseif ('last_login' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastlogin ASC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname';
            } elseif ('last_login_rev' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastlogin DESC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname';
            } elseif ('mod_date' == $this->_sort_order) {
                $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.modification_date DESC';
            }
        } else {
            $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname, '.$this->addDatabasePrefix('user').'.user_id ASC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
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

    /** get a user in newest version.
     *
     * @param int item_id id of the item
     */
    public function getItem(?int $item_id): ?cs_user_item
    {
        $user = null;
        if (isset($this->_cache[$item_id])) {
            $user = $this->_cache[$item_id];
        } elseif (!empty($item_id)) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').".item_id = '".encode(AS_DB, $item_id)."'";
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting one user item.', E_USER_WARNING);
            } elseif (!empty($result[0])) {
                $user = $this->_buildItem($result[0]);
                unset($result);
                if ($this->_cache_on
                     and !array_key_exists($item_id, $this->_cache)
                ) {
                    $this->_cache[$item_id] = $user;
                }
            }
        }

        return $user;
    }

    public function getItemByUserIDAuthSourceID($uid, $asid)
    {
        $retour = null;
        if (!empty($uid)
             and !empty($asid)
        ) {
            $this->resetLimits();
            $this->setUserIDLimit($uid);
            $this->setAuthSourceLimit($asid);
            $this->setContextLimit($this->_environment->getCurrentContextID());
            $this->select();
            $list = $this->get();
            if ($list->isNotEmpty()
                 and 1 == $list->getCount()
            ) {
                $retour = $list->getFirst();
            } elseif ($list->isNotEmpty()
                       and $list->getCount() > 1
            ) {
                trigger_error('bug in database: multiple user for user_id: '.$uid.', auth_source_id: '.$asid.', portal: '.$this->_environment->getCurrentContextID().' - '.__FILE__.' - '.__LINE__, E_USER_ERROR);
            }
        }

        return $retour;
    }

    public function getRoomUserByIDsForCache($context_id, $id_array = 0)
    {
        // ------------------
        // --->UTF8 - OK<----
        // ------------------
        if (!$this->_cache_on) {
            // do nothing
        } elseif (!empty($context_id) and !empty($id_array)) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.item_id IN ('.implode(',', $id_array).') AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB, $context_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
            } else {
                foreach ($result as $rs) {
                    $user = $this->_buildItem($rs);
                    if (!array_key_exists($rs['item_id'], $this->_cache)) {
                        $this->_cache[$rs['item_id']] = $user;
                    }
                }
                unset($result);
            }
            unset($query);
        } elseif (!empty($context_id)) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE  '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB, $context_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
            } else {
                foreach ($result as $rs) {
                    $user = $this->_buildItem($rs);
                    if (!array_key_exists($rs['item_id'], $this->_cache)) {
                        $this->_cache[$rs['item_id']] = $user;
                    }
                }
                unset($result);
            }
            unset($query);
        }
    }

    public function getAllUsersByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id)
    {
        $retour = [];
        $user_array = $this->getUserArrayByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id);
        if (!empty($user_array)) {
            foreach ($user_array as $key => $value) {
                $retour[$key] = $this->_buildItem($value);
            }
        }

        return $retour;
    }

    public function getMembershipContextIDArrayByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id)
    {
        $retour = [];
        $user_array = $this->getUserArrayByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id);
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

    public function getUserArrayByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id)
    {
        $user_array = [];
        if (isset($room_id_array) and !empty($room_id_array)) {
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.context_id IN ('.implode(',', $room_id_array).') AND '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB, $user_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
            $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source = "'.$auth_source_id.'"';
            $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
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

    public function getAllRoomUsersFromCache($context_id)
    {
        $user_list = new cs_list();
        if (!empty($context_id) and !empty($this->_cache)) {
            foreach ($this->_cache as $user) {
                $user_list->add($user);
            }
        } else {
            $this->resetLimits();
            $this->setContextLimit($this->_environment->getCurrentContextID());
            $this->setUserLimit();
            $this->select();
            $user_list = $this->get();
        }

        return $user_list;
    }

    public function getItemList(array $id_array)
    {
        return $this->_getItemList('user', $id_array);
    }

    public function getRootUser()
    {
        if (!isset($this->_root_user)) {
            $this->setWithoutDatabasePrefix();
            $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').".user_id = 'root' AND context_id = '".encode(AS_DB, $this->_environment->getServerID())."'";
            $this->setWithDatabasePrefix();
            $result = $this->_db_connector->performQuery($query);
            if (!isset($result)) {
                trigger_error('Problems selecting one user item.', E_USER_WARNING);
            } elseif (!empty($result[0])) {
                $this->_root_user = $this->_buildItem($result[0]);
                unset($result);
            } else {
                trigger_error('can not get root user object - '.__LINE__.' - '.__FILE__, E_USER_WARNING);
            }
        }

        return $this->_root_user;
    }

    /** Prepares the db_array for the item.
     *
     * @param $db_array Contains the data from the database
     *
     * @return array Contains prepared data ( textfunctions applied etc. )
     */
    public function _buildItem($db_array)
    {
        $db_array['extras'] = unserialize($db_array['extras']);

        return parent::_buildItem($db_array);
    }

  /** update a user - internal, do not use -> use method save
   * this method updates a user.
   *
   * @param object cs_item user_item the user
   */
  public function _update($user_item, $with_creator_id = false)
  {
      parent::_update($user_item);
      $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';
      if ($user_item->isChangeModificationOnSave()) {
          $modificator = $user_item->getModificatorItem();
          if (isset($modificator)) {
              $modifier_id = $modificator->getItemID();
              if (!empty($modifier_id)) {
                  $query .= 'modifier_id="'.encode(AS_DB, $modifier_id).'",';
              }
              unset($modificator);
          }
          $query .= 'modification_date="'.encode(AS_DB, getCurrentDateTimeInMySQL()).'",';
      }

      $contact_status = $user_item->getContactStatus();
      if (empty($contact_status)) {
          $contact_status = 0;
      }

      $usePortalEmail = $user_item->getUsePortalEmail();
      if (empty($usePortalEmail)) {
          $usePortalEmail = 0;
      }

      $query .= 'context_id="'.encode(AS_DB, $user_item->getContextID()).'",';
      $query .= 'status="'.encode(AS_DB, $user_item->getStatus()).'",';
      $query .= 'is_contact="'.encode(AS_DB, $contact_status).'",';
      $query .= 'user_id="'.encode(AS_DB, $user_item->getUserID()).'",';
      $query .= 'auth_source="'.$user_item->getAuthSource().'",';
      $query .= 'firstname="'.encode(AS_DB, $user_item->getFirstname()).'",';
      $query .= 'lastname="'.encode(AS_DB, $user_item->getLastname()).'",';
      $query .= 'email="'.encode(AS_DB, $user_item->getRoomEmail()).'",';
      $query .= 'city="'.encode(AS_DB, $user_item->getCity()).'",';
      $query .= 'visible="'.encode(AS_DB, $user_item->getVisible()).'",';
      $query .= 'description="'.encode(AS_DB, $user_item->getDescription()).'",';
      $query .= 'use_portal_email="'.encode(AS_DB, $usePortalEmail).'",';
      // Datenschutz
      $expire_date = $user_item->getPasswordExpireDate();

      if (empty($expire_date) or 0 == $expire_date) {
          $query .= 'expire_date=NULL,';
      } else {
          $query .= 'expire_date="'.encode(AS_DB, $expire_date).'",';
      }

      // if user was entered by system (creator_id == 0) then creator_id must change from 0 to item_id of the user_item
      // see methode _create()
      if ($with_creator_id) {
          $query .= 'creator_id="'.encode(AS_DB, $user_item->getCreatorID()).'",';
      }

      $query .= "extras='".encode(AS_DB, serialize($user_item->getExtraInformation()))."'";
      $query .= ' WHERE item_id="'.encode(AS_DB, $user_item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or !$result) {
          trigger_error('Problems upating user item.', E_USER_ERROR);
      } else {
          unset($result);
      }
      unset($user_item);
  }

    /**
     * This method updates the last login of the user given user in the db.
     * The lastLogin will be setted to the current DateTime.
     *
     * @param user_item is the User, who will be updated
     */
    public function updateLastLoginOf($user_item)
    {
        $datetime = getCurrentDateTimeInMySQL();
        $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';
        $query .= 'lastlogin="'.$datetime.'" ';
        $query .= 'WHERE item_id="'.encode(AS_DB, $user_item->getItemID()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems updating users last login.', E_USER_ERROR);
        } else {
            unset($result);
        }
        unset($user_item);
    }

    /** create a new item in the items table - internal, do not use -> use method save
     * this method creates a new item of type 'user' in the database and sets the dates user item id.
     * it then calls the private method _newUser to store the dates item itself.
     *
     * @param cs_dates_item the dates item for which an entry should be made
     */
    public function _create($item)
    {
        $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET ';
        $context_id = $item->getContextID();
        $query .= 'context_id="'.encode(AS_DB, $item->getContextID()).'", ';
        $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
                  'type="user"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems creating user.', E_USER_WARNING);
            $this->_create_id = null;
        } else {
            $this->_create_id = $result;
            $item->setItemID($this->_create_id);
            $this->_newUser($item);
            unset($result);
        }
        unset($item);
    }

  /** creates a new user - internal, do not use -> use method save.
   *
   * @param object cs_item user_item the user
   */
  public function _newUser($item)
  {
      $current_datetime = getCurrentDateTimeInMySQL();
      $query = 'INSERT INTO '.$this->addDatabasePrefix('user').' SET '.
                'item_id="'.encode(AS_DB, $item->getItemID()).'", ';
      $context_id = $item->getContextID();
      $creator_id = $item->getCreatorID();
      if (empty($creator_id)) {
          $creator_id = $item->getItemID();
      }
      $query .= 'context_id="'.encode(AS_DB, $item->getContextID()).'", ';
      $query .= 'creator_id="'.encode(AS_DB, $creator_id).'",'.
                'creation_date="'.$current_datetime.'",'.
                'modification_date="'.$current_datetime.'",'.
                'user_id="'.encode(AS_DB, $item->getUserID()).'",'.
                'auth_source="'.encode(AS_DB, $item->getAuthSource()).'",'.
                'status="'.encode(AS_DB, $item->getStatus()).'",'.
                'firstname="'.encode(AS_DB, $item->getFirstName()).'",'.
                'lastname="'.encode(AS_DB, $item->getLastName()).'",'.
                'email="'.encode(AS_DB, $item->getEmail()).'",'.
                'city="'.encode(AS_DB, $item->getCity()).'",'.
                'visible="'.encode(AS_DB, $item->getVisible()).'",'.
                'description="'.encode(AS_DB, $item->getDescription()).'",'.
                'extras="'.encode(AS_DB, serialize($item->getExtraInformation())).'",'.
                'expire_date=NULL';

      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
          trigger_error('Problems insert new user item.', E_USER_ERROR);
      } else {
          unset($result);
      }
  }

    /** updates a new user - internal, do not use -> use method save
     * this method sets the creator id to the item id for new user at the portal.
     *
     * @param object cs_item user_item the user
     */
    public function _setCreatorID2ItemID($item)
    {
        $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET '.
                 'creator_id="'.encode(AS_DB, $item->getItemID()).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $item->getItemID()).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems set creator id to item id.', E_USER_WARNING);
        } else {
            unset($result);

            return true;
        }
    }

    /**  delete a user item.
     *
     * @param cs_user_item the user item to be deleted
     */
    public function delete(int $itemId): void
    {
        global $symfonyContainer;

        $user_item = $this->getItem($itemId);
        if ($this->_environment->inPortal()) {
            if (isset($user_item)
                 and !empty($user_item)
                 and $user_item->getContextID() == $this->_environment->getCurrentContextID()
            ) {
                // fire an AccountDeletedEvent (which will e.g. trigger deletion of the user's saved searches)
                /** @var EventDispatcher $eventDispatcher */
                $eventDispatcher = $symfonyContainer->get('event_dispatcher');

                $accountDeletedEvent = new \App\Event\AccountDeletedEvent($user_item);
                $eventDispatcher->dispatch($accountDeletedEvent, \App\Event\AccountDeletedEvent::class);

                // delete private room - part I
                $private_room_manager = $this->_environment->getPrivateRoomManager();
                $own_room = $private_room_manager->getRelatedOwnRoomForUser($user_item, $this->_environment->getCurrentPortalID());
                if (isset($own_room) and !empty($own_room)) {
                    $room_id = $own_room->getItemID();
                    if (!empty($room_id)) {
                        $delete_own_room = true;
                    } else {
                        $delete_own_room = false;
                    }
                }

                // delete related user in project rooms and community rooms and private room
                $user_list = $user_item->getRelatedUserList();
                if (!$user_list->isEmpty()) {
                    $u_item = $user_list->getFirst();
                    while ($u_item) {
                        $u_item->delete();
                        $u_item = $user_list->getNext();
                    }
                }

                // delete private room - part II
                if (isset($delete_own_room) and $delete_own_room) {
                    $own_room->delete();
                }
            }
        } elseif ($this->_environment->inProjectRoom()) {
            if (isset($user_item)
                 and !empty($user_item)
                 and $user_item->getContextID() == $this->_environment->getCurrentContextID()
            ) {
                // delete related user in group rooms
                if ($this->_environment->getCurrentPortalItem()->withGrouproomFunctions()) {
                    // get all grouprooms of this user
                    $grouproom_manager = $this->_environment->getGroupRoomManager();
                    $grouproom_list = $grouproom_manager->getUserRelatedGroupListForUser($user_item);

                    if (!$grouproom_list->isEmpty()) {
                        $grouproom_ids = [];
                        $grouproom = $grouproom_list->getFirst();
                        while ($grouproom) {
                            // is a group room of this project room?
                            $project_room = $grouproom->getLinkedProjectItem();
                            if (!empty($project_room)) {
                                $project_room_id = $project_room->getItemID();
                                if ($this->_environment->getCurrentContextID() == $project_room_id) {
                                    // add grouproom id to array of ids
                                    $grouproom_ids[] = $grouproom->getItemID();
                                }
                            }
                            $grouproom = $grouproom_list->getNext();
                        }

                        // delete related users
                        if (!empty($grouproom_ids)) {
                            $user_manager = $this->_environment->getUserManager();
                            $user_manager->resetLimits();
                            $user_manager->setContextArrayLimit($grouproom_ids);
                            $user_manager->setUserIDLimit($user_item->getUserID());
                            $user_manager->setAuthSourceLimit($user_item->getAuthSource());
                            $user_manager->select();
                            $user_list = $user_manager->get();
                            unset($user_manager);

                            if (!$user_list->isEmpty()) {
                                $user = $user_list->getFirst();
                                while ($user) {
                                    // delete user
                                    $user->delete();

                                    $user = $user_list->getNext();
                                }
                            }
                        }
                    }
                }
            }
        }

        // delete hash values
        /** @var HashRepository $hashRepository */
        $hashRepository = $symfonyContainer->get(HashRepository::class);
        $hash = $hashRepository->findByUserId($itemId);
        $hashRepository->deleteHash($hash);

        // delete all related items
        $user_item->deleteAllEntriesOfUser();

        // delete the user item itself
        $current_datetime = getCurrentDateTimeInMySQL();
        $currentUser = $this->_environment->getCurrentUserItem();

        $deleterId = (0 !== $currentUser->getItemID()) ? $currentUser->getItemID() : 0;

        $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET '.
                 'deletion_date="'.$current_datetime.'",'.
                 'deleter_id="'.encode(AS_DB, $deleterId).'"'.
                 ' WHERE item_id="'.encode(AS_DB, $itemId).'"';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            trigger_error('Problems deleting user.', E_USER_WARNING);
        } else {
            parent::delete($itemId);
        }
    }

  /** save a commsy item
   * this method saves a commsy item.
   *
   * @param cs_item
   */
  public function saveItem($item)
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
              unset($current_user);
              if (!empty($creator_id)) {
                  $item->setCreatorID($creator_id);
              } else {
                  $setCreatorID2ItemID = true;
              }
          }
          $this->_create($item);
          if ($setCreatorID2ItemID) {
              $this->_setCreatorID2ItemID($item);
          }

          $context_id = $item->getContextID();
          $portal_id = $this->_environment->getCurrentPortalID();
          if ($context_id == $portal_id) {
              // initiation of private room
              $room_manager = $this->_environment->getPrivateRoomManager();
              $room_item = $room_manager->getNewItem();
              $room_item->setCreatorItem($item);
              $room_item->setCreationDate(getCurrentDateTimeInMySQL());
              $room_item->setContextID($this->_environment->getCurrentPortalID());
              $room_item->setShowTitle();
              $room_item->setStatus(CS_ROOM_OPEN);
              $room_item->setTitle('PRIVATE_ROOM');
              $room_item->setCheckNewMemberAlways();
              $room_item->setClosedForGuests();
              $room_item->setContinuous();
              $room_item->save();
              unset($room_item);
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
                  unset($new_array);
                  unset($customized_room_id_array);
              }
              unset($private_room);
          }
      }

      // Add modifier to all users who ever edited this user
      if ($this->_link_modifier) {
          $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
          $mod_id = $item->getModificatorID();
          if (!empty($mod_id)
               and is_numeric($mod_id)
               and $mod_id > 99
          ) {
              $link_modifier_item_manager->markEdited($item->getItemID(), $mod_id);
          } else {
              $link_modifier_item_manager->markEdited($item->getItemID());
          }
      }
      unset($item);
  }

    public function setCreatorID2ItemID($item)
    {
        $this->_setCreatorID2ItemID($item);
    }

    public function moveRoom($roomMover)
    {
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE room_id = '.encode(AS_DB, $roomMover->getRoomId());
        $result = $this->_db_connector->performQuery($query);

        $user_ids_transformation = $roomMover->getTransformedUsers();
        foreach ($result as $row) {
            if (!$roomMover->isUserInRoom($row['creator_id'], $roomMover->getRoomId())) {
                $creator = $this->_environment->getCurrentUser();
                $creator_id = $creator->getItemId();
                unset($creator);
            }
            if (!empty($row['deleter_id']) and !$roomMover->isUserInRoom($row['deleter_id'], $roomMover->getRoomId())) {
                $deleter = $this->_environment->getCurrentUser();
                $deleter_id = $deleter->getItemId();
                unset($deleter);
            }

            $updateQuery = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';

            $oldUserId = $row['user_id'];
            if (isset($user_ids_transformation[$oldUserId])) {
                $newUserId = $user_ids_transformation[$oldUserId];
            } else {
                $newUserId = $oldUserId;
            }
            $updateQuery .= " user_id = '".encode(AS_DB, $newUserId)."', ";

            if (isset($creator_id)) {
                $updateQuery .= " creator_id='".encode(AS_DB, $creator_id)."', ";
                unset($creator_id);
            }
            if (isset($deleter_id)) {
                $updateQuery .= " deleter_id='".encode(AS_DB, $deleter_id)."', ";
                unset($deleter_id);
            }

            $updateQuery .= ' context_id = '.encode(AS_DB, $roomMover->getRoomId());
            $updateQuery .= " WHERE user_id = '".encode(AS_DB, $oldUserId)."'";
            $updateQuery .= " AND context_id = '".encode(AS_DB, $roomMover->getOldRoomId())."'";
            $result2 = $this->_db_connector->performQuery($updateQuery);
            if (!isset($result2) or !$result2) {
                trigger_error('Problems user: move room.', E_USER_WARNING);
            } else {
                unset($result2);
            }
        }
        unset($result);
    }

    public function mergeAccount($account_new, $account_old)
    {
        // implemented in class cs_authentication
    }

     public function changeUserID(string $username, cs_user_item $userItem)
     {
         $room_manager = $this->_environment->getRoomManager();
         $room_list = $room_manager->getAllRelatedRoomListForUser($userItem);
         $room_item_ids = [];
         $room_item_ids[] = $this->_environment->getCurrentPortalID();
         if (!$room_list->isEmpty()) {
             $room_item = $room_list->getFirst();
             while ($room_item) {
                 $room_item_ids[] = $room_item->getItemID();
                 $room_item = $room_list->getNext();
             }
         }

         // private room
         $own_room = $userItem->getOwnRoom();
         if (isset($own_room)) {
             $room_item_ids[] = $own_room->getItemID();
             unset($own_room);
         }

         // user rooms
         $relatedUserrooms = $userItem->getRelatedUserroomsList();
         foreach ($relatedUserrooms as $userroom) {
             $room_item_ids[] = $userroom->getItemID();
         }

         $update = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';
         $update .= " user_id = '".encode(AS_DB, $username)."',";

         $update .= ' modifier_id=creator_id,';
         $update .= " modification_date='".getCurrentDateTimeInMySQL()."'";
         $update .= " WHERE user_id = '".encode(AS_DB, $userItem->getUserID())."' AND context_id IN (".implode(',',
             encode(AS_DB, $room_item_ids)).") AND auth_source='".encode(AS_DB,
                 $userItem->getAuthSource())."'";
         $result = $this->_db_connector->performQuery($update);
         if (!isset($result) or !$result) {
             trigger_error('Problems changing user id.', E_USER_WARNING);
             $success = false;
         } else {
             unset($result);
             $success = true;
         }

         return $success;
     }

    public function getCountAuthSourceOfRoom($context_id)
    {
        $retour = null;
        $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.auth_source) as number FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB, $context_id).'" and '.$this->addDatabasePrefix('user').'.deletion_date IS NULL and '.$this->addDatabasePrefix('user').'.auth_source > 0';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting users.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
            unset($result);
        }

        return $retour;
    }

    public function exists($user_id, $auth_source = '')
    {
        $retour = false;
        $this->setUserIDLimit($user_id);
        if (!empty($auth_source)) {
            $this->setAuthSourceLimit($auth_source);
        }
        $this->select();
        $count = $this->getCountAll();
        if (!empty($count) and $count > 0) {
            $retour = true;
        }

        return $retour;
    }

    // #########################################################
    // statistic functions
    // #########################################################

    public function getCountUsedAccounts($start, $end)
    {
        $retour = 0;

        $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.email) as number FROM '.$this->addDatabasePrefix('user').' WHERE';
        if (!empty($this->_context_array_limit)
             and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN ('.implode(',', encode(AS_DB, $this->_context_array_limit)).')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '".encode(AS_DB, $this->_room_limit)."'";
        }
        $query .= " and lastlogin > '".encode(AS_DB, $start)."' and creation_date < '".encode(AS_DB, $end)."'";
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

        $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.email) as number FROM '.$this->addDatabasePrefix('user').' WHERE';
        if (!empty($this->_context_array_limit)
             and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN ('.implode(',', encode(AS_DB, $this->_context_array_limit)).')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '".encode(AS_DB, $this->_room_limit)."'";
        }
        $query .= " and status >= 2 and (deletion_date IS NULL or deletion_date > '".encode(AS_DB, $end)."') and creation_date < '".encode(AS_DB, $end)."'";
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

        $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.email) as number FROM '.$this->addDatabasePrefix('user').' WHERE';
        if (!empty($this->_context_array_limit)
             and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN ('.implode(',', encode(AS_DB, $this->_context_array_limit)).')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '".encode(AS_DB, $this->_room_limit)."'";
        }
        $query .= ' and '.$this->addDatabasePrefix('user').".creation_date < '".encode(AS_DB, $end)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting all accounts.', E_USER_WARNING);
        } else {
            foreach ($result as $rs) {
                $retour = $rs['number'];
            }
            unset($result);
        }

        return $retour;
    }

    public function getCountPlugin($plugin, $start, $end)
    {
        $retour = 0;

        $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.email,'.$this->addDatabasePrefix($this->_db_table).'.extras FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE';
        if (!empty($this->_context_array_limit)
             and (is_countable($this->_context_array_limit) ? count($this->_context_array_limit) : 0) > 0
        ) {
            $query .= ' context_id IN ('.implode(',', encode(AS_DB, $this->_context_array_limit)).')';
        } elseif (!empty($this->_room_limit)) {
            $query .= " context_id = '".encode(AS_DB, $this->_room_limit)."'";
        }
        $query .= ' and '.$this->addDatabasePrefix($this->_db_table).".extras LIKE '%LASTLOGIN_".mb_strtoupper((string) $plugin)."%' and user.creation_date < '".encode(AS_DB, $end)."'";
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            trigger_error('Problems counting all accounts.', E_USER_WARNING);
        } else {
            $retour_array = [];
            foreach ($result as $rs) {
                $extra_array = [];
                if (!empty($rs['extras'])) {
                    $extra_array = unserialize($rs['extras']);
                    if (!empty($extra_array['LASTLOGIN_'.mb_strtoupper((string) $plugin)])
                         and $extra_array['LASTLOGIN_'.mb_strtoupper((string) $plugin)] > $start
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

     // ###################################################
     // archive method
     // ###################################################

     public function getLastUsedDateOfRoom($room_id)
     {
         $retour = '';
         if (!empty($room_id)) {
             $query = 'SELECT lastlogin FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id = '.$room_id.' AND lastlogin IS NOT NULL ORDER BY lastlogin DESC LIMIT 0,1';
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result)) {
                 trigger_error('Problems getting last used date of this room: '.$room_id, E_USER_WARNING);
             } elseif (!empty($result[0]['lastlogin'])) {
                 $retour = $result[0]['lastlogin'];
             }
         }

         return $retour;
     }

     public function getUserPasswordExpiredByContextID($cid)
     {
         $user_array = [];
         $current_date = getCurrentDateTimeInMySQL();
         $user = null;
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.expire_date IS NOT NULL AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('user').".context_id = '".encode(AS_DB, $cid)."' AND ".$this->addDatabasePrefix('user').".expire_date  <= '".encode(AS_DB, $current_date)."'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $user_array[] = $this->_buildItem($rs);
             }
             unset($result);
             unset($query);
         }

         return $user_array;
     }

     public function getCountUserPasswordExpiredByContextID($cid)
     {
         $retour = 0;
         $date = getCurrentDateTimeInMySQL();
         $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.item_id) as number FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.expire_date IS NOT NULL AND '.$this->addDatabasePrefix('user').".context_id = '".encode(AS_DB, $cid)."' AND ".$this->addDatabasePrefix('user').".expire_date  <= '".encode(AS_DB, $date)."'";
         $query .= ' and deletion_date IS NULL';
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

     public function getCountUserPasswordExpiredSoonByContextID($cid, $portal_item = null)
     {
         $retour = 0;
         $days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();
         if (isset($days_before_expiring_sendmail)) {
             $date = getCurrentDateTimePlusDaysInMySQL($days_before_expiring_sendmail, true);
         } else {
             $date = getCurrentDateTimePlusDaysInMySQL('14', true);
         }
         $now = getCurrentDateTimeInMySQL();
         $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.item_id) as number FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.expire_date IS NOT NULL AND deletion_date IS NULL AND '.$this->addDatabasePrefix('user').".context_id = '".encode(AS_DB, $cid)."' AND ".$this->addDatabasePrefix('user').".expire_date BETWEEN '".encode(AS_DB, $now)."' AND '".encode(AS_DB, $date)."'";
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

     public function getUserPasswordExpiredSoonByContextID($cid, $portal_item = null)
     {
         $user_array = [];
         $days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();

         if (isset($days_before_expiring_sendmail)) {
             $date = getCurrentDateTimePlusDaysInMySQL($days_before_expiring_sendmail);
         } else {
             $date = getCurrentDateTimePlusDaysInMySQL('14');
         }
         $now = getCurrentDateTimeInMySQL();
         $user = null;
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.expire_date IS NOT NULL AND '.$this->addDatabasePrefix('user').".context_id = '".encode(AS_DB, $cid)."' AND ".$this->addDatabasePrefix('user').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('user').".expire_date BETWEEN '".encode(AS_DB, $now)."' AND '".encode(AS_DB, $date)."'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $user_array[] = $this->_buildItem($rs);
             }
             unset($result);
             unset($query);
         }

         return $user_array;
     }

     public function getUserTempLoginExpired()
     {
         $user = null;
         $user_array = [];
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').".status = '3' AND ".$this->addDatabasePrefix('user').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('user').".extras LIKE '%LOGIN_AS_TMSP%'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $user_array[] = $this->_buildItem($rs);
             }
             unset($result);
             unset($query);
         }

         return $user_array;
     }

     public function getUserLastLoginLaterAs($date, $cid, $status = 2)
     {
         $user = null;
         $user_array = [];
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').".lastlogin <= '".encode(AS_DB, $date)."' AND ".$this->addDatabasePrefix('user').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('user').'.status >= '.encode(AS_DB, $status).' AND '.$this->addDatabasePrefix('user').".context_id = '".encode(AS_DB, $cid)."'";
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $user_array[] = $this->_buildItem($rs);
             }
             unset($result);
             unset($query);
         }

         return $user_array;
     }

     public function getAllUserItemArray($uid)
     {
         $user = null;
         $user_array = [];
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').".user_id = '".encode(AS_DB, $uid)."' AND ".$this->addDatabasePrefix('user').'.deletion_date IS NULL';
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
             trigger_error('Problems selecting list of '.$this->_type.' items.', E_USER_WARNING);
         } else {
             foreach ($result as $rs) {
                 $user_array[] = $this->_buildItem($rs);
             }
             unset($result);
             unset($query);
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
     public function getNewestItems($contextIds, $limits, $size, DateTime $newerThen = null, $excludedIds = [])
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
