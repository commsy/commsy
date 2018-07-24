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

/** upper class of the context manager
 */
include_once('classes/cs_manager.php');

/** interface for ex- and import-functions
 */
include_once('interfaces/cs_export_import_interface.php');

/** upper class for database connection to the database table "community", "project" and "portal"
 * this upper class implements a database manager for the table "community", "project" and "portal"
 */
class cs_context_manager extends cs_manager implements cs_export_import_interface {

   var $_room_type = NULL;

   var $_all_room_limit = false;
   
   var $_all_status_limit = false;

  /**
   * integer - containing the id of a institution as a limit for the selected announcement
   */
  var $_institution_limit = NULL;

  /**
   * integer - containing the id of a topic as a limit for the selected announcement
   */
  var $_topic_limit = NULL;

   /**
    * string - containing an order limit for the select context
    */
   var $_order = NULL;

   /**
    * string - containing an order limit for the select project
    */
   var $_sort_order = NULL;

   /**
    * integer - containing a status limit: 0 no project, 1 open, 2 closed, 3 deleted
    */
   var $_status_limit = NULL;

   var $_id_array_limit = NULL;
   var $_cache_extras = array();
   var $_cache_list = array();
   var $_cache_row = array();
   var $_sql_with_extra = true;

   /** constructor: cs_room_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
   }

   /** reset limits
    * reset limits of this class: age limit, from limit, interval limit, order limit and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_status_limit = NULL;
      $this->_all_room_limit = false;
      $this->_all_status_limit = false;
      $this->_order = NULL;
      $this->_institution_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_sort_order = NULL;
      $this->_id_array_limit = NULL;
   }

   /** set status limit
    */
   function setStatusLimit($limit) {
      $this->_status_limit = (int)$limit;
   }

   /** set status limit to "opened"
    */
   function setOpenedLimit () {
      $this->_status_limit = CS_ROOM_OPEN;
   }

  /** set status limit to "closed"
    */
   function setClosedLimit () {
      $this->_status_limit = CS_ROOM_CLOSED;
   }

   /** set status limit to "locked"
    */
   function setLockedLimit () {
      $this->_status_limit = CS_ROOM_LOCK;
   }

   /** set status limit to "not locked"
    */
   function setNotLockedLimit () {
      $this->_status_limit = 5;
   }

   function setInstitutionLimit ($limit) {
      $this->_institution_limit = (int)$limit;
   }

   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected project
    */
   function setOrder ($limit) {
      $this->_order = (string)$limit;
   }

   /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected project
    */
   function setSortOrder ($limit) {
      $this->_sort_order = (string)$limit;
   }

   /** build a new room item
    * this method returns a new EMTPY room item
    */
   function getNewItem () {
      return $this->_getNewRoomItem($this->_room_type);
   }

  /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      if (empty($db_array)) { // room not exists in database
         return NULL;
      }
      if (isset($db_array['extras'])){
         include_once('functions/text_functions.php');
         $db_array['extras'] = mb_unserialize($db_array['extras']);
      }
      if (isset($db_array['description'])){
         include_once('functions/text_functions.php');
         $db_array['description'] = mb_unserialize($db_array['description']);
      }
      $item = $this->_getNewRoomItem($db_array['type']);
      $item->_setItemData(encode(FROM_DB,$db_array));

      if ( isset($this->_sql_with_extra)
           and !$this->_sql_with_extra ) {
         $item->unsetLoadExtras();
      }
      if ( !empty($db_array['zzz_table'])
      	  and $db_array['zzz_table'] == 1
      	) {
      	$item->setArchiveStatus();
      }

      if ( $this->_cache_on ) {
         if ( empty($this->_cache_object[$item->getItemID()]) ) {
            $this->_cache_object[$item->getItemID()] = $item;
         }
      }
      
