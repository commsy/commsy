<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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


/** upper class of the room manager
 */
include_once('classes/cs_room2_manager.php');

/** misc functions are needed for extras field in database table
 */
include_once('functions/misc_functions.php');

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community"
 */
class cs_privateroom_manager extends cs_room2_manager {

  /**
   * integer - containing the age of community as a limit
   */
  var $_age_limit = NULL;

  var $_query_cache_array = array();

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

  private $_room_home_cache = NULL;

  private $_template_limit = NULL;

  private $_active_limit = false;

  /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     $this->_db_table = 'room_privat';
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
     $this->_template_limit = NULL;
     $this->_active_limit = false;
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

  public function setActiveLimit () {
     $this->_active_limit = true;
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

  function setTemplateLimit () {
    $this->_template_limit = 1;
  }

  function setNotTemplateLimit () {
    $this->_template_limit = -1;
  }

  function unsetTemplateLimit () {
    $this->_template_limit = NULL;
  }


function getContextIDForItemID($id){
     $query = '';
     $query .= 'SELECT DISTINCT '.$this->addDatabasePrefix($this->_db_table).'.context_id';
     $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
     $query .= ' WHERE 1';
     $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.item_id = "'.$id.'"';
     if (isset($this->_query_cache_array[$query])){
        return $this->_query_cache_array[$query];
     }
     $result = $this->_db_connector->performQuery($query);
     if (isset($result[0]['context_id'])){
        if (!isset($this->_query_cache_array[$query])){
          $this->_query_cache_array[$query] = $result[0]['context_id'];
        }
        return $result[0]['context_id'];
     }else{
        if (!isset($this->_query_cache_array[$query])){
          $this->_query_cache_array[$query] = 0;
        }
     	return '0';
     }
}



  /** select privatrooms limited by limits
    * this method returns a list (cs_list) of privatrooms within the database limited by the limits. the select statement is a bit tricky, see source code for further information
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
     
      if ( $this->_active_limit ) {
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS user2 ON '.$this->addDatabasePrefix('user').'.user_id=user2.user_id';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source=user2.auth_source';
         $query .= ' AND user2.deletion_date IS NULL';
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
     if (!empty($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if (isset($this->_room_type)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'"';
     }
     if (isset($this->_template_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.template = "1"';
     }

      if ( $this->_active_limit ) {
         include_once('functions/date_functions.php');
         $query .= ' AND user2.context_id = '.encode(AS_DB,$this->_room_limit);
         $query .= ' and user2.lastlogin >= "'.getCurrentDateTimeMinusDaysInMySQL(100).'"';
      }
     
      // archive
      // lastlogin_limit
      if ( !empty($this->_lastlogin_limit) ) {
      	if ( $this->_lastlogin_limit == 'NULL' ) {
      		$query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin IS NULL';      		
      	} else {
      		$query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.lastlogin = '.encode(AS_DB,$this->_lastlogin_limit);
      	}
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

   public function getRelatedContextListForUserOnPrivateRoomHome ($user_item) {
      include_once('classes/cs_list.php');
      $retour = new cs_list();
      if ( !isset($this->_room_home_cache) ) {
         $room_manager = $this->_environment->getRoomManager();
         $list = $room_manager->_getRelatedContextListForUser($user_item->getUserID(),$user_item->getAuthSource(),$this->_environment->getCurrentPortalID());
         if ( !$list->isEmpty() ) {
            $item = $list->getFirst();
            $run = true;
            while ( $item and $run) {
               if ( !$item->isPrivateRoom()
                    and $item->isShownInPrivateRoomHome($user_item->getUserID())
                  ) {
                  $retour->add($item);
               }
               $item = $list->getNext();
            }
         }
         unset($room_manager);
         unset($list);
         if ( $this->_cache_on ) {
            $this->_room_home_cache = $retour;
         }
      } else {
         $retour = $this->_room_home_cache;
      }
      return $retour;
   }

  function getSortedItemList($id_array,$sortBy) {
      include_once('classes/cs_list.php');
      if (empty($id_array)) {
         return new cs_list();
      } else {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ("'.implode('", "',encode(AS_DB,$id_array)).'") AND '.$this->addDatabasePrefix($this->_db_table).'.type LIKE "privateroom"';
         $query .= " ORDER BY ".encode(AS_DB,$sortBy);
         $result = $this->_db_connector->performQuery($query);
         if (!isset($result)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_room_type.' items from query: "'.$query.'"',E_USER_WARNING);
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

   /** creates a new room - internal, do not use -> use method save
    * this method creates a new room
    *
    * @param object cs_context_item (upper class) a commsy room
    */
   function _new ($item) {
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
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'modifier_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'type="'.encode(AS_DB,$item->getRoomType()).'",'.
               'continuous="1",'.
               'status="'.encode(AS_DB,$item->getStatus()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating new '.$this->_room_type.' item from query: "'.$query.'"', E_USER_ERROR);
      }
   }

  /** update a room - internal, do not use -> use method save
    * this method updates a room
    *
    * @param object cs_context_item a commsy room
    */
   public function _update ($item) {
      if ( $this->_update_with_changing_modification_information ) {
         parent::_update($item);
      }
      $query  = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET ';
      if ( $this->_update_with_changing_modification_information ) {
         $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",';
         $modifier_id = $this->_current_user->getItemID();
         if ( !empty($modifier_id) ) {
            $query .= 'modifier_id="'.encode(AS_DB,$modifier_id).'",';
         }
      }

      if ($item->isOpenForGuests()) {
         $open_for_guests = 1;
      } else {
         $open_for_guests = 0;
      }
      if ( $item->isContinuous() ) {
         $continuous = 1;
      } else {
         $continuous = -1;
      }
      if ( $item->isTemplate() ) {
         $template = 1;
      } else {
         $template = -1;
      }

      if ( $item->getActivityPoints() ) {
         $activity = $item->getActivityPoints();
      } else {
         $activity = '0';
      }

      if ( $item->getPublic() ) {
         $public = '1';
      } else {
         $public = '0';
      }

      if ( $item->isTemplate() ) {
         $title = $item->getTitlePure();
      } else {
         $title = $item->getTitle();
      }

      $query .= 'title="'.encode(AS_DB,$title).'",'.
                "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."',".
                "status='".encode(AS_DB,$item->getStatus())."',".
                "activity='".encode(AS_DB,$activity)."',".
                "public='".encode(AS_DB,$public)."',".
                "continuous='".$continuous."',".
                "template='".$template."',".
                "is_open_for_guests='".$open_for_guests."'".
                ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating '.$this->_db_table.' item from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   public function getItemIDOfRelatedOwnRoomForUser ($user_id, $auth_source, $context_id) {
      $retour = '';

      $query  = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.item_id';
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);

      $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id
                  AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL
                  AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$user_id).'"
                  AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,$auth_source).'"';
      $query .= ' AND '.$this->addDatabasePrefix('user').'.status = "3"';

