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

/** cs_list is needed for storage of the commsy items.
 */
include_once 'classes/cs_list.php';

/** upper class of the room manager.
 */
include_once 'classes/cs_room2_manager.php';

/** date functions are needed for method _newVersion().
 */
include_once 'functions/date_functions.php';

/** text functions are needed for ???
 */
include_once 'functions/text_functions.php';

/** misc functions are needed for extras field in database table.
 */
include_once 'functions/misc_functions.php';

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community".
 */
class cs_community_manager extends cs_room2_manager
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

    public $_template_limit = null;

    /** constructor: cs_community_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        $this->_db_table = 'room';
        $this->_room_type = CS_COMMUNITY_TYPE;
        cs_context_manager::__construct($environment);
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
        $this->_template_limit = null;
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

      public function getRelatedCommunityRooms(cs_user_item $userItem, $contextId)
      {
          return $this->getRelatedContextListForUserInt($userItem->getUserID(), $userItem->getAuthSource(), $contextId);
      }

      public function getRelatedCommunityListForUser($user_item, bool $withExtras = true)
      {
          return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(),
              $this->_environment->getCurrentPortalID(), false, false, $withExtras);
      }

     public function getRelatedCommunityListForUserAllUserStatus($user_item)
     {
         $this->_all_status_limit = true;

         return $this->getRelatedCommunityListForUser($user_item);
     }

     public function getUserRelatedCommunityListForUser($user_item, bool $withExtras = true)
     {
         return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), false, true, $withExtras);
     }

    /** select communities limited by limits
     * this method returns a list (cs_list) of communities within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
     */
    public function _performQuery($mode = 'select')
    {
        if ('count' == $mode) {
            $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
        } elseif ('id_array' == $mode) {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
        } else {
            $query = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
        }

        $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
        $query .= ' WHERE 1';

        // insert limits into the select statement
        if (true == $this->_delete_limit) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
        }
        if (isset($this->_status_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB, $this->_status_limit).'"';
        }
        if (isset($this->_room_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
        }
        if (isset($this->_room_type)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB, $this->_room_type).'"';
        }

        // id_array_limit
        if (!empty($this->_id_array_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.implode(', ', encode(AS_DB, $this->_id_array_limit)).')';
        }

        // template
        if (isset($this->_template_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.template = "'.encode(AS_DB, $this->_template_limit).'"';
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
        // _lastlogin_older_limit
        if (!empty($this->_lastlogin_older_limit)) {
            $query .= ' AND ( '.$this->addDatabasePrefix($this->_db_table).'.lastlogin < "'.encode(AS_DB, $this->_lastlogin_older_limit).'"';
            $query .= ' OR ('.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date < "'.encode(AS_DB, $this->_lastlogin_older_limit).'" ) )';
        }

        // lastlogin_newer_limit
        if (!empty($this->_lastlogin_newer_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin >= "'.encode(AS_DB, $this->_lastlogin_newer_limit).'"';
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
            $query .= ' ORDER BY title, modification_date DESC';
        }

        if ('select' == $mode) {
            if (isset($this->_interval_limit) and isset($this->_from_limit)) {
                $query .= ' LIMIT '.encode(AS_DB, $this->_from_limit).', '.encode(AS_DB, $this->_interval_limit);
            }
        }

        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
            include_once 'functions/error_functions.php';
            trigger_error('Problems selecting '.$this->_db_table.' items.', E_USER_ERROR);
        } else {
            return $result;
        }
    }

     public function getSortedItemList($id_array, $sortBy)
     {
         if (empty($id_array)) {
             return new cs_list();
         } else {
             $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ("'.implode('", "', encode(AS_DB, $id_array)).'") AND '.$this->addDatabasePrefix($this->_db_table).'.type LIKE "community"';
             $query .= ' ORDER BY '.$sortBy;
             $result = $this->_db_connector->performQuery($query);
             if (!isset($result)) {
                 include_once 'functions/error_functions.php';
                 trigger_error('Problems selecting list of '.$this->_room_type.' items.', E_USER_WARNING);
             } else {
                 $list = new cs_list();
                 // filter items with highest version_id, doing this in MySQL would be too expensive
                 foreach ($result as $rs) {
                     $list->add($this->_buildItem($rs));
                 }
             }

             return $list;
         }
     }

     public function saveActivityPoints(cs_item $item)
     {
         parent::saveActivityPoints($item);

         global $symfonyContainer;
         /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
         /** @noinspection MissingService */
         $entityManager = $symfonyContainer->get('doctrine.orm.entity_manager');

         $portal = $entityManager->getRepository(\App\Entity\Portal::class)->find($item->getContextId());
         $extras = $portal->getExtras();
         if (isset($extras['MAX_ROOM_ACTIVITY'])) {
             if ($item->getActivityPoints() > $extras['MAX_ROOM_ACTIVITY']) {
                 $extras['MAX_ROOM_ACTIVITY'] = $item->getActivityPoints();
                 $portal->setExtras($extras);
                 $entityManager->persist($portal);
                 $entityManager->flush();
             }
         }
     }
}