      return $item;
   }

  /** build a new room item, INTERNAL
    * this method returns a new EMTPY room item
    *
    * @return object cs_item a new EMPTY room
    */
   function _getNewRoomItem ($type) {
      if ( (empty($type)) ) {
         $retour = NULL;
      } elseif ( $type == CS_PROJECT_TYPE ) {
         include_once('classes/cs_project_item.php');
         $retour = new cs_project_item($this->_environment);
      } elseif ( $type == CS_SERVER_TYPE ) {
         include_once('classes/cs_server_item.php');
         $retour = new cs_server_item($this->_environment);
      } elseif ( $type == CS_COMMUNITY_TYPE ) {
         include_once('classes/cs_community_item.php');
         $retour = new cs_community_item($this->_environment);
      } elseif ( $type == CS_PRIVATEROOM_TYPE ) {
         include_once('classes/cs_privateroom_item.php');
         $retour = new cs_privateroom_item($this->_environment);
      } elseif ( $type == CS_GROUPROOM_TYPE ) {
         include_once('classes/cs_grouproom_item.php');
         $retour = new cs_grouproom_item($this->_environment);
      } elseif ( $type == CS_PORTAL_TYPE ) {
         include_once('classes/cs_portal_item.php');
         $retour = new cs_portal_item($this->_environment);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('do not know this type: '.$type,E_USER_WARNING);
      }
      $retour->setRoomType($type);
      return $retour;
   }

   function _getRelatedContextListForUser ($user_id, $auth_source, $context_id, $grouproom = false, $only_user = false) {
      include_once('classes/cs_list.php');
      $list = new cs_list();
      if ( !isset($this->_cache_list[$user_id.'_'.$auth_source.'_'.$context_id]) ) {
         $query  = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*';
         $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id
                     AND '.$this->addDatabasePrefix('user').'.auth_source="'.$auth_source.'"
                     AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL
                     AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$user_id).'"';
         if (!$this->_all_status_limit) {
            if ( !$only_user ) {
               $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "1"';
            } else {
               $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
            }
         } else {
            $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "0"';
         }
         $query .= ' WHERE 1';
         if ( isset($this->_room_type) and !empty($this->_room_type) ) {
            ############################################
            # FLAG: group room
            ###################BEGIN####################
            $current_portal = $this->_environment->getCurrentPortalItem();
            if ( !isset($current_portal) and !empty($context_id) ) {
               $portal_manager = $this->_environment->getPortalManager();
               $current_portal = $portal_manager->getItem($context_id);
            }
            if ( $this->_room_type == CS_PROJECT_TYPE
                 and (
                       ( isset($current_portal) and $current_portal->withGroupRoomFunctions() )
                       or $grouproom
                     )
               ) {
               $query .= ' AND ('.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'" or '.$this->addDatabasePrefix($this->_db_table).'.type = "'.CS_GROUPROOM_TYPE.'")';
            } else {
            ####################END#####################
            # FLAG: group room
            ############################################
               $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'"';
            ############################################
            # FLAG: group room
            ##################BEGIN####################
               if ( $this->_room_type != CS_GROUPROOM_TYPE ) {
                  $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type != "'.CS_GROUPROOM_TYPE.'"';
               }
            }
         } else {
            $current_portal = $this->_environment->getCurrentPortalItem();
            if ( !isset($current_portal) and !empty($context_id) ) {
               $portal_manager = $this->_environment->getPortalManager();
               $current_portal = $portal_manager->getItem($context_id);
            }
            if ( ( isset($current_portal)
                   and !$current_portal->withGroupRoomFunctions()
                 )
                 or !$grouproom
               ) {
               $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type != "'.CS_GROUPROOM_TYPE.'"';
            }
            ###################END######################
            # FLAG: group room
            ############################################
         }

         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id="'.encode(AS_DB,$context_id).'"';

         if ($this->_delete_limit == true) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
         }
         if (isset($this->_status_limit)) {
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
         }
         $query .= ' ORDER BY title, creation_date DESC';

         // perform query
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting '.$this->_db_table.' items.',E_USER_WARNING);
         } else {
            foreach ($result as $query_result) {
               $list->add($this->_buildItem($query_result));
            }
            if ( $this->_cache_on ) {
               $this->_cache_list[$user_id.'_'.$auth_source.'_'.$context_id] = $list;
            }
         }
      } else {
         $list = $this->_cache_list[$user_id.'_'.$auth_source.'_'.$context_id];
      }
      return $list;
   }

   function _getRelatedContextListForUserSortByTime ($user_id, $auth_source, $context_id, $grouproom = false) {
      $list = new cs_list();

      $query  = 'SELECT '.$this->addDatabasePrefix($this->_db_table).'.*, '.$this->addDatabasePrefix('labels').'.item_id AS labels_item_id';
      $query .= ' FROM '.$this->addDatabasePrefix($this->_db_table);

      $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.item_id
                  AND '.$this->addDatabasePrefix('user').'.auth_source="'.$auth_source.'"
                  AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL
                  AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$user_id).'"';
      if (!$this->_all_room_limit) {
         $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
      } else {
         $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "1"';
      }

      $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' ON '.$this->addDatabasePrefix($this->_db_table).'.item_id='.$this->addDatabasePrefix('links').'.from_item_id AND '.$this->addDatabasePrefix('links').'.link_type="in_time" AND '.$this->addDatabasePrefix('links').'.context_id="'.$context_id.'"';
      $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' ON '.$this->addDatabasePrefix('links').'.to_item_id='.$this->addDatabasePrefix('labels').'.item_id';

      $query .= ' WHERE 1';
      if (isset($this->_room_type)) {
         ############################################
         # FLAG: group room
         ###################BEGIN####################
         $current_portal = $this->_environment->getCurrentPortalItem();
         if ( $this->_room_type == CS_PROJECT_TYPE
              and ($current_portal->withGroupRoomFunctions()
              or $grouproom) ) {
            $query .= ' AND ('.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'" or '.$this->addDatabasePrefix($this->_db_table).'.type = "'.CS_GROUPROOM_TYPE.'")';
         } else {
         ####################END#####################
         # FLAG: group room
         ############################################
            $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.type = "'.encode(AS_DB,$this->_room_type).'"';
         ############################################
         # FLAG: group room
         ##################BEGIN####################
         }
         ###################END######################
         # FLAG: group room
         ############################################
      }

      $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id="'.encode(AS_DB,$context_id).'"';

      if ($this->_delete_limit == true) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.deleter_id IS NULL';
      }
      if (isset($this->_status_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.status = "'.encode(AS_DB,$this->_status_limit).'"';
      }

      $query .= ' ORDER BY '.$this->addDatabasePrefix('labels').'.name DESC, '.$this->addDatabasePrefix($this->_db_table).'.title, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';

      // perform query
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' items.',E_USER_WARNING);
      } else {
         $label_item_id = '';
         $label_manager = $this->_environment->getLabelManager();
         foreach ($result as $query_result) {
            if ( $label_item_id != $query_result['labels_item_id']) {
               if ( isset($query_result['labels_item_id']) ) {
                  $label_item_id = $query_result['labels_item_id'];
                  $label_item = $label_manager->getItem($label_item_id);
               } else {
                  $label_item_id = NULL;
                  $label_item = $label_manager->getNewItem();
                  $label_item->setTitle('COMMON_NOT_LINKED');
               }
               $list->add($label_item);
               unset($label_item);
            }
            $item = $this->_buildItem($query_result);
            $list->add($item);
         }
         unset($label_manager);
      }
      return $list;
   }

   /** get a room
    *
    * @param integer item_id id of the item
    *
    * @return \cs_context_item cs_context a room: project, community, portal, server
    */
   function getItem ($item_id) {
      $retour = NULL;
      if ( !empty($item_id)
           and is_numeric($item_id)
         ) {
         if ( !isset($this->_cache_object[$item_id])
              and !isset($this->_cache_row[$item_id])
            ) {
            $query = "SELECT * FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".item_id='".encode(AS_DB,$item_id)."'";
            $result = $this->_db_connector->performQuery($query);
            unset($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting '.$this->_db_table.' item.',E_USER_WARNING);
            } elseif ( !empty($result[0]) ) {
               $data_array = $result[0];
               if ( !empty($data_array) ) {
               	if ( function_exists('get_called_class')
               		  and strstr(get_called_class(),'_zzz_')
               	   ) {
               		$data_array['zzz_table'] = 1;
               	}
                  $retour = $this->_buildItem($data_array);
               }
               unset($result);
            }
         } else {
            if ( !empty($this->_cache_object[$item_id]) ) {
               $retour = $this->_cache_object[$item_id];
            } else {
               $retour = $this->_buildItem($this->_cache_row[$item_id]);
            }
         }
      }
      return $retour;
   }

   /** get a extras of a room
    *
    * @param integer item_id id of the item
    *
    * @return array extras of a room: project, community, portal, server
    */
   function getExtras ($item_id) {
      $retour = array();
      if ( !empty($item_id)
           and is_numeric($item_id)
         ) {
         if (isset($this->_cache_extras[$item_id])){
            $retour = mb_unserialize($this->_cache_extras[$item_id]);
         }else{
            $query = "SELECT extras FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".item_id='".encode(AS_DB,$item_id)."'";
            $result = $this->_db_connector->performQuery($query);
            unset($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting '.$this->_db_table.' item.',E_USER_WARNING);
            } elseif ( !empty($result[0]) ) {
               $data_array = $result[0];
               if ( !empty($data_array['extras']) ) {
                  include_once('functions/text_functions.php');
                  $retour = mb_unserialize($data_array['extras']);
               }
               unset($data_array);
               unset($result);
            }
         }
      }
      return $retour;
   }

   function loadExtrasForContextArrayInCache ($id_array) {
      $retour = array();
      $query = "SELECT extras, item_id FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ";
      $query .= $this->addDatabasePrefix($this->_db_table).".item_id IN (".implode(', ', $id_array).")";
      $result = $this->_db_connector->performQuery($query);
      unset($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' item.',E_USER_WARNING);
      } else{
         foreach($result as $r){
            $this->_cache_extras[$r['item_id']]=$r['extras'];
         }
      }

      return $retour;
   }


  /** create a project - internal, do not use -> use method save
    * this method creates a project
    *
    * @param object cs_item project_item the project
    */
  function _create ($item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="'.encode(AS_DB,$this->_room_type).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating '.$this->_db_table.' item.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->getCreateID());
        $this->_new($item);
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

      $activity_points = $item->getActivityPoints();
      if ( empty($activity_points) ) {
         $activity_points = 0;
      }

      $query .= 'title="'.encode(AS_DB,$item->getTitle()).'",'.
                "context_id='".encode(AS_DB,$item->getContextID())."',".
                "extras='".encode(AS_DB,serialize($item->getExtraInformation()))."',".
                "status='".encode(AS_DB,$item->getStatus())."',".
                "activity='".encode(AS_DB,$activity_points)."',".
                "is_open_for_guests='".encode(AS_DB,$open_for_guests)."'";

      // maybe move this to method to portal/server manager
      if ( $item->isPortal()
           or $item->isServer()
         ) {
         $url = $item->getUrl();
         if ( isset($url) ) {
            $query .= ", url='".encode(AS_DB,$url)."'";
         }
      }

      $query .= ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating '.$this->_db_table.' item.',E_USER_WARNING);
      }
      unset($item);
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
      $query = 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).' SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'",'.
               'context_id="'.encode(AS_DB,$item->getContextID()).'",'.
               'creator_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'modifier_id="'.encode(AS_DB,$user->getItemID()).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modification_date="'.$current_datetime.'",'.
               'title="'.encode(AS_DB,$item->getTitle()).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'type="'.encode(AS_DB,$item->getRoomType()).'",'.
               'status="'.encode(AS_DB,$item->getStatus()).'"';

      // maybe move this to method to portal/server manager
      if ( $item->isPortal()
           or $item->isServer()
         ) {
         $url = $item->getUrl();
         if ( isset($url) ) {
            $query .= ", url='".encode(AS_DB,$url)."'";
         }
      }

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems creating new '.$this->_room_type.' item: "'.$this->_dberror.'" from query: "'.$query.'"', E_USER_ERROR);
      } else {
         $item->setCreationDate($current_datetime);
      }
      unset($item);
   }

   function minimizeActivityPoints ($quotient) {
      $retour = false;
      $query  = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET activity=ROUND(activity/'.encode(AS_DB,$quotient).') WHERE activity > 0;';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems minimizing activity points.',E_USER_WARNING);
      } else {
         $retour = true;
      }
      return $retour;
   }

   /** delete a project
    * this method deletes a project
    *
    * @param integer item_id item id of the project
    */
   function delete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
               ' deletion_date="'.$current_datetime.'",'.
               ' deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         parent::delete($item_id);
      }
   }

   function undelete ($item_id) {
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
               ' deletion_date=NULL,'.
               ' deleter_id=NULL,'.
               ' modification_date="'.$current_datetime.'",'.
               ' modifier_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems undeleting '.$this->_db_table.'.',E_USER_WARNING);
      } else {
         parent::undelete($item_id);
      }
   }

   function getMaxActivityPoints () {
      $retour = 0;
      $query = 'SELECT MAX(activity) AS max FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id = '.encode(AS_DB,$this->_room_limit).';';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or empty($result[0]) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' max activity.',E_USER_WARNING);
      } else {
         $data_array = $result[0];
         if (!empty($data_array)) {
            $retour = $data_array['max'];
         }
      }
      return $retour;
   }

   function getMaxActivityPointsInCommunityRoom ($community_room_limit) {
      $retour = 0;
      $query = 'SELECT MAX(activity) AS max FROM '.$this->addDatabasePrefix($this->_db_table).'';
      $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l31.second_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
      $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix($this->_db_table).'.item_id AND l32.first_item_type="'.CS_COMMUNITY_TYPE.'"))) ';
      $query .= 'WHERE '.$this->addDatabasePrefix($this->_db_table).'.context_id = '.encode(AS_DB,$this->_room_limit).'';
      $query .= ' AND ( (l31.context_id="'.encode(AS_DB,$this->_room_limit).'" AND (l31.first_item_id = "'.encode(AS_DB,$community_room_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$community_room_limit).'"))';
      $query .= ' OR ( l32.context_id="'.encode(AS_DB,$this->_room_limit).'" AND (l32.first_item_id = "'.encode(AS_DB,$community_room_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$community_room_limit).'")));';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or empty($result[0]) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting '.$this->_db_table.' max activity: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
      } else {
         $data_array = $result[0];
         if (!empty($data_array)) {
            $retour = $data_array['max'];
         }
      }
      return $retour;
   }

   function getMaxActivityPointsInCommunityRoomInternal ($community_room_array_limit) {
      $retour = 0;
      if ( !empty($community_room_array_limit)
           and is_array($community_room_array_limit)
         ) {
         $query  = 'SELECT MAX(activity) AS max FROM '.$this->addDatabasePrefix($this->_db_table);
         $query .= ' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id IN ('.encode(AS_DB,implode(',',$community_room_array_limit)).');';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or empty($result[0]) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting '.$this->_db_table.' max activity: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $data_array = $result[0];
            if (!empty($data_array)) {
               $retour = $data_array['max'];
            }
         }
      }
      return $retour;
   }

   function saveActivityPoints ($item) {
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET'.
               ' activity="'.encode(AS_DB,$item->getActivityPoints()).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating activity points '.$this->_db_table.'.',E_USER_WARNING);
      }
   }

   public function setQueryWithExtra () {
      $this->_sql_with_extra = true;
   }

   public function setQueryWithoutExtra () {
      $this->_sql_with_extra = false;
   }
   
   function export_item($id) {
      $item_manager = $this->_environment->getItemManager();
      $item = $item_manager->getItem($id);
      
      if ($item != null) {
         if ($item->getItemType() == 'community') {
            $community_manager = $this->_environment->getCommunityManager();
            $context_item = $community_manager->getItem($id);
         } else if ($item->getItemType() == 'project') {
            $project_manager = $this->_environment->getProjectManager();
            $context_item = $project_manager->getItem($id);
         } else if ($item->getItemType() == 'grouproom') {
            $grouproom_manager = $this->_environment->getGrouproomManager();
            $context_item = $grouproom_manager->getItem($id);
         } else if ($item->getItemType() == 'privateroom') {
            $privateroom_manager = $this->_environment->getPrivateRoomManager();
            $context_item = $privateroom_manager->getItem($id);
         }

         $xml = new SimpleXMLElementExtended('<context_item></context_item>');
         $xml->addChildWithCDATA('item_id', $context_item->getItemID());
         $xml->addChildWithCDATA('context_id', $context_item->getContextID());
         $xml->addChildWithCDATA('creator_id', $context_item->getCreatorID());
         $xml->addChildWithCDATA('modifier_id', $context_item->getModificatorID());
         $xml->addChildWithCDATA('deleter_id', $context_item->getDeleterID());
         $xml->addChildWithCDATA('creation_date', $context_item->getCreationDate());
         $xml->addChildWithCDATA('modification_date', $context_item->getModificationDate());
         $xml->addChildWithCDATA('deletion_date', $context_item->getDeletionDate());
         $xml->addChildWithCDATA('title', $context_item->getTitle());
         
         $extras_array = $context_item->getExtraInformation();
         $xmlExtras = $this->getArrayAsXML($xml, $extras_array, true, 'extras');
         $this->simplexml_import_simplexml($xml, $xmlExtras);
         
         $xml->addChildWithCDATA('status', $context_item->getStatus());
         $xml->addChildWithCDATA('activity', $context_item->getActivityPoints());
         $xml->addChildWithCDATA('type', $context_item->getType());
         $xml->addChildWithCDATA('public', $context_item->getPublic());
         $xml->addChildWithCDATA('is_open_for_guests', $context_item->isOpenForGuests());
         $xml->addChildWithCDATA('continuous', $context_item->isContinuous());
         $xml->addChildWithCDATA('template', $context_item->isTemplate());
         $xml->addChildWithCDATA('contact_persons', $context_item->getContactPersonString());
         
         $description_array = $context_item->getDescriptionArray();
         $xmlDescription = $this->getArrayAsXML($xml, $description_array, true, 'description');
         $this->simplexml_import_simplexml($xml, $xmlDescription);
         
         $xml->addChildWithCDATA('room_description', $context_item->getDescription());
         $xml->addChildWithCDATA('lastlogin', $context_item->getLastLogin());
         
         if ($item->getItemType() == 'privateroom') {
            $user_manager = $this->_environment->getUserManager();
            $private_room_user = $user_manager->getItem($context_item->getCreatorID());
            $xml->addChildWithCDATA('user_id', $private_room_user->getUserID());
         }
         
         $xml = $this->export_sub_items($xml, $context_item);
         
         return $xml;
      }
   }
   
   function export_sub_items($xml, $top_item) {
      $conf = $top_item->getHomeConf();
      if (!empty($conf)) {
         $rubrics = explode(',', $conf);
      } else {
         $rubrics = array();
      }
      $type_array = array();
      foreach ($rubrics as $rubric) {
         $rubric_array = explode('_', $rubric);
         if ($rubric_array[1] != 'none' && $rubric_array[1] != 'user' && $rubric_array[1] != 'topic' && $rubric_array[1] != 'group') {
            $type_array[] = $rubric_array[0];
         }
      }
      $type_array[] = 'label';
      
      if ($top_item->getItemType() == 'privateroom') {
         if (!in_array('material', $type_array)) {
            $type_array[] = 'material';
         }
         if (!in_array('date', $type_array)) {
            $type_array[] = 'date';
         }
         if (!in_array('discussion', $type_array)) {
            $type_array[] = 'discussion';
         }
         if (!in_array('todo', $type_array)) {
            $type_array[] = 'todo';
         }
         if (!in_array('annotation', $type_array)) {
            $type_array[] = 'annotation';
         }
         if (!in_array('portfolio', $type_array)) {
            $type_array[] = 'portfolio';
         }
      }
      
      $rubric_xml = new SimpleXMLElementExtended('<rubric></rubric>');

      foreach ($type_array as $type) {
         $type_manager = $this->_environment->getManager($type);
         if ($type_manager instanceof cs_export_import_interface) {
            $type_manager->setContextLimit($top_item->getItemID());
            if ($type == 'date') {
               $type_manager->setWithoutDateModeLimit();
            }
            if ($type == 'portfolio') {
               $current_user_item = $this->_environment->getCurrentUserItem();
               $private_room_user_item = $current_user_item->getRelatedPrivateRoomUserItem();
               $type_manager->setUserLimit($private_room_user_item->getItemID());
            }
            $type_manager->select();
            $type_list = $type_manager->get();
            
            // get XML for each item
            $type_item_xml_array = array();
            if (!$type_list->isEmpty()) {
               $type_item = $type_list->getFirst();
               while ($type_item) {
                  $type_id = $type_item->getItemID();
                  $type_item_xml_array[] = $type_manager->export_item($type_id);
                  $type_item = $type_list->getNext();
               }
            }
   
            // combine in tag
            $type_xml = new SimpleXMLElementExtended('<'.$type.'></'.$type.'>');
            foreach ($type_item_xml_array as $type_item_xml) {
               $this->simplexml_import_simplexml($type_xml, $type_item_xml);
            }
         
            // add to base xml
            $this->simplexml_import_simplexml($rubric_xml, $type_xml);
         }
      }

      $this->simplexml_import_simplexml($xml, $rubric_xml);

      $tags_xml = new SimpleXMLElementExtended('<tags></tags>');
      if ($top_item->withTags()) {
         $current_context_id = $this->_environment->getCurrentContextID();
         $this->_environment->setCurrentContextID($top_item->getItemID());
         include_once('classes/controller/cs_utils_controller.php');
         $utils_controller = new cs_utils_controller($this->_environment);
         $room_id = $top_item->getItemID();
         $tag2tag_manager = $this->_environment->getTag2TagManager();
         $tag2tag_manager->resetCachedChildrenIdArray();
         $tag_array = $utils_controller->getTags($room_id);
         $this->_environment->setCurrentContextID($current_context_id);
         $xml_tags = $this->getTagsAsXML($tags_xml, $tag_array);
         $this->simplexml_import_simplexml($xml, $xml_tags);
      }

      if ($top_item->getItemType() == 'community') {
         $project_list_xml = new SimpleXMLElementExtended('<projects></projects>');
         $project_list = $top_item->getProjectList();
         if ($project_list->isNotEmpty()) {
            $project_manager = $this->_environment->getProjectManager();
            $project_item = $project_list->getFirst();
            while ($project_item) {
               $project_id = $project_item->getItemID();
               $project_xml = $project_manager->export_item($project_id);
               $this->simplexml_import_simplexml($project_list_xml, $project_xml);
               $project_item = $project_list->getNext();
            }
         }
         $this->simplexml_import_simplexml($xml, $project_list_xml);
      } else if ($top_item->getItemType() == 'project') {
         $grouproom_list_xml = new SimpleXMLElementExtended('<grouprooms></grouprooms>');
         $grouproom_list = $top_item->getGroupRoomList();
         if ($grouproom_list->isNotEmpty()) {
            $grouproom_manager = $this->_environment->getGroupRoomManager();
            $grouproom_item = $grouproom_list->getFirst();
            while ($grouproom_item) {
               $grouproom_id = $grouproom_item->getItemID();
               $grouproom_xml = $grouproom_manager->export_item($grouproom_id);
               $this->simplexml_import_simplexml($grouproom_list_xml, $grouproom_xml);
               $grouproom_item = $grouproom_list->getNext();
            }
         }
         $this->simplexml_import_simplexml($xml, $grouproom_list_xml);
      }

      $links_manager = $this->_environment->getLinkManager();
      $links_manager->setContextLimit($top_item->getItemId());
      $links_xml = $links_manager->export_items();
      $this->simplexml_import_simplexml($xml, $links_xml);

      $link_items_xml = new SimpleXMLElementExtended('<link_items></link_items>');
      $link_item_manager = $this->_environment->getLinkItemManager();
      $link_item_manager->setContextLimit($top_item->getItemId());
      $link_item_manager->select();
      $link_item_list = $link_item_manager->get();
      $link_item = $link_item_list->getFirst();
      while ($link_item) {
         $link_item_xml = $link_item_manager->export_item($link_item->getItemId());
         $this->simplexml_import_simplexml($link_items_xml, $link_item_xml);
         $link_item = $link_item_list->getNext();   
      }
      $this->simplexml_import_simplexml($xml, $link_items_xml);

      return $xml;
   }
   
   function import_item($xml, $top_item, &$options) {
      $translator = $this->_environment->getTranslationObject();
      if ($xml != null) {
         if (((string)$xml->type[0]) == 'community') {
            $community_manager = $this->_environment->getCommunityManager();
            $context_item = $community_manager->getNewItem();
            
            // delete previous version of the room and all associated project rooms.
            $community_manager->select();
            $community_list = $community_manager->get();
            $community_item = $community_list->getFirst();
            while ($community_item) {
               if ($community_item->getTitle() == ((string)$xml->title[0]) || $community_item->getTitle() == ((string)$xml->title[0]).' ['.$translator->getMessage('PREFERENCES_EXPORT_IMPORTED_CONTEXT').']') {
                  $project_list = $community_item->getProjectList();
                  $project_item = $project_list->getFirst();
                  while ($project_item) {
                     $grouproom_list = $project_item->getGroupRoomList();
                     $grouproom_item = $grouproom_list->getFirst();
                     while ($grouproom_item) {
                        $grouproom_item->delete();
                        $grouproom_item = $grouproom_list->getNext();   
                     }
                     $project_item->delete();
                     $project_item = $project_list->getNext();
                  }
                  $community_item->delete();
               }
               $community_item = $community_list->getNext();
            }
         } else if (((string)$xml->type[0]) == 'project') {
            $project_manager = $this->_environment->getProjectManager();
            $context_item = $project_manager->getNewItem();
         } else if (((string)$xml->type[0]) == 'grouproom') {
            $grouproom_manager = $this->_environment->getGrouproomManager();
            $context_item = $grouproom_manager->getNewItem();
         } else if (((string)$xml->type[0]) == 'privateroom') {
            $this->_environment->setCurrentContextID($this->_environment->getCurrentPortalID());
            $privateroom_manager = $this->_environment->getPrivateRoomManager();
            $current_user_item = $this->_environment->getCurrentUserItem();
            $old_private_room_user_item = $current_user_item->getRelatedPrivateRoomUserItem();
            $portfolio_manager = $this->_environment->getPortfolioManager();
            $portfolio_manager->setUserLimit($old_private_room_user_item->getItemID());
            $portfolio_manager->select();
            $portfolio_list = $portfolio_manager->get();
            if ($portfolio_list->isNotEmpty()) {
               $portfolio_item = $portfolio_list->getFirst();
               while ($portfolio_item) {
                  $portfolio_item->delete();
                  $portfolio_item = $portfolio_list->getNext();
               }
            }
            $context_item = $privateroom_manager->getNewItem();
         }

         $context_item->setTitle((string)$xml->title[0].' ['.$translator->getMessage('PREFERENCES_EXPORT_IMPORTED_CONTEXT').']');
         $context_item->setStatus((string)$xml->status[0]);
         $context_item->getActivityPoints((string)$xml->activity[0]);
         $context_item->setPublic((string)$xml->public[0]);
         $context_item->setOpenForGuests((string)$xml->is_open_for_guests[0]);
         $context_item->setContinuous((string)$xml->continuous[0]);
         if ((string)$xml->template[0] == '1') {
            $context_item->setTemplate();
         } else {
            $context_item->setNotTemplate();
         }
         $extra_array = $this->getXMLAsArray($xml->extras);
         $context_item->setExtraInformation($extra_array['extras']);
         $context_item->setDescription((string)$xml->room_description[0]);
         
         // set additional values
         if (((string)$xml->type[0]) == 'community') {
         } else if (((string)$xml->type[0]) == 'project') {
         } else if (((string)$xml->type[0]) == 'grouproom') {
            $context_item->setLinkedProjectRoomItemID($top_item->getItemId());
         }
         
         $context_item->save();

         $new_private_room_user_item = NULL;
         if (((string)$xml->type[0]) == 'privateroom') {
            $privateroom_manager = $this->_environment->getPrivateRoomManager();
            $temp_user_item = $this->_environment->getCurrentUser();
            $temp_private_room_item = $privateroom_manager->getRelatedOwnRoomForUser($temp_user_item, $this->_environment->getCurrentPortalID());
            
            $temp_private_room_user_item = NULL;
            $user_manager = $this->_environment->getUserManager();
            $user_array = $user_manager->getAllUserItemArray($temp_user_item->getUserID());
            foreach ($user_array as $temp_user) {
               if ($temp_user->getContextID() == $temp_private_room_item->getItemID()) {
                  $temp_private_room_user_item = $temp_user;
               }
            }

            $temp_private_room_item->delete();
            $temp_private_room_user_item->delete();
            
            $user_array = $user_manager->getAllUserItemArray($temp_user_item->getUserID());
            foreach ($user_array as $temp_user) {
               if ($temp_user->getContextID() == $context_item->getItemID()) {
                  $new_private_room_user_item = $temp_user;
               }
            }
            $this->_environment->setCurrentUserItem($new_private_room_user_item);
            $linkModifierItemManager = $this->_environment->getLinkModifierItemManager();
            $linkModifierItemManager->_current_user_id = $new_private_room_user_item->getItemID();
            
            $displayConfig = array($context_item->getItemID().'_dates');
            $context_item->setMyCalendarDisplayConfig($displayConfig);
            $privateroom_manager->reset();
            $context_item->save();
         }

         $options[(string)$xml->item_id[0]] = $context_item->getItemId();
         
         if (((string)$xml->type[0]) == 'privateroom') {
           	$this->_environment->setCurrentContextID($context_item->getItemId());
           	$this->_environment->setCurrentContextItem($context_item);
           	$this->_environment->setCurrentUserItem($new_private_room_user_item);
           	$this->_environment->unsetAllInstancesExceptTranslator();
         }
         $this->import_sub_items($xml, $context_item, $options);
         if (((string)$xml->type[0]) == 'privateroom') {
            $this->_environment->setCurrentContextID($this->_environment->getCurrentPortalID());
         }
         
         $this->checkOptions($xml, $context_item, $options);
         
         $room_logo_filename = $context_item->getLogoFilename();
         $logo_matches = array();
         preg_match('/(?<=cid)(\d+)(?=_logo)/', $room_logo_filename, $logo_matches);
         if (!empty($logo_matches)) {
             if (isset($options[$logo_matches[0]])) {
                 $room_logo_filename = str_ireplace($logo_matches[0], $options[$logo_matches[0]], $room_logo_filename);
                 $context_item->setLogoFilename($room_logo_filename);
                 $context_item->save();
             }
         }
         
         return $context_item;
      }
   }
   
   function import_sub_items($xml, $top_item, &$options) {
      if ($xml != null) {
         if ($top_item->getRoomType() == 'community') {
            $project_manager = $this->_environment->getProjectManager();
            foreach ($xml->projects->children() as $project) {
               $temp_project_item = $project_manager->import_item($project, $top_item, $options);
               $community_room_array = array();
               $community_room_array[] = $top_item->getItemId();
               $temp_project_item->setCommunityListByID($community_room_array);
               $temp_project_item->save();
            }
         } else if ($top_item->getRoomType() == 'project') {
            $grouproom_manager = $this->_environment->getGrouproomManager();
            if (!empty($xml->grouprooms)) {
               foreach ($xml->grouprooms as $grouproom) {
                  $temp_grouproom_item = $grouproom_manager->import_item($grouproom->context_item, $top_item, $options);
               }
            }
         }
         
         if ($top_item->withTags()) {
            $tag_manager = $this->_environment->getTagManager();
            $tag_manager->forceSQL();
            $root_tag = $tag_manager->getRootTagItemFor($top_item->getItemId());
            foreach ($xml->tags->children() as $tag) {
               $tag_item = $this->importTagsFromXML($tag, $root_tag, $options);
            }
         }
         
         foreach ($xml->rubric->children() as $rubric) {
            if (($rubric->getName() != 'group') && ($rubric->getName() != 'topic') && ($rubric->getName() != 'label')) { // those items are included in the exported labels-xml
               $type_manager = $this->_environment->getManager($rubric->getName());
               if ($type_manager instanceof cs_export_import_interface) {
                  foreach ($rubric->children() as $item_xml) {
                     $type_manager->reset();
                     $temp_item = $type_manager->import_item($item_xml, $top_item, $options);
                  }
               }
            } else if ($rubric->getName() == 'label') {
               $type_manager = $this->_environment->getManager($rubric->getName());
               foreach ($rubric->children() as $item_xml) {
                  $import = true;
                  if (($item_xml->type == 'group') && ($item_xml->extras->SYSTEM_LABEL == '1')) {
                     $import = false;
                  }
                  if ($import) {
                     $temp_item = $type_manager->import_item($item_xml, $top_item, $options);
                  }
               }
            }
         }
         
         /* perform the import of link items as last task, as the matching $options['<old_item_id>'] = <new_item_id> is needed. */
         
         $links_manager = $this->_environment->getLinkManager();
         $links_manager->import_items($xml->links, $top_item, $options);
         
         $link_item_manager = $this->_environment->getLinkItemManager();
         foreach ($xml->link_items->children() as $link_xml) {
            $link_item = $link_item_manager->import_item($link_xml, $top_item, $options);
         }
      }
   }
   
   function checkOptions ($xml, $context_item, $options) {
      if (isset($options['check']['dates']['recurrence_id'])) {
         $dates_manager = $this->_environment->getDatesManager();
         foreach ($options['check']['dates']['recurrence_id'] as $item_id) {
            $temp_date_item = $dates_manager->getitem($item_id);
            $temp_date_old_recurrence_id = $temp_date_item->getRecurrenceId();
            $temp_date_new_recurrence_id = $options[$temp_date_old_recurrence_id];
            if ($temp_date_new_recurrence_id != '') {
               $temp_date_item->setRecurrenceId($temp_date_new_recurrence_id);
               $temp_date_item->save();
            }
         }
      }
      if (isset($options['check']['labels']['GROUP_ROOM_ID'])) {
         $group_manager = $this->_environment->getGroupManager();
         foreach ($options['check']['labels']['GROUP_ROOM_ID'] as $item_id) {
            $temp_group_item = $group_manager->getitem($item_id);
            $temp_group_old_grouproom_id = $temp_group_item->getGroupRoomItemID();
            if (isset($options[$temp_group_old_grouproom_id])) {
               $temp_group_new_grouproom_id = $options[$temp_group_old_grouproom_id];
               if ($temp_group_new_grouproom_id != '') {
                  $temp_group_item->setGroupRoomItemID($temp_group_new_grouproom_id);
                  $temp_group_item->save();
               }
            }
         }
      }
   }
}
?>