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
include_once('classes/cs_item.php');
include_once('classes/cs_manager.php');

/** class for database connection to the database table "material"
 * this class implements a database manager for the table "material"
 */
class cs_item_manager extends cs_manager {

   /**
   * integer - containing the age of news as a limit
   */
   var $_age_limit = NULL;

   var $_type_limit = NULL;

   var $_label_limit = NULL;

   var $_interval_limit = NULL;

   var $_type_array_limit = array();

   var $_user_userid_limit = NULL;
   var $_user_authsourceid_limit = NULL;
   var $_user_since_lastlogin_limit = NULL;
   var $_cache_row = array();
  /**
   * integer - containing the age of material as a limit
   */
  var $_type = NULL;

  /** constructor: cs_item_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function cs_item_manager ($environment) {
     $this->cs_manager($environment);
     $this->_db_table = 'items';
  }

   /** reset limits
    * reset limits of this class: age limit and all limits from upper class
    *
    * @author CommSy Development Group
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_age_limit = NULL;
      $this->_type_limit = NULL;
      $this->_label_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_type_array_limit = array();
      $this->_user_userid_limit = NULL;
      $this->_user_authsourceid_limit = NULL;
      $this->_user_since_lastlogin_limit = NULL;
   }

   /** set age limit
    * this method sets an age limit for items
    *
    * @param integer limit age limit for items
    *
    * @author CommSy Development Group
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   function setIntervalLimit ($interval) {
     $this->_interval_limit = (int)$interval;
   }

   function setTypeArrayLimit ($array) {
     $this->_type_array_limit = $array;
   }

   function setUserUserIDLimit ($limit) {
      $this->_user_userid_limit = $limit;
   }
   function setUserAuthSourceIDLimit ($limit) {
      $this->_user_authsourceid_limit = $limit;
   }
   function setUserSinceLastloginLimit () {
      $this->_user_sincelastlogin_limit = true;
   }

   function _performQuery($mode = 'select') {
     if ($mode == 'count') {
        $query = 'SELECT count(items.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT items.item_id';
     } else {
        $query = 'SELECT items.*,label.type AS subtype';
     }
     $query .= ' FROM items';
     $query .= ' LEFT JOIN labels AS label ON items.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';

     if ( isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
          and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
        ) {
        $query .= ' INNER JOIN user ON user.context_id='.$this->_db_table.'.context_id';
     }

     $query .= ' WHERE 1';

/***Activating Code***/
      if (!$this->_show_not_activated_entries_limit) {
         $query .= ' AND (items.modification_date IS NULL OR items.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
      }
/*********************/

     if ( isset($this->_existence_limit) ) {
         $query .= ' AND items.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
     }
     if ( isset($this->_type_array_limit) and !empty($this->_type_array_limit) ) {
        $query .= ' AND (';
        $first = true;
        foreach($this->_type_array_limit as $type){
           if ($first){
              $first = false;
           } else {
              $query .= ' OR';
           }
           $query .=' items.type = "'.encode(AS_DB,$type).'"';
        }
        $query .= ' )';
     }
     if (isset($this->_room_limit) and empty($this->_room_array_limit)) {
        $query .= ' AND items.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } elseif(empty($this->_room_array_limit)) {
        $query .= ' AND items.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if (isset($this->_id_array_limit)) {
        $query .= ' AND items.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
     }
     if ( isset($this->_type_limit) or isset ($this->_label_limit)){
     $query .= ' AND (';
     if ( isset($this->_type_limit) ){
        $first = true;
        foreach($this->_type_limit as $type){
           if ($first){
              $first = false;
           }else{
              $query .= ' OR';
           }
           $query .=' items.type = "'.encode(AS_DB,$type).'"';
        }
     }
     if (isset ($this->_label_limit)){
        $first = true;
        if (isset($this->_type_limit)){
           $query .= ' OR';
        }
        foreach($this->_label_limit as $type){
           if ($first){
              $first = false;
           } else {
              $query .= ' OR';
           }
           $query .=' label.type = "'.encode(AS_DB,$type).'"';
        }
     }
     $query .=')';
     }
     if ($this->_delete_limit == true) {
        $query .= ' AND items.deleter_id IS NULL';
        $query .= ' AND items.deletion_date IS NULL';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND items.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }

