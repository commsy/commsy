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

/** class for database connection to the database table "project"
 * this class implements a database manager for the table "project".
 */
class cs_project_manager extends cs_room2_manager
{
    /**
     * integer - containing the age of project as a limit.
     */
    public $_age_limit = null;

    /**
     * integer - containing a start point for the select project.
     */
    public $_from_limit = null;

    /**
     * integer - containing how many project the select statement should get.
     */
    public $_interval_limit = null;

    /**
     * string - enthält die USERID eines Benutzers.
     */
    public $_user_id_limit = null;

    public $_community_room_limit = null;

    public $_time_limit = null;

    public $_template_limit = null;

    /** constructor: cs_project_manager
     * the only available constructor, initial values for internal variables.
     *
     * @param object cs_environment the environment
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_db_table = 'room';
        $this->_room_type = CS_PROJECT_TYPE;
    }

  /** reset limits
   * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class.
   */
  public function resetLimits()
  {
      parent::resetLimits();
      $this->_age_limit = null;
      $this->_from_limit = null;
      $this->_interval_limit = null;
      $this->_user_id_limit = null;
      $this->_community_room_limit = null;
      $this->_time_limit = null;
      $this->_template_limit = null;
  }

  /** set age limit
   * this method sets an age limit for project.
   *
   * @param int limit age limit for project
   */
  public function setAgeLimit($limit)
  {
      $this->_age_limit = (int) $limit;
  }

