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
class cs_room_manager extends cs_context_manager {

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

  /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function cs_room_manager ($environment) {
     $this->cs_context_manager($environment);
     $this->_db_table = CS_ROOM_TYPE;
     $this->_room_type = '';
  }

  /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
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

  function setRoomTypeLimit ($value) {
     $this->_room_type = $value;
  }

  /** set user id limit
    *
    * @param string limit userid limit for selected rooms
    */
  function setUserIDLimit ($limit) {
     $this->_user_id_limit = (string)$limit;
  }

  function setAuthSourceLimit ($limit) {
     $this->_auth_source_limit = (int)$limit;
  }

  function setGetAllRoomLimit () {
     $this->_all_room_limit = true;
  }

  function setDeletedLimit () {
     $this->_deleted_limit = true;
  }

  /** set time limit
    * this method sets an clock pulses limit for rooms
    *
    * @param integer limit time limit for rooms (item id of clock pulses)
    */
  function setTimeLimit ($limit) {
     $this->_time_limit = $limit;
  }

  function setContinuousLimit () {
    $this->_continuous_limit = 1;
  }

  function setNotContinuousLimit () {
    $this->_continuous_limit = -1;
  }

  function unsetContinuousLimit () {
    $this->_continuous_limit = NULL;
  }

  function setTemplateLimit () {
    $this->_template_limit = 1;
  }

  function setNotTemplateLimit () {
    $this->_template_limit = -1;
  }

  function unsetTemplateLimit () {
    $this->_template_limit = NULL;
  }

  public function setLogArchiveLimit () {
     $this->_logarchive_limit = array();
     $this->_logarchive_limit[] = 'LOGARCHIVE";i:1';
     $this->_logarchive_limit[] = 'LOGARCHIVE";s:1';
  }

  /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected communities
    */
  function setOrder ($limit) {
     $this->_order = (string)$limit;
  }