     if ( isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
          and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
        ) {
        $query .= ' AND user.user_id="'.encode(AS_DB,$this->_user_userid_limit).'"';
        $query .= ' AND user.auth_source="'.encode(AS_DB,$this->_user_authsourceid_limit).'"';
        if ( isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit ) {
           $query .= ' AND user.lastlogin < '.$this->_db_table.'.modification_date';
        }
     }
     //context array limit
     if( !empty($this->_room_array_limit)) {
         $query .= ' AND '.$this->_db_table.'.context_id IN ('.implode(", ",encode(AS_DB,$this->_room_array_limit)).')';
      }
        $query .= ' ORDER BY items.modification_date DESC';
      if (!isset($this->_id_array_limit)) {
         if ($mode == 'select' and !(isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit)) {
            $query .= ' LIMIT ';
            if ( isset($this->_interval_limit) ) {
               $query .= $this->_interval_limit;
            } else {
               $query .= CS_LIST_INTERVAL;
            }
         }
      }

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     } else {
         return $result;
     }
   }

  function getItemList ($id_array) {
     return $this->_getItemList('items', $id_array);
  }

   function getAllUsedRubricsOfRoomList($room_ids){
        $rs = array();
        $query = 'SELECT DISTINCT items.context_id, items.type, label.type AS subtype';
        $query .= ' FROM items';
        $query .= ' LEFT JOIN labels AS label ON items.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
        $query .= ' WHERE 1';
        $query .= ' AND items.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
        $query .= ' AND items.deleter_id IS NULL';
        $query .= ' AND items.deletion_date IS NULL';
        $query .= ' AND items.type != "annotation"';
        $query .= ' AND items.type != "link_item"';
        $query .= ' AND items.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND items.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
        }
        $query .= ' ORDER BY items.context_id DESC';
        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
        } else {
           $rs = $result;
        }
        return $rs;
   }

   function getAllNewEntriesOfRoomList($room_ids){
        $rs = array();
        $query = 'SELECT DISTINCT items.item_id';
        $query .= ' FROM items';
        $query .= ' WHERE 1';
        $query .= ' AND items.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
        $query .= ' AND items.deleter_id IS NULL';
        $query .= ' AND items.deletion_date IS NULL';
        $query .= ' AND items.type != "annotation"';
        $query .= ' AND items.type != "link_item"';
        $query .= ' AND items.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND items.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
        }
        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
        } else {
            foreach ( $result as $query_result ) {
                $rs[] = $query_result['item_id'];
           }
        }
        return $rs;
   }

   function getAllNewEntriesOfHomeView($room_id){
        $rs = array();
        $query = 'SELECT DISTINCT items.item_id, items.type, label.type AS subtype';
        $query .= ' FROM items';
        $query .= ' LEFT JOIN labels AS label ON items.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
        $query .= ' WHERE 1';
        $query .= ' AND items.context_id ="'.encode(AS_DB,$room_id).'"';
        $query .= ' AND items.deleter_id IS NULL';
        $query .= ' AND items.deletion_date IS NULL';
        $query .= ' AND items.type != "annotation"';
        $query .= ' AND items.type != "discarticle"';
        $query .= ' AND items.type != "section"';
        $query .= ' AND items.type != "link_item"';
        if (isset($this->_age_limit)){
           $query .= ' AND items.type != "announcement"';
           $query .= ' AND items.type != "date"';
        }
        $query .= ' AND items.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND items.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
        }
        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
        } else {
            foreach ( $result as $query_result ) {
                if ($query_result['type'] == 'label'){
                   $rs[$query_result['subtype']][] = $query_result['item_id'];
                }else{
                   $rs[$query_result['type']][] = $query_result['item_id'];
                }
           }
        }
        if (isset($this->_age_limit)){
           $dates_manager = $this->_environment->getDatesManager();
           $dates_manager->reset();
           $dates_manager->setContextLimit($this->_environment->getCurrentContextID());
           // Get all current dates items
           $dates_manager->setFutureLimit();
           $dates_manager->setDateModeLimit(3);
           $ids = $dates_manager->getIDs(); // saved in session for browsing details
           $rs['date'] = $ids;
           $announcement_manager = $this->_environment->getAnnouncementManager();
           $announcement_manager->reset();
           $announcement_manager->setContextLimit($this->_environment->getCurrentContextID());
           $announcement_manager->setDateLimit(getCurrentDateTimeInMySQL());
           $announcement_manager->setSortOrder('modified');
           $ids = $announcement_manager->getIDs();
           $rs['announcement'] = $ids;
        }
        return $rs;
   }


   function getCountExistingItemsOfUser ($user_id) {
      $query = 'SELECT count(items.item_id) AS count';
      $query .= ' FROM items';
      $query .= ' INNER JOIN link_modifier_item AS l1 ON items.item_id=l1.item_id AND l1.modifier_id="'.encode(AS_DB,$user_id).'"';
      $query .= ' WHERE 1';

      if (isset($this->_room_limit)) {
         $query .= ' AND items.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      } else {
         $query .= ' AND items.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
      }
      $query .= ' AND items.deleter_id IS NULL';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or empty($result[0])) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
      } else {
         return $result[0]['count'];
      }
   }

  /** get a type of an item
    *
    * @param integer item_id id of the item
    *
    * @return string type of an item
    */
  function getItemType($iid) {
      $type = "";
      $query = 'SELECT items.type';
      $query .= ' FROM items';
      $query .= ' WHERE item_id = "'.encode(AS_DB,$iid).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting an item from query: "'.$query.'"',E_USER_WARNING);
         $success = false;
      } else {
         foreach ( $result as $query_result ) {
            $type = $query_result['type'];
         }
      }
      return $type;
   }

   /** build a new item
    * this method returns a new EMTPY material item
    *
    * @return object cs_item a new EMPTY material
    *
    * @author CommSy Development Group
    */
   function getNewItem () {
      return new cs_item($this->_environment);
   }

   /** get an item
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item an item
    */
   function getItem($iid, $vid = NULL) {
      $retour = NULL;
      if ( !isset($this->_cached_items[$iid]) ) {
         $query = 'SELECT *';
         $query .= ' FROM items';
         $query .= ' WHERE item_id="'.$iid.'"';
         $result = $this->_db_connector->performQuery($query);
         if ( isset($result) and !empty($result) ) {
            $retour = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$iid] = $retour;
            }
         }
      } else {
         $retour = $this->_cached_items[$iid];
      }
      return $retour;
   }

  /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
  function _buildItemArray($db_array) {
     return $db_array;
  }

  function setCommunityHomeLimit(){
     $this->_type_limit = array(0=>'materials',1=>CS_MATERIAL_TYPE);
     $this->_label_limit = array(0=>CS_TOPIC_TYPE,1=>CS_INSTITUTION_TYPE);
  }

   public function deleteSpecialItems ($context_id, $type) {
      $current_user = $this->_environment->getCurrentUserItem();
      $query = 'UPDATE '.$this->_db_table.' SET deleter_id='.encode(AS_DB,$current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB,$context_id).' AND type="'.encode(AS_DB,$type).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting items from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function deleteReallyOlderThan ($days) {
      $retour = false;
      $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
      $query = 'DELETE FROM '.$this->_db_table.' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'" AND type != "'.CS_DISCARTICLE_TYPE.'" AND type != "'.CS_USER_TYPE.'";'; // user und discarticle werden noch gebraucht
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem deleting items.',E_USER_ERROR);
      } else {
         unset($result);
         $retour = true;
      }
      return $retour;
   }
}
?>