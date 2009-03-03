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

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** text functions are needed for ???
 */
include_once('functions/text_functions.php');

/** misc functions are needed for extras field in database table
 */
include_once('functions/misc_functions.php');

/** class for database connection to the database table "community"
 * this class implements a database manager for the table "community"
 */
class cs_community_manager extends cs_context_manager {

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

  var $_template_limit = NULL;

  /** constructor: cs_community_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function cs_community_manager ($environment) {
     $this->_db_table = 'room';
     $this->_room_type = CS_COMMUNITY_TYPE;
     $this->cs_context_manager($environment);
  }

  /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
  function resetLimits () {
     parent::resetLimits();
     $this->_from_limit = NULL;
     $this->_interval_limit = NULL;
     $this->_order = NULL;
     $this->_template_limit = NULL;
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

  function getRelatedCommunityListForUser ($user_item) {
      return $this->_getRelatedContextListForUser($user_item->getUserID(),$user_item->getAuthSource(),$this->_environment->getCurrentPortalID());
   }

  /** select communities limited by limits
    * this method returns a list (cs_list) of communities within the database limited by the limits. the select statement is a bit tricky, see source code for further information
    */
  function _performQuery ($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->_db_table.'.item_id) as count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->_db_table.'.item_id';
     } else {
        $query = 'SELECT '.$this->_db_table.'.*';
     }

     $query .= ' FROM '.$this->_db_table;
     $query .= ' WHERE 1';

     // insert limits into the select statement
     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->_db_table.'.deleter_id IS NULL';
     }
     if (isset($this->_status_limit)) {
        $query .= ' AND '.$this->_db_table.'.status = "'.encode(AS_DB,$this->_status_limit).'"';
     }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->_db_table.'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     }
     if (isset($this->_room_type)) {
        $query .= ' AND '.$this->_db_table.'.type = "'.encode(AS_DB,$this->_room_type).'"';
     }

      // id_array_limit
      if( !empty($this->_id_array_limit) ) {
         $query .= ' AND '.$this->_db_table.'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
      }

     // template
     if (isset($this->_template_limit)) {
        $query .= ' AND '.$this->_db_table.'.template = "'.encode(AS_DB,$this->_template_limit).'"';
     }

     if (isset($this->_order)) {
        if ($this->_order == 'date') {
           $query .= ' ORDER BY '.$this->_db_table.'.modification_date DESC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'creation_date') {
           $query .= ' ORDER BY '.$this->_db_table.'.creation_date ASC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'creator') {
           $query .= ' ORDER BY user.lastname, '.$this->_db_table.'.modification_date DESC';
        } elseif ($this->_order == 'activity') {
           $query .= ' ORDER BY '.$this->_db_table.'.activity ASC, '.$this->_db_table.'.title ASC';
        } elseif ($this->_order == 'activity_rev') {
           $query .= ' ORDER BY '.$this->_db_table.'.activity DESC, '.$this->_db_table.'.title ASC';
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

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems selecting '.$this->_db_table.' items.',E_USER_ERROR);
     } else {
        return $result;
     }
  }

   function getSortedItemList ($id_array,$sortBy) {
      if ( empty($id_array) ) {
         return new cs_list();
      } else {
         $query = 'SELECT * FROM '.$this->_db_table.' WHERE '.$this->_db_table.'.item_id IN ("'.implode('", "',encode(AS_DB,$id_array)).'") AND '.$this->_db_table.'.type LIKE "community"';
         $query .= " ORDER BY ".$sortBy;
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems selecting list of '.$this->_room_type.' items.',E_USER_WARNING);
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

  /** update a room - internal, do not use -> use method save
    * this method updates a room
    *
    * @param object cs_context_item a commsy room
    */
   function _update ($item) {
      if ( $this->_update_with_changing_modification_information ) {
         parent::_update($item);
      }
      $query  = 'UPDATE '.$this->_db_table.' SET ';
      if ( $this->_update_with_changing_modification_information ) {
         $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",';
         $modifier_id = $this->_current_user->getItemID();
         if ( !empty($modifier_id) ) {
            $query .= 'modifier_id="'.$modifier_id.'",';
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

      $query .= 'title="'.encode(AS_DB,$item->getTitle()).'",'.
#                "short_title='".encode(AS_DB,$item->getShortTitle())."',".
                "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."',".
                "status='".encode(AS_DB,$item->getStatus())."',".
                "activity='".encode(AS_DB,$activity)."',".
                "public='".encode(AS_DB,$public)."',".
                "continuous='".encode(AS_DB,$continuous)."',".
                "template='".encode(AS_DB,$template)."',".
                "is_open_for_guests='".encode(AS_DB,$open_for_guests)."'".
                ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating '.$this->_db_table.' item.',E_USER_WARNING);
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

      if ($item->isContinuous()) {
         $continuous = 1;
      } else {
         $continuous = -1;
      }

      if ($item->getPublic()) {
         $public = $item->getPublic();
      } else {
         $public = 0;
      }

      $query = 'INSERT INTO '.$this->_db_table.' SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'modifier_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
#               'short_title="'.encode(AS_DB,$item->getShortTitle()).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'public="'.encode(AS_DB,$public).'",'.
               'type="'.encode(AS_DB,$item->getRoomType()).'",'.
               'continuous="'.encode(AS_DB,$continuous).'",'.
               'status="'.encode(AS_DB,$item->getStatus()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating new '.$this->_room_type.' item.', E_USER_ERROR);
      }
   }
}
?>