  /** set interval limit
   * this method sets a interval limit.
   *
   * @param int from     from limit for selected project
   * @param int interval interval limit for selected project
   */
  public function setIntervalLimit($from, $interval)
  {
      $this->_interval_limit = (int) $interval;
      $this->_from_limit = (int) $from;
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

  public function setGetAllRoomLimit()
  {
      $this->_all_room_limit = true;
  }

   public function setCommunityroomLimit($value)
   {
       $this->_community_room_limit = (int) $value;
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

  /** select project limited by limits
   * this method returns a list (cs_list) of projects within the database limited by the limits. the select statement is a bit tricky, see source code for further information.
   */
  public function _performQuery($mode = 'select')
  {
      if (isset($this->_id_array_limit)
           and empty($this->_id_array_limit)
      ) {
          return [];
      }

      $query = 'SELECT DISTINCT';
//     if ( isset($this->_search_limit) ) {
//        $query .= ' DISTINCT';
//     }
      if ('count' == $mode) {
          $query .= ' count( DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id) AS count';
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
      if (isset($this->_sort_order) and ('modificator' == $this->_sort_order || 'modificator_rev' == $this->_sort_order)) {
          $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS modificator ON (modificator.item_id='.$this->addDatabasePrefix($this->_db_table).'.modifier_id)';
      }
      if (isset($this->_community_room_limit)) {
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l31.second_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l32.first_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
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
          // ###########################################
          // FLAG: group room
          // sinnfrei? 15.12.2009 ij
          // ##################BEGIN####################
          // $current_portal = $this->_environment->getCurrentPortalItem();
          // if ( !isset($current_portal) and isset($this->_room_limit) ) {
          //   $portal_manager = $this->_environment->getPortalManager();
          //   $current_portal = $portal_manager->getItem($this->_room_limit);
          // }
          // if ( $this->_room_type == CS_PROJECT_TYPE
         //     and isset($current_portal)
         //     and $current_portal->withGroupRoomFunctions()
          //   ) {
          //   $query .= ' AND ('.$this->_db_table.'.type = "'.encode(AS_DB,$this->_room_type).'" or '.$this->_db_table.'.type = "'.CS_GROUPROOM_TYPE.'")';
          // } else {
          // ###################END#####################
          // FLAG: group room
          // ###########################################
          $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB, $this->_room_type).'"';
          // ###########################################
          // FLAG: group room
          // #################BEGIN####################
          // }
          // ##################END######################
          // FLAG: group room
          // ###########################################
      }
      if (isset($this->_community_room_limit) and isset($this->_room_limit)) {
          if (-1 == $this->_community_room_limit) {
              $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
              $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
              $query .= ' AND l31.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
              $query .= ' AND l32.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
          } else {
              $query .= ' AND (';
              $query .= '(';
              $query .= '(l31.first_item_id = "'.encode(AS_DB, $this->_community_room_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_community_room_limit).'")';
              $query .= ' AND l31.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
              $query .= ')';
              $query .= ' OR ';
              $query .= '(';
              $query .= '(l32.first_item_id = "'.encode(AS_DB, $this->_community_room_limit).'" OR l32.second_item_id = "'.encode(AS_DB, $this->_community_room_limit).'")';
              $query .= ')';
              $query .= ' AND l32.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
              $query .= ')';
          }
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
              $query .= ' AND (l21.first_item_id IS NULL AND l21.second_item_id IS NULL)';
              $query .= ' AND (l22.first_item_id IS NULL AND l22.second_item_id IS NULL)';
          } else {
              $query .= ' AND ((l21.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l21.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'")';
              $query .= ' OR (l22.second_item_id = "'.encode(AS_DB, $this->_institution_limit).'" OR l22.first_item_id = "'.encode(AS_DB, $this->_institution_limit).'"))';
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

      // archive
      // lastlogin_limit
      if (!empty($this->_lastlogin_limit)) {
          if ('NULL' == $this->_lastlogin_limit) {
              $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL';
          } else {
              $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin = '.encode(AS_DB, $this->_lastlogin_limit);
          }
      }
      // lastlogin_older_limit
      if (!empty($this->_lastlogin_older_limit)) {
          $query .= ' AND ( '.$this->addDatabasePrefix($this->_db_table).'.lastlogin < "'.encode(AS_DB, $this->_lastlogin_older_limit).'"';
          $query .= ' OR ('.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date < "'.encode(AS_DB, $this->_lastlogin_older_limit).'" ) )';
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
          } elseif ('date' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
          } elseif ('date_rev' == $this->_sort_order) {
              $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
          } elseif ('modificator' == $this->_sort_order) {
              $query .= ' ORDER BY modificator.lastname, modificator.firstname, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
          } elseif ('modificator_rev' == $this->_sort_order) {
              $query .= ' ORDER BY modificator.lastname DESC, modificator.firstname DESC, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
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
          $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ("'.implode('", "', encode(AS_DB, $id_array)).'") AND '.$this->addDatabasePrefix($this->_db_table).'.type LIKE "project"';
          $query .= ' ORDER BY '.encode(AS_DB, $sortBy);
          $result = $this->_db_connector->performQuery($query);
          if (!isset($result)) {
              trigger_error('Problems selecting list of '.$this->_room_type.' items from query: "'.$query.'"', E_USER_WARNING);
          } else {
              $list = new cs_list();
              // filter items with highest version_id, doing this in MySQL would be too expensive
              if (!empty($result)) {
                  foreach ($result as $rs) {
                      $list->add($this->_buildItem($rs));
                  }
              }
          }

          return $list;
      }
  }

    public function getRelatedProjectRooms($userItem, $contextId)
    {
        return $this->getRelatedContextListForUserInt($userItem->getUserID(), $userItem->getAuthSource(), $contextId);
    }

    public function getRelatedProjectListForUser(cs_user_item $user, $contextId = null, bool $withExtras = true)
    {
        if (!$contextId) {
            $contextId = $this->_environment->getCurrentPortalID();
        }

        return $this->getRelatedContextListForUserInt($user->getUserID(), $user->getAuthSource(), $contextId, false, false, $withExtras);
    }

   public function getUserRelatedProjectListForUser($user_item, bool $withExtras = true)
   {
       return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), false, true, $withExtras);
   }

   public function getRelatedProjectListForUserSortByTime($user_item)
   {
       return $this->_getRelatedContextListForUserSortByTime($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID());
   }

   public function getRelatedProjectListForUserForMyArea($user_item)
   {
       return $this->getRelatedContextListForUserInt($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), true);
   }

   public function getRelatedProjectListForUserSortByTimeForMyArea($user_item)
   {
       return $this->_getRelatedContextListForUserSortByTime($user_item->getUserID(), $user_item->getAuthSource(), $this->_environment->getCurrentPortalID(), true);
   }

   public function getRelatedProjectListForUserAllUserStatus($user_item, $context_id)
   {
       $this->_all_status_limit = true;

       return $this->getRelatedProjectListForUser($user_item, $context_id);
   }

   /**
    * documentation TBD.
    */
   public function getItemList($id_array)
   {
       return $this->_getItemList(CS_ROOM_TYPE, $id_array);
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
  }

   // #######################################################
   // statistic functions
   // #######################################################

   public function getCountProjects($start, $end)
   {
       $retour = 0;

       $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) as number FROM '.$this->addDatabasePrefix($this->_db_table);
       if (isset($this->_community_room_limit)) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l31.second_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l32.first_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
       }
       $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB, $this->_room_type).'" AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB, $this->_room_limit).'" AND (('.$this->addDatabasePrefix($this->_db_table).'.creation_date > "'.encode(AS_DB, $start).'" AND '.$this->addDatabasePrefix($this->_db_table).'.creation_date < "'.encode(AS_DB, $end).'") OR ('.$this->addDatabasePrefix($this->_db_table).'.modification_date > "'.encode(AS_DB, $start).'" AND '.$this->addDatabasePrefix($this->_db_table).'.modification_date < "'.encode(AS_DB, $end).'"))';

       if (isset($this->_community_room_limit) and isset($this->_room_limit)) {
           if (-1 == $this->_community_room_limit) {
               $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
               $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
               $query .= ' AND l31.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
               $query .= ' AND l32.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
           } else {
               $query .= ' AND (';
               $query .= '(';
               $query .= '(l31.first_item_id = "'.encode(AS_DB, $this->_community_room_limit).'" OR l31.second_item_id = "'.encode(AS_DB, $this->_community_room_limit).'")';
               $query .= ' AND l31.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
               $query .= ')';
               $query .= ' OR ';
               $query .= '(';
               $query .= '(l32.first_item_id = "'.encode(AS_DB, $this->_community_room_limit).'" OR l32.second_item_id = "'.encode(AS_DB, $this->_community_room_limit).'")';
               $query .= ')';
               $query .= ' AND l32.context_id = "'.encode(AS_DB, $this->_room_limit).'"';
               $query .= ')';
           }
       }

       $result = $this->_db_connector->performQuery($query);
       if (!isset($result)) {
           trigger_error('Problems counting all '.$this->_room_type.' from query: "'.$query.'"', E_USER_WARNING);
       } else {
           foreach ($result as $rs) {
               $retour = $rs['number'];
           }
       }

       return $retour;
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

   public function getRoomsByTitle($string, $portalid)
   {
       $list = null;
       if (empty($string)) {
           return new cs_list();
       } else {
           $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.deletion_date IS NULL AND
   				 '.$this->addDatabasePrefix($this->_db_table).'.context_id = '.$portalid.' AND
   				 		 ('.$this->addDatabasePrefix($this->_db_table).'.title LIKE "%'.$string.'%" OR
   				 		 		 '.$this->addDatabasePrefix($this->_db_table).'.item_id LIKE "%'.$string.'%") LIMIT 20';
//    		$query .= " ORDER BY ".encode(AS_DB,$sortBy);
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result)) {
               trigger_error('Problems selecting list of '.$this->_room_type.' items from query: "'.$query.'"', E_USER_WARNING);
           } else {
               $list = new cs_list();
               // filter items with highest version_id, doing this in MySQL would be too expensive
               if (!empty($result)) {
                   foreach ($result as $rs) {
                       $list->add($this->_buildItem($rs));
                   }
               }
           }

           return $list;
       }
   }
}