      $query .= ' WHERE 1';
      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "privateroom"';
      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id="'.encode(AS_DB,$context_id).'"';
      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      include_once('classes/cs_room_item.php');
      $item = new cs_room_item($this->_environment);
      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' items from query: "'.$query.'"',E_USER_WARNING);
      } else {
        $result_array = array();
         foreach ($result as $query_result) {
            $result_array[] = $query_result['item_id'];
         }
         $result_array = array_unique($result_array);
         if (count($result_array) == 1) {
            $retour = $result_array[0];
         } else {
            include_once('functions/error_functions.php');
            trigger_error('Multiple or no private rooms for user ('.$user_id.') form auth_source ('.$auth_source.') on portal ('.$context_id.')',E_USER_WARNING);
         }
      }
      return $retour;
   }

   function getRelatedOwnRoomForUser ($user_item, $context_id) {
      $retour = NULL;
      if ( !empty($user_item) ) {
         if ( isset($this->_private_room_array[$user_item->getItemID()])
              and !empty($this->_private_room_array[$user_item->getItemID()])
            ) {
            $retour = $this->_private_room_array[$user_item->getItemID()];
         } else {
            $query  = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
            $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);

            $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id
                        AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,$user_item->getAuthSource()).'"
                        AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL
                        AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$user_item->getUserID()).'"';
            /*
            if (!$this->_all_room_limit) {
               $query .= ' AND user.status >= "2"';
            } else {
               $query .= ' AND user.status >= "1"';
            }
            */

            $query .= ' WHERE 1';
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "privateroom"';
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id="'.encode(AS_DB,$context_id).'"';

            if ($this->_delete_limit == true) {
               $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
            }
            if (isset($this->_status_limit)) {
               $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
            }
            $query .= ' ORDER BY title, creation_date DESC';

            //store query
            $this->_last_query = $query;

            // perform query
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting '.$this->_db_table.' items.',E_USER_WARNING);
            } elseif ( !empty($result[0]) ) {
               $query_result = $result[0];
               $item = $this->_buildItem($query_result);
               if ( isset($item) ) {
                  $item->setType(CS_PRIVATEROOM_TYPE);
                  $this->_private_room_array[$user_item->getItemID()] = $item;
                  $retour = $this->_private_room_array[$user_item->getItemID()];
                  unset($item);
               }
            }
         }
         unset($user_item);
      }
      return $retour;
   }
}
?>