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

/** misc functions are needed for extras field in database table
 */
include_once('functions/misc_functions.php');

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community"
 */
class cs_myroom_manager extends cs_context_manager {

  /**
   * integer - containing the age of community as a limit
   */
  var $_age_limit = NULL;

  /**
   * integer - containing a start point for the select community
   */
  var $_from_limit = NULL;

  /**
   * integer - containing how many communities the select statement should get
   */
  var $_interval_limit = NULL;

  /**
   * string - containing an order limit for the select community
   */
  var $_order = NULL;

  var $_time_limit = NULL;

  /** constructor: cs_community_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     $this->_db_table = 'room';
     $this->_room_type = CS_PRIVATEROOM_TYPE;
     cs_context_manager::__construct($environment);
  }

  /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_order = NULL;
     $this->_time_limit = NULL;
     $this->_user_id_limit = NULL;
     $this->_room_type = CS_PRIVATEROOM_TYPE;
  }

  /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected communities
    * @param integer interval interval limit for selected communities
    */
  function setIntervalLimit ($from, $interval) {
     $this->_interval_limit = (integer)$interval;
     $this->_from_limit = (integer)$from;
  }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected communities
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

  function setTypeLimit($limit){
     $this->_room_type = (string)$limit;
  }

   function getRelatedCommunityListForUser ($user_item) {
      return $this->_getRelatedContextListForUser($user_item->getUserID(),$user_item->getAuthSource(),$this->_environment->getCurrentPortalID());
   }

  /** set time limit
    * this method sets an clock pulses limit for rooms
    *
    * @param integer limit time limit for rooms (item id of clock pulses)
    */
  function setTimeLimit ($limit) {
     $this->_time_limit = $limit;
  }

  /** set user id limit
    *
    * @param string limit userid limit for selected project rooms
    */
  function setUserIDLimit ($limit) {
     $this->_user_id_limit = (string)$limit;
  }

  function setAuthSourceLimit ($limit) {
     $this->_auth_source_limit = (int)$limit;
  }

  /** select communities limited by limits
    * this method returns a list (cs_list) of communities within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->addDatabasePrefix($this->_db_table).'.item_id) AS count';
     } elseif ($mode == 'id_array') {
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
     $query .= ' WHERE 1';
     if (isset($this->_user_id_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$this->_user_id_limit).'"';
     }
     if (isset($this->_auth_source_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,$this->_auth_source_limit).'"';
     }
     // insert limits into the select statement
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
     }
     if (isset($this->_status_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
     }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if (isset($this->_room_type)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'"';
     }

     if (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'creation_date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'creator') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
        } elseif ($this->_order == 'activity') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'activity_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
     }

     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
        }
     }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',E_USER_ERROR);
     } else {
        return $result;
     }
  }

   function getRelatedContextListForUser ($user_id, $auth_source, $context_id, $mode='select') {
      $list = new cs_list();
      $query  = 'SELECT DISTINCT';
     if ($mode == 'count') {
        $query .= ' count(DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.item_id) as count';
     } elseif ($mode == 'id_array') {
        $query .= ' '.$this->addDatabasePrefix($this->_db_table).'.item_id';
     } else {
        $query .= ' '.$this->addDatabasePrefix($this->_db_table).'.*';
     }
      $query .= ' FROM '.$this->addDatabasePrefix('room');

      $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id
                  AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL
                  AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$user_id).'"
                  AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,$auth_source).'"';
      if (!$this->_all_room_limit) {
         $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
      } else {
         $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "1"';
      }

     if ( !empty($this->_search_array) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('user').' AS user2 ON user2.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND user2.deletion_date IS NULL AND user2.is_contact="1"';
     }

      if ( isset($this->_topic_limit) ) {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l41.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l42.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
      }

    // time (clock pulses)
    if ( isset($this->_time_limit) ) {
       if ($this->_time_limit != -1) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS time_label ON room_time.to_item_id=time_label.item_id AND time_label.type="time"';
       } else {
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS room_time ON room_time.from_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND room_time.link_type="in_time"';
       }
     }
      $query .= ' WHERE 1';
      if ( isset($this->_topic_limit) ){
         if($this->_topic_limit == -1){
            $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
            $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l41.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l41.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
            $query .= ' OR (l42.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l42.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'"))';
         }
      }
     if (isset($this->_status_limit)) {
        if ($this->_status_limit != 5) {
           $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
        } elseif ($this->_status_limit == 5) {
           $query .= ' AND ('.$this->addDatabasePrefix($this->_db_table).'.status = "1" OR '.$this->addDatabasePrefix($this->_db_table).'.status = "2")';
        }
     } else {
        $query .= ' AND ('.$this->addDatabasePrefix($this->_db_table).'.status = "'.CS_ROOM_OPEN.'" OR '.$this->addDatabasePrefix($this->_db_table).'.status = "'.CS_ROOM_CLOSED.'" OR '.$this->addDatabasePrefix($this->_db_table).'.status = "'.CS_ROOM_LOCK.'")';
     }

      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
       $field_array = array('TRIM(CONCAT(user2.firstname," ",user2.lastname))',''.$this->addDatabasePrefix($this->_db_table).'.title');
       $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
       $query .= $search_limit_query_code;
         $query .= ' )';
      }

      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id="'.encode(AS_DB,$context_id).'"';
      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type !="privateroom"';
      if (isset($this->_room_type) and $this->_room_type !=CS_PRIVATEROOM_TYPE ) {
         $query .= ' AND  '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'"';
      }

      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      }
    // time (clock pulses)
    if (isset($this->_time_limit)) {
       if ($this->_time_limit != -1) {
         $query .= ' AND time_label.item_id = "'.encode(AS_DB,$this->_time_limit).'"';
       } else {
         $query .= ' AND room_time.to_item_id IS NULL';
       }
     }
     if (isset($this->_sort_order)) {
        if ($this->_sort_order == 'title_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title DESC';
        } elseif ($this->_sort_order == 'activity') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC,'.$this->addDatabasePrefix($this->_db_table).'.title';
        } elseif ($this->_sort_order == 'activity_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC,'.$this->addDatabasePrefix($this->_db_table).'.title';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        }
     }
     elseif (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'creation_date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.creation_date ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'creator') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix($this->_db_table).'.modification_date DESC';
        } elseif ($this->_order == 'activity') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity ASC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } elseif ($this->_order == 'activity_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.activity DESC, '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.modification_date ASC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.title ASC';
     }
     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
        }
     }

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',E_USER_WARNING);
      } else {
         if ($mode == 'select'){
            foreach ($result as $query_result) {
               $item = $this->_buildItem($query_result);
               $list->add($item);
               unset($item);
            }
            $retour = $list;
            unset($list);
         } else {
            $id_array = array();
            foreach ($result as $query_result) {
               $id_array[] = $query_result['item_id'];
            }
            $retour = $id_array;
         }
         unset($result);
      }
      return $retour;
   }
}
?>