  /** select rooms limited by limits
    * this method returns a list (cs_list) of rooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count(DISTINCT '.$this->_db_table.'.item_id) as count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT DISTINCT '.$this->_db_table.'.item_id';
     } else {
        $query = 'SELECT DISTINCT '.$this->_db_table.'.*';
     }

     $query .= ' FROM '.$this->_db_table;
     // user id limit
     if (isset($this->_user_id_limit)) {
        $query .= ' LEFT JOIN user ON user.context_id='.$this->_db_table.'.item_id AND user.deletion_date IS NULL';
        if (!$this->_all_room_limit) {
           $query .= ' AND user.status >= "2"';
        }
     }
     if (isset($this->_search_array) AND !empty($this->_search_array)) {
        $query .= ' LEFT JOIN user AS user2 ON user2.context_id='.$this->_db_table.'.item_id AND user2.deletion_date IS NULL AND user2.is_contact="1"';
     }

    // time (clock pulses)
    if ( isset($this->_time_limit) ) {
       if ($this->_time_limit != -1) {
         $query .= ' INNER JOIN links AS room_time ON room_time.from_item_id=room.item_id AND room_time.link_type="in_time"';
         $query .= ' INNER JOIN labels AS time_label ON room_time.to_item_id=time_label.item_id AND time_label.type="time"';
       } else {
         $query .= ' LEFT JOIN links AS room_time ON room_time.from_item_id=room.item_id AND room_time.link_type="in_time"';
       }
     }

     $query .= ' WHERE 1';

     if ( !empty($this->_id_array_limit) ) {
        $query .= ' AND '.$this->_db_table.'.item_id IN ('.implode(',',$this->_id_array_limit).')';
     }

     if (!empty($this->_room_type)) {
        $query .= ' AND '.$this->_db_table.'.type = "'.encode(AS_DB,$this->_room_type).'"';
     }

     ###################################
     # FLAG: group room
     ###################################
     if ( empty($this->_room_type) or $this->_room_type != CS_GROUPROOM_TYPE ) {
        if ( !isset($this->_logarchive_limit)
             and !isset($this->_id_array_limit)
           ) {
           $query .= ' AND '.$this->_db_table.'.type != "'.CS_GROUPROOM_TYPE.'"';
        }
     }
     ###################################
     # FLAG: group room
     ###################################

     // insert limits into the select statement
    if (isset($this->_deleted_limit) and $this->_deleted_limit) {
        $query .= ' AND '.$this->_db_table.'.deleter_id IS NOT NULL AND '.$this->_db_table.'.deletion_date IS NOT NULL';
    } elseif ($this->_delete_limit == true) {
        $query .= ' AND '.$this->_db_table.'.deleter_id IS NULL AND '.$this->_db_table.'.deletion_date IS NULL';
     }
     if (isset($this->_status_limit)) {
        $query .= ' AND '.$this->_db_table.'.status = "'.encode(AS_DB,$this->_status_limit).'"';
     }
     if ( isset($this->_room_limit)
          and !empty($this->_room_limit)
          and !isset($this->_id_array_limit)
        ) {
        $query .= ' AND '.$this->_db_table.'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if (isset($this->_continuous_limit)) {
        $query .= ' AND '.$this->_db_table.'.continuous = "'.encode(AS_DB,$this->_continuous_limit).'"';
     }
     //search limit
     if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND (';
         $field_array = array('CONCAT(user2.firstname, " ",user2.lastname)','user2.lastname','user2.firstname',$this->_db_table.'.title',$this->_db_table.'.extras');
         $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
         $query .= $search_limit_query_code;
         $query .= ')';
      }

     if (!empty($this->_user_id_limit)) {
        $query .= ' AND user.user_id="'.encode(AS_DB,$this->_user_id_limit).'"';
     }
     if (!empty($this->_auth_source_limit)) {
        $query .= ' AND user.auth_source="'.encode(AS_DB,$this->_auth_source_limit).'"';
     }

    // time (clock pulses)
    if (isset($this->_time_limit)) {
       if ($this->_time_limit != -1) {
         $query .= ' AND time_label.item_id = "'.encode(AS_DB,$this->_time_limit).'"';
       } else {
         $query .= ' AND room_time.to_item_id IS NULL';
       }
     }

      // template
      if (isset($this->_template_limit)) {
        $query .= ' AND '.$this->_db_table.'.template = "'.encode(AS_DB,$this->_template_limit).'"';
      }
      if ( !isset($this->_logarchive_limit) ) {
         $query .= ' AND '.$this->_db_table.'.type != "privateroom"';
      }

      // log archive
      if ( !empty($this->_logarchive_limit)
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
            $query .= $this->_db_table.'.extras LIKE "%'.encode(AS_DB,$log_arg_limit).'%"';
         }
         $query .= ' AND '.$this->_db_table.'.extras LIKE "%'.encode(AS_DB,$this->_logarchive_limit).'%"';
         $query .= ')';
      }

     if (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->_db_table.'.modification_date DESC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'creation_date') {
           $query .= ' ORDER BY '.$this->_db_table.'.creation_date ASC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'activity') {
           $query .= ' ORDER BY '.$this->_db_table.'.activity ASC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'activity_rev') {
           $query .= ' ORDER BY '.$this->_db_table.'.activity DESC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'title') {
           $query .= ' ORDER BY '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'title_rev') {
           $query .= ' ORDER BY '.$this->_db_table.'.title DESC';
        } else {
           $query .= ' ORDER BY '.$this->_db_table.'.title, '.$this->_db_table.'.modification_date DESC';
        }
     } else {
        $query .= ' ORDER BY title, modification_date DESC';
     }

     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.encode(AS_DB,$this->_from_limit).', '.encode(AS_DB,$this->_interval_limit);
        }
     }
     $this->_last_query = $query;

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',E_USER_ERROR);
     } else {
        if ( !empty($this->_id_array_limit)
             and $this->_order == 'id_array'
           ) {
           // sort result
           $result2 = array();
           foreach ( $result as $value ) {
              $result2[$value['item_id']] = $value;
           }
           $result = array();
           foreach ( $this->_id_array_limit as $item_id ) {
              if ( isset($result2[$item_id]) ) {
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
        }
        return $result;
     }
  }

   ##########################################################
   # statistic functions
   ##########################################################

   function getCountAllRooms ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE context_id = '".encode(AS_DB,$this->_room_limit)."' and creation_date < '".encode(AS_DB,$end)."' and status != '4' and (type = 'project' or type = 'community')";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting all rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountOpenRooms ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE context_id = '".encode(AS_DB,$this->_room_limit)."' AND (status = '1' or status = '3') and (deletion_date IS NULL or deletion_date > '".encode(AS_DB,$end)."') and creation_date < '".encode(AS_DB,$end)."' and (type = 'project' or type = 'community')";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting open rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountClosedRooms ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->_db_table.".item_id) as number FROM ".$this->_db_table." WHERE context_id = '".encode(AS_DB,$this->_room_limit)."' AND status = '2' and (deletion_date IS NULL or deletion_date > '".encode(AS_DB,$end)."') and creation_date < '".encode(AS_DB,$end)."' and (type = 'project' or type = 'community')";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting open rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountUsedRooms ($start, $end) {
      $retour = 0;

      $query  = "SELECT count(DISTINCT ".$this->_db_table.".item_id) as number FROM ".$this->_db_table.", user";
      $query .= " WHERE user.context_id=".$this->_db_table.".item_id AND user.lastlogin > '".encode(AS_DB,$start)."' and user.creation_date < '".encode(AS_DB,$end)."'";
      $query .= " AND ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' AND (".$this->_db_table.".status = '1' or ".$this->_db_table.".status = '3') and ".$this->_db_table.".deletion_date IS NULL and ".$this->_db_table.".creation_date < '".encode(AS_DB,$end)."' and (type = 'project' or type = 'community')";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting used rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountUsedClosedRooms ($start, $end) {
      $retour = 0;

      $query  = "SELECT count(DISTINCT ".$this->_db_table.".item_id) as number FROM ".$this->_db_table.", user";
      $query .= " WHERE user.context_id=".$this->_db_table.".item_id AND user.lastlogin > '".encode(AS_DB,$start)."' and user.creation_date < '".encode(AS_DB,$end)."'";
      $query .= " AND ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' AND ".$this->_db_table.".status = '2' and ".$this->_db_table.".deletion_date IS NULL and ".$this->_db_table.".creation_date < '".encode(AS_DB,$end)."' and (type = 'project' or type = 'community')";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting used rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountActiveRooms ($start, $end) {
      $list = $this->getActiveRooms($start,$end);
      if ($list->isEmpty()) {
         return 0;
      } else {
         return $list->getCount();
      }
   }

   ##########################################

   function getActiveRooms ($start, $end) {
      $list = $this->getUsedRooms($start,$end);

      // delete rooms that are not really active
      $retour_list = new cs_list();
      if (!$list->isEmpty()) {
         $item = $list->getFirst();
         while ($item) {
            if ($item->isActive($start,$end)) {
               $retour_list->add($item);
            }
            $item = $list->getNext();
         }
      }

      return $retour_list;
   }

   function getUsedRooms ($start, $end) {
      $list = new cs_list();

      $query  = "SELECT ".$this->_db_table.".* FROM ".$this->_db_table.", user";
      $query .= " WHERE user.context_id=".$this->_db_table.".item_id AND user.lastlogin > '".encode(AS_DB,$start)."' and user.creation_date < '".encode(AS_DB,$end)."'";
      $query .= " AND ".$this->_db_table.".context_id = '".encode(AS_DB,$this->_room_limit)."' AND ".$this->_db_table.".status != '4' and ".$this->_db_table.".deletion_date IS NULL and ".$this->_db_table.".creation_date < '".encode(AS_DB,$end)."' and (type = 'project' or type = 'community')";
      $query .= " GROUP BY ".$this->_db_table.".item_id";
      $query .= " ORDER BY ".$this->_db_table.".type";
      $query .= ", ".$this->_db_table.".title";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems counting used rooms '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      } else {
         foreach ( $result as $rs ) {
            $list->add($this->_buildItem($rs));
         }
         unset($result);
      }

      return $list;
   }

   function getRelatedRoomListForUser ($user_item) {
      return $this->_getRelatedContextListForUser($user_item->getUserID(),$user_item->getAuthSource(),$this->_environment->getCurrentPortalID());
   }

   function getAllRelatedRoomListForUser ($user_item) {
      $this->setRoomTypeLimit('');
      return $this->_getRelatedContextListForUser($user_item->getUserID(),$user_item->getAuthSource(),$this->_environment->getCurrentPortalID(),true);
   }

   function getAllMaxActivityPoints () {
      $retour = 0;
      $query = 'SELECT MAX(activity) AS max FROM '.$this->_db_table.' WHERE deleter_id IS NULL AND deletion_date is NULL and (type = "project" or type = "community");';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting '.$this->_db_table.' max activity from query: "'.$query.'"',E_USER_WARNING);
      } else {
         if (!empty($result[0]['max'])) {
            $retour = $result[0]['max'];
         }
      }
      return $retour;
   }

   function getLastQuery() {
      return $this->_last_query;
   }
}
?>