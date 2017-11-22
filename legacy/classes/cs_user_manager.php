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

/** date functions are needed for method _newVersion()
 */
include_once('functions/date_functions.php');

/** date functions are needed for method _newVersion() and _create() and _update
 */
include_once('functions/text_functions.php');

/** date functions are needed for lastlogin_limit
 */
include_once('functions/date_functions.php');

/** cs_list is needed for storage of the commsy items
 */
include_once('classes/cs_list.php');

/** cs_set is needed for caching user items
*/
include_once('classes/cs_set.php');


/** class for database connection to the database table "user"
 * this class implements a database manager for the table "user"
 */
class cs_user_manager extends cs_manager {

   var $_last_query = '';

   /**
   * integer - containing the age of user as a limit
   */
   var $_age_limit = NULL;

   /**
   * integer - containing a start point for the select user
   */
   var $_from_limit = NULL;

   var $_isset_room_user_cache = false;

   /**
   * integer - containing how many user the select statement should get
   */
   var $_interval_limit = NULL;

   var $_room_limit = NULL;

   var $_is_user_in_context_cache = array();


   /**
    * string - containing a string as a search limit for accounts
    */
   var $_account_search_limit = NULL;

   /**
   * integer - containing a status limit: 0 rejected, 1 registered, 2 normal user, 3 moderator
   */
   var $_status_limit = NULL;

   var $_status_select_limit = NULL;

   /**
   * integer - containing 0 for not public, 0 - none (not visible), 1 - Commsy only visible if logged in, 2 - All always visible, >= 1 AllandCommsy
   */
   var $_visible_limit = NULL;

   /**
   * string - containing a string: name of a user -> search method
   */
   var $_name_limit = NULL;

   /**
   * boolean - containing a flag: load only user that has login in already (true) or all (false)
   */
   var $_lastlogin_limit = false;

   /**
   *  array - containing an id-array as search limit
   */
   var $_id_array_limit = array();

   /**
    * array - containing the cached items already loaded from the database
    */
   var $_cache = array();

  /**
   * string - containing an order limit for the select users
   */
   var $_order = NULL;

  /**
   * document this limit (TBD)
   */
   var $_user_limit = NULL;
   
   var $_user_limit_binary = NULL;

  /**
   * document this limit (TBD)
   */
   var $_contact_moderator_limit = NULL;

  /**
   * document this limit (TBD)
   */
   var $_group_limit = NULL;

  /**
   * integer - containing the id of a institution as a limit for the selected contacts
   */
   var $_institution_limit = NULL;
   var $_topic_limit = NULL;

   var $_sort_order = NULL;

   var $_root_user = NULL;

   var $_context_array_limit = NULL;

   var $_status_project_limit = NULL;

   var $_auth_source_limit = NULL;

   var $_limit_community = NULL;

   var $_limit_project = NULL;

   var $_limit_portal_id = NULL;

   var $_cache_sql = array();

   private $_only_from_portal = false;

   private $_limit_no_membership = NULL;

   private $_limit_email = NULL;
   
   private $_limit_connection_key = NULL;
   private $_limit_connection_server_key = NULL;
   private $_limit_connection_own_key = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables<br />
    * NOTE: the constructor must never be called directly, instead the cs_environment must
    * be used to access this manager
    *
    * @param object cs_environment the environment
    */
   function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'user';
   }

   /** reset limits
    * reset limits of this class: age limit, group limit, from limit, interval limit, order limit and all limits from upper class
    */
   function resetLimits () {
      parent::resetLimits();
      $this->_user_id_limit = NULL;
      $this->_user_limit = NULL;
      $this->_age_limit = NULL;
      $this->_from_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_visible_limit = NULL;
      $this->_status_limit = NULL;
      $this->_status_project_limit = NULL;
      $this->_status_select_limit = NULL;
      $this->_lastlogin_limit = false;
      $this->_name_limit = NULL;
      $this->_group_limit = NULL;
      $this->_institution_limit = NULL;
      $this->_topic_limit = NULL;
      $this->_group_array_limit = NULL;
      $this->_order = NULL;
      $this->_sort_order = NULL;
      $this->_delete_limit = true;
      $this->_id_array_limit = array();
      $this->_context_array_limit = NULL;
      $this->_contact_moderator_limit = NULL;
      $this->_auth_source_limit = NULL;
      $this->_limit_community = NULL;
      $this->_limit_project = NULL;
      $this->_limit_portal_id = NULL;
      $this->_limit_no_membership = NULL;
      $this->_only_from_portal = false;
      $this->_limit_email = NULL;
      $this->_user_limit_binary = NULL;
      $this->_limit_connection_key = NULL;
      $this->_limit_connection_server_key = NULL;
      $this->_limit_connection_own_key = NULL;
   }

   public function setExternalConnectionUserKeyLimit ($value) {
      $this->_limit_connection_key = $value;
   }

   public function setExternalConnectionServerKeyLimit ($value) {
      $this->_limit_connection_server_key = $value;
   }

   public function setOwnConnectionUserKeyLimit ($value) {
      $this->_limit_connection_own_key = $value;
   }

   public function setEMailLimit ($value) {
      $this->_limit_email = $value;
   }

   public function setOnlyUserFromPortal () {
      $this->_only_from_portal = true;
   }

   function setAuthSourceLimit ($value) {
      $this->_auth_source_limit = (int)$value;
   }

   /** set age limit
    * this method sets an age limit for user
    *
    * @param integer limit age limit for user
    */
   function setAgeLimit ($limit) {
      $this->_age_limit = (int)$limit;
   }

   /** set interval limit
    * this method sets a interval limit
    *
    * @param integer from     from limit for selected user
    * @param integer interval interval limit for selected user
    */
   function setIntervalLimit ($from, $interval) {
      $this->_interval_limit = (integer)$interval;
      $this->_from_limit = (int)$from;
   }

   /** set visible limit, internal -> do not use
    *
    * @param integer limit visible limit for selected user
    */
  function _setVisibleLimit ($limit) {
     $this->_visible_limit = (string) $limit;
  }

  /** set order limit to name
    * this method sets an order limit for the select statement to name
    */
  function setVisibleToCommsy () {
     $this->_setVisibleLimit ('= "1"');
  }

  /** set order limit to name
    * this method sets an order limit for the select statement to name
    */
  function setVisibleToAll () {
     $this->_setVisibleLimit ('= "2"');
  }

  /** set order limit to name
    * this method sets an order limit for the select statement to name
    */
  function setVisibleToAllAndCommsy () {
     $this->_setVisibleLimit (' >= "1"');
  }

  /** set status limit to "rejected"
    * this method sets the status limit to "rejected"
    */
  function setRejectedLimit () {
     $this->_status_limit = 0;
  }

  /** set status limit to "registered"
    * this method sets the status limit to "registered"
    */
  function setRegisteredLimit () {
     $this->_status_limit = 1;
  }

  /** set status limit to "normal user"
    * this method sets the status limit to "normal user"
    */
  function setUserLimit ($limit = null) {
     $this->_status_limit = 2;
  }

  /** set status limit to "moderator"
    * this method sets the status limit to "moderator"
    */
  function setModeratorLimit () {
     $this->_status_limit = 3;
  }

/**
 * set status limit to "readonly"
 * this method sets the status limit to "readonly"
 */
  function setReadonlyLimit () {
    $this->_status_limit = 4;
  }

  function setStatusLimit ($limit){
    if ($limit == 6) {
       $this->_status_select_limit = (int)0;
    } elseif ($limit != 7) {
       $this->_status_select_limit = (int)$limit;
    }
  }

   function setUserInProjectLimit() {
      $this->_status_project_limit = 'user';
   }

   function setContactModeratorInProjectLimit() {
      $this->_status_project_limit = 'contact_moderator';
   }

  /** set group limit
    * this method sets a group limit for selected user
    *
    * @param integer limit id of the group
    */
  function setGroupLimit ($limit) {
     $this->_group_limit = (integer)$limit;
     $this->_group_array_limit = NULL; // there can be only one
  }

  /** set group array limit
    * this method sets a group array limit for selected user
    *
    * @param integer limit id of the group
    */
  function setGroupArrayLimit ($limit) {
     $this->_group_array_limit = (array)$limit;
     $this->_group_limit = NULL; // there can be only one
  }

  /** set name limit
    * this method sets the name limit
    */
  function setNameLimit ($name) {
     $this->_name_limit = encode(AS_DB,$name);
  }

   function setInstitutionLimit ($limit) {
      $this->_institution_limit = (int)$limit;
   }
   function setTopicLimit ($limit) {
      $this->_topic_limit = (int)$limit;
   }

   function setSortOrder ($order) {
      $this->_sort_order = (string)$order;
   }

  /** set lastlogin limit
    * this method sets the last login limit
    *
    * @param integer days in the past user has not logged in or empty: user has logged in
    */
  function setLastLoginLimit ($value = '') {
     if (empty($value)) {
        $this->_lastlogin_limit = 'empty';
     } else {
        $this->_lastlogin_limit = getCurrentDateTimeMinusDaysInMySQL($value);
     }
  }

  /** set user id limit
    * this method sets a user id limit for user
    *
    * @param string value user id limit for selected user
    */
  function setUserIDLimit ($value) {
     $this->_user_limit = (string)$value;
  }
  
  /** set user id limit with mysql binary (case sensitive)
   *  this method sets a user id limit for user (case sensitive)
   *  
   *  @param string value user id limit for selected user
   */
  function setUserIDLimitBinary($value) {
  	$this->_user_limit_binary = (string)$value;
  }

  function setContactModeratorLimit(){
     $this->_contact_moderator_limit = true;
  }

   /** set limit to array of context item_ids
    *
    * @param array array of ids of contexts user to be loaded from db
    */
   function setContextArrayLimit ($id_array){
      $this->_context_array_limit = (array)$id_array;
   }

   function setPortalIDLimit ( $value ) {
      $this->_limit_portal_id = (int)$value;
   }

   function setCommunityLimit () {
      $this->_limit_community = true;
   }

   function setProjectLimit () {
      $this->_limit_project = true;
   }

   public function setNoMemberShipLimit () {
      $this->_limit_no_membership = true;
   }

   /** set order limit
    * this method sets an order limit for the select statement
    *
    * @param string limit order limit for selected users
    */
   function setOrder ($limit) {
      $this->_order = (string)$limit;
   }

   /** get only the item ids of the selected items - should be deleted
     * (old style)
     *
     */
   function getIDs () {
      return $this->getIDArray();
   }

   private function _getSQLJoinForNoMemberShip () {
      $retour  = '';
      $current_portal = $this->_environment->getCurrentPortalItem();
      $room_id_array = $current_portal->getCommunityIDArray();
      $room_id_array = array_merge($room_id_array,$current_portal->getProjectIDArray());
      $room_id_array = array_merge($room_id_array,$current_portal->getGroupIDArray());
      if ( !empty($room_id_array) ) {
         $tmp_db_name = 'usernomem';
         $retour .= ' LEFT JOIN '.$this->addDatabasePrefix($this->_db_table).' AS '.$tmp_db_name;
         $retour .= ' ON '.$this->addDatabasePrefix($this->_db_table).'.user_id='.$tmp_db_name.'.user_id';
         $retour .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.auth_source='.$tmp_db_name.'.auth_source';
         $retour .= ' AND '.$tmp_db_name.'.deleter_id IS NULL';
         $retour .= ' AND '.$tmp_db_name.'.deletion_date IS NULL';
         $retour .= ' AND '.$tmp_db_name.'.context_id IN ('.implode(',',$room_id_array).')';
      }
      
      // archive
      if ( !$this->_environment->isArchiveMode() ) {
         $this->_environment->activateArchiveMode();
         $room_id_array = $current_portal->getCommunityIDArray();
         $room_id_array = array_merge($room_id_array,$current_portal->getProjectIDArray());
         $room_id_array = array_merge($room_id_array,$current_portal->getGroupIDArray());
         if ( !empty($room_id_array) ) {
            $tmp_db_name = 'usernomem_archive';
            $this->setWithDatabasePrefix();

            global $symfonyContainer;
            $this->_db_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix').'_';
            
            $retour .= ' LEFT JOIN '.$this->addDatabasePrefix($this->_db_table).' AS '.$tmp_db_name;
            $this->_db_prefix = '';
            $this->setWithoutDatabasePrefix();
            $retour .= ' ON '.$this->addDatabasePrefix($this->_db_table).'.user_id='.$tmp_db_name.'.user_id';
            $retour .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.auth_source='.$tmp_db_name.'.auth_source';
            $retour .= ' AND '.$tmp_db_name.'.deleter_id IS NULL';
            $retour .= ' AND '.$tmp_db_name.'.deletion_date IS NULL';
            $retour .= ' AND '.$tmp_db_name.'.context_id IN ('.implode(',',$room_id_array).')';
         }
         $this->_environment->deactivateArchiveMode();
      }
      // archive
      
      return $retour;
   }

   private function _getSQLLimitForNoMemberShip () {
      $retour  = '';
      $tmp_db_name = 'usernomem';
      $retour .= ' AND '.$tmp_db_name.'.auth_source IS NULL';
      
      // archive
      if ( !$this->_environment->isArchiveMode() ) {
         $tmp_db_name_archive = 'usernomem_archive';
         $retour .= ' AND '.$tmp_db_name_archive.'.auth_source IS NULL';
      }
      // archive
      
      return $retour;
   }

   function isUserInContext($user_id, $context_id, $auth_source){
      if (isset($this->_is_user_in_context_cache[$user_id.$auth_source])){
         if (isset($this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) and $this->_is_user_in_context_cache[$user_id.$auth_source][$context_id] == 'is_user'){
            return true;
         }else{
            return false;
         }
      }else{
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('user').'.context_id FROM '.$this->addDatabasePrefix('user');
         $query .= ' WHERE 1 AND '.$this->addDatabasePrefix('user').'.user_id = "'.$user_id.'" AND '.$this->addDatabasePrefix('user').'.auth_source = "'.$auth_source.'"';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('user').'.status >= "2" ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname DESC, '.$this->addDatabasePrefix('user').'.user_id ASC';
         #$query .= ' WHERE 1 AND user.user_id = "'.$user_id.'" AND user.auth_source = "'.$auth_source.'"';
         #$query .= ' AND user.deleter_id IS NULL AND user.deletion_date IS NULL AND user.status >= "2" ORDER BY user.lastname, user.firstname DESC, user.user_id ASC';
         if ( isset($this->_cache_sql[$query]) ) {
            $result = $this->_cache_sql[$query];
         } else {
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems selecting user.',E_USER_WARNING);
            } else {
               if ( $this->_cache_on ) {
                  $this->_cache_sql[$query] = $result;
               }
            }
         }
         if (isset($result)){
            foreach ($result as $r){
               $this->_is_user_in_context_cache[$user_id.$auth_source][$r['context_id']] = 'is_user';
            }
            if (isset($this->_is_user_in_context_cache[$user_id.$auth_source][$context_id]) and $this->_is_user_in_context_cache[$user_id.$auth_source][$context_id] == 'is_user'){
               return true;
            } else {
            	if ( $this->_environment->foundCurrentContextInArchive()
            	     and !$this->_environment->isArchiveMode()
            	     and !($this instanceof cs_zzz_user_manager)
            	   ) {
            		$zzz_user_manager = $this->_environment->getZZZUserManager();
            		$retour = $zzz_user_manager->isUserInContext($user_id, $context_id, $auth_source);
            		return $retour;
            	} else {
                  return false;
            	}
            }
         } else {
           	if ( $this->_environment->foundCurrentContextInArchive()
           	     and !$this->_environment->isArchiveMode()
           	     and !($this instanceof cs_zzz_user_manager)
               ) {
           		$zzz_user_manager = $this->_environment->getZZZUserManager();
           		$retour = $zzz_user_manager->isUserInContext($user_id, $context_id, $auth_source);
           		return $retour;
           	} else {
               return false;
           	}
         }
      }
   }

   /** INTERNAL: perform database query to get user data
     *
     */
   function _performQuery($mode = 'select') {
      if ( !empty($this->_user_limit)
           and mb_strtoupper($this->_user_limit) == 'GUEST'
         ) {
         return array();
      }

      if ($mode == 'count') {
         $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
          $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('user').'.item_id';
      } else {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('user').'.*';
      }

     $query .= ' FROM '.$this->addDatabasePrefix('user');
     if ( isset($this->_institution_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l11 ON ( l11.deletion_date IS NULL AND ((l11.first_item_id='.$this->addDatabasePrefix('user').'.item_id AND l11.second_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l12 ON ( l12.deletion_date IS NULL AND ((l12.second_item_id='.$this->addDatabasePrefix('user').'.item_id AND l12.first_item_type="'.CS_INSTITUTION_TYPE.'"))) ';
     }
     if ( isset($this->_topic_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('user').'.item_id AND l41.second_item_type="'.CS_TOPIC_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('user').'.item_id AND l42.first_item_type="'.CS_TOPIC_TYPE.'"))) ';
     }
     if ( isset($this->_group_limit) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l31 ON ( l31.deletion_date IS NULL AND ((l31.first_item_id='.$this->addDatabasePrefix('user').'.item_id AND l31.second_item_type="'.CS_GROUP_TYPE.'"))) ';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l32 ON ( l32.deletion_date IS NULL AND ((l32.second_item_id='.$this->addDatabasePrefix('user').'.item_id AND l32.first_item_type="'.CS_GROUP_TYPE.'"))) ';
     }
     if ($this->_status_project_limit) {
        // links over link_items to room
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS l91 ON ( l91.deletion_date IS NULL AND l91.second_item_id='.$this->addDatabasePrefix('user').'.context_id AND l91.first_item_type="'.CS_PROJECT_TYPE.'") ';
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('room').' ON ( '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND l91.first_item_id='.$this->addDatabasePrefix('room').'.item_id ) ';
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS l92 ON ( '.$this->addDatabasePrefix('room').'.item_id=l92.context_id AND l92.user_id='.$this->addDatabasePrefix('user').'.user_id) ';
     }
     if ( $this->_only_from_portal ) {
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS user2 ON ( user2.user_id='.$this->addDatabasePrefix('user').'.user_id AND user2.auth_source='.$this->addDatabasePrefix('user').'.auth_source) ';
     }

     if ( isset($this->_limit_portal_id)
          and ( isset($this->_limit_community)
                or isset($this->_limit_project)
              )
        ) {
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' AS user2 ON ( '.$this->addDatabasePrefix('user').'.user_id=user2.user_id and '.$this->addDatabasePrefix('user').'.auth_source=user2.auth_source ) ';
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('room').' ON ( '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND user2.context_id=room.item_id ) ';
     }

      if ( isset($this->_limit_no_membership) and  $this->_limit_no_membership  ) {
         $query .= $this->_getSQLJoinForNoMemberShip();
      }

     $query .= ' WHERE 1';

     if ( isset($this->_limit_email) ) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.email = "'.encode(AS_DB,$this->_limit_email).'"';
     }

     // fifth, insert limits into the select statement
     if (isset($this->_user_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB,$this->_user_limit).'"';
     }
     if (isset($this->_user_limit_binary)) {
     	$query .= ' AND BINARY '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB,$this->_user_limit_binary).'"';
     }
      
     if ( empty($this->_id_array_limit) ) {
        if ( isset($this->_context_array_limit)
             and !empty($this->_context_array_limit)
             and count($this->_context_array_limit) > 0
             and !empty($this->_context_array_limit[0])
           ) {
           $id_string = implode(',',$this->_context_array_limit);
           if ( $this->_only_from_portal ) {
              $query .= ' AND user2.context_id IN ('.encode(AS_DB,$id_string).')';
              $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentPortalID()).'"';
           } else {
              $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id IN ('.$id_string.')';
           }
        } elseif (isset($this->_room_limit) and $this->_room_limit != 0) {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
        } else {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.context_id IS NULL';
        }
     }

     if ( isset($this->_auth_source_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source = "'.encode(AS_DB,$this->_auth_source_limit).'"';
     }

     if ($this->_delete_limit == true) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
     }
     if ($this->_contact_moderator_limit == true) {
        if ( isset($this->_limit_portal_id)
             and ( isset($this->_limit_community)
                   or isset($this->_limit_project)
                 )
           ) {
           $query .= ' AND user2.is_contact="1"';
        } else {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.is_contact="1"';
        }
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }
     if ( isset($this->_existence_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.creation_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
     }
      if ( isset($this->_age_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('user').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
      }
     if (isset($this->_status_limit) and !isset($this->_status_select_limit)) {
        if ($this->_status_limit == 2) {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "'.encode(AS_DB,$this->_status_limit).'"';
        } else {
           if ( isset($this->_limit_portal_id)
                and ( isset($this->_limit_community)
                      or isset($this->_limit_project)
                    )
              ) {
              $query .= ' AND user2.status = "'.encode(AS_DB,$this->_status_limit).'"';
           } else {
              $query .= ' AND '.$this->addDatabasePrefix('user').'.status = "'.encode(AS_DB,$this->_status_limit).'"';
           }
        }
     }
     if (isset($this->_status_select_limit)) {
        if ($this->_status_select_limit == 8) {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.status >= "2"';
        } else {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.status = "'.encode(AS_DB,$this->_status_select_limit).'"';
        }
     }
     if ($this->_status_project_limit) {
        if ($this->_status_project_limit == 'user') {
           $query .= ' AND l92.is_contact="0" AND l92.status >= "2"';
        } elseif ($this->_status_project_limit == 'contact_moderator') {
           $query .= ' AND l92.is_contact="1" AND l92.status >= "2"';
        }
        $query .= ' AND l92.deleter_id IS NULL';
        $query .= ' AND l92.deletion_date IS NULL';
     }
     if ($this->_lastlogin_limit) {
        if ($this->_lastlogin_limit != 'empty') {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin > "'.encode(AS_DB,$this->_lastlogin_limit).'"';
        } else {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin IS NOT NULL AND user.lastlogin != "00-00-00 00:00:00"';
        }
     }

     if (isset($this->_visible_limit)) {
        $query .= " AND ".$this->addDatabasePrefix("user").".visible ". $this->_visible_limit;
     }

     if (isset($this->_name_limit)) {
        $name_array = explode(" ",$this->_name_limit);
        if (count($name_array) == 1) {
           $query .= ' AND ('.$this->addDatabasePrefix('user').'.firstname LIKE "'.encode(AS_DB,$name_array[0]).'" OR '.$this->addDatabasePrefix('user').'.lastname LIKE "'.encode(AS_DB,$name_array[0]).'")';
        } else {
           $query .= ' AND ('.$this->addDatabasePrefix('user').'.firstname LIKE "'.encode(AS_DB,$name_array[0]).'" AND '.$this->addDatabasePrefix('user').'.lastname LIKE "'.encode(AS_DB,$name_array[1]).'")';
        }
     }

     if ( !empty($this->_id_array_limit) ) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.item_id IN ('.implode(", ", $this->_id_array_limit).')';
     }
     
     // portal2Portal: connection key limit
     if ( !empty($this->_limit_connection_key) ) {
     	  $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%CONNECTION_EXTERNAL_KEY_ARRAY%"';
     	  $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%'.encode(AS_DB,$this->_limit_connection_key).'%"';
     }
     // portal2Portal: connection server key limit
     if ( !empty($this->_limit_connection_server_key) ) {
     	  $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%CONNECTION_ARRAY%"';
     	  $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%'.encode(AS_DB,$this->_limit_connection_server_key).'%"';
     }
     // portal2Portal: connection own key limit
     if ( !empty($this->_limit_connection_own_key) ) {
     	  $query .= ' AND '.$this->addDatabasePrefix('user').'.extras LIKE "%s:17:\"CONNECTION_OWNKEY\";s:32:\"'.$this->_limit_connection_own_key.'\"%"';
     }
     
      // restrict sql-statement by search limit, create wheres
      if (isset($this->_search_array) AND !empty($this->_search_array)) {
         $query .= ' AND ( 1 = 1';
   if (!isset($this->_attribute_limit) || ('all'==$this->_attribute_limit)){
      $field_array = array($this->addDatabasePrefix('user').'.city',$this->addDatabasePrefix('user').'.user_id',$this->addDatabasePrefix('user').'.firstname',$this->addDatabasePrefix('user').'.lastname',$this->addDatabasePrefix('user').'.email',$this->addDatabasePrefix('user').'.modification_date',$this->addDatabasePrefix('user').'.description','TRIM(CONCAT('.$this->addDatabasePrefix('user').'.firstname," ",'.$this->addDatabasePrefix('user').'.lastname))');
      $search_limit_query_code = ' AND '.$this->_generateSearchLimitCode($field_array);
      $query .= $search_limit_query_code;
   } else {
      if ('description' == $this->_attribute_limit) {
         $query .= $this->_generateSearchLimitCode(array($this->addDatabasePrefix('user').'.description'));
      }
      if (('modificator' == $this->_attribute_limit) || ('title'==$this->_attribute_limit)) {
               if ('description' == $this->_attribute_limit) {
                  $query .= 'OR';
               }
               $query .= $this->_generateSearchLimitCode(array('TRIM(CONCAT('.$this->addDatabasePrefix('user').'.firstname," ",'.$this->addDatabasePrefix('user').'.lastname))'));
            }
         }
         $query .= ')';
      }

      if ( isset($this->_topic_limit) ){
         if($this->_topic_limit == -1){
            $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
            $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
         } else {
            $query .= ' AND ((l41.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l41.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'")';
            $query .= ' OR (l42.first_item_id = "'.encode(AS_DB,$this->_topic_limit).'" OR l42.second_item_id = "'.encode(AS_DB,$this->_topic_limit).'"))';
         }
      }
      if ( isset($this->_institution_limit) ){
         if ($this->_institution_limit == -1){
            $query .= ' AND (l11.first_item_id IS NULL AND l11.second_item_id IS NULL)';
            $query .= ' AND (l12.first_item_id IS NULL AND l12.second_item_id IS NULL)';
         } else {
            $query .= ' AND ((l11.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l11.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'")';
            $query .= ' OR (l12.second_item_id = "'.encode(AS_DB,$this->_institution_limit).'" OR l12.first_item_id = "'.encode(AS_DB,$this->_institution_limit).'"))';
         }
      }
      if ( isset($this->_group_limit) ){
         if($this->_group_limit == -1){
            $query .= ' AND (l31.first_item_id IS NULL AND l31.second_item_id IS NULL)';
            $query .= ' AND (l32.first_item_id IS NULL AND l32.second_item_id IS NULL)';
         }else{
            $query .= ' AND ((l31.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l31.second_item_id = "'.encode(AS_DB,$this->_group_limit).'")';
            $query .= ' OR (l32.first_item_id = "'.encode(AS_DB,$this->_group_limit).'" OR l32.second_item_id = "'.encode(AS_DB,$this->_group_limit).'"))';
         }
      }

      if ( isset($this->_limit_portal_id)
          and ( isset($this->_limit_community)
                or isset($this->_limit_project)
              )
        ) {
        $query .= ' AND '.$this->addDatabasePrefix('room').'.context_id='.encode(AS_DB,$this->_limit_portal_id);
        if ( isset($this->_limit_community)
             and isset($this->_limit_project)
           ) {
           $query .= ' AND ('.$this->addDatabasePrefix('room').'.type="'.CS_COMMUNITY_TYPE.'" OR '.$this->addDatabasePrefix('room').'.type="'.CS_PROJECT_TYPE.'")';
        } elseif ( isset($this->_limit_community) ) {
           $query .= ' AND '.$this->addDatabasePrefix('room').'.type="'.CS_COMMUNITY_TYPE.'"';
        } elseif ( isset($this->_limit_project) ) {
           $query .= ' AND '.$this->addDatabasePrefix('room').'.type="'.CS_PROJECT_TYPE.'"';
        }
     }

      if ( isset($this->_limit_no_membership) and  $this->_limit_no_membership  ) {
         $query .= $this->_getSQLLimitForNoMemberShip();
      }

      if ( isset($this->_limit_portal_id)
           and ( isset($this->_limit_community)
                or isset($this->_limit_project)
              )
         ) {
         $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.user_id,'.$this->addDatabasePrefix('user').'.auth_source';
      }
     if ( ( isset($this->_search_limit)
            AND !empty($this->_search_limit)
          )
          OR isset($this->_status_select_limit)
        ) {
        $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
     }
     if (isset($this->_sort_order)) {
        if ($this->_sort_order == 'name') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname ASC, '.$this->addDatabasePrefix('user').'.firstname ASC, '.$this->addDatabasePrefix('user').'.user_id';
        } elseif ($this->_sort_order == 'name_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname DESC, '.$this->addDatabasePrefix('user').'.firstname DESC, '.$this->addDatabasePrefix('user').'.user_id';
        } elseif ($this->_sort_order == 'email') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.email ASC';
        } elseif ($this->_sort_order == 'email_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.email DESC';
        } elseif ($this->_sort_order == 'user_id') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.user_id ASC';
        } elseif ($this->_sort_order == 'user_id_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.user_id DESC';
        } elseif ($this->_sort_order == 'status') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.status ASC';
        } elseif ($this->_sort_order == 'status_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.status DESC';
        } elseif ($this->_sort_order == 'date') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.creation_date DESC';
        } elseif ($this->_sort_order == 'last_login') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastlogin ASC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname DESC';
        } elseif ($this->_sort_order == 'last_login_rev') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastlogin DESC, '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname DESC';
        }
     } else {
        $query .= ' ORDER BY '.$this->addDatabasePrefix('user').'.lastname, '.$this->addDatabasePrefix('user').'.firstname DESC, '.$this->addDatabasePrefix('user').'.user_id ASC';
     }

     if ($mode == 'select') {
        if (isset($this->_interval_limit) and isset($this->_from_limit)) {
           $query .= ' LIMIT '.$this->_from_limit.', '.$this->_interval_limit;
        }
     }
     $this->_last_query = $query;
      // perform query
      if ( isset($this->_cache_sql[$query]) ) {
         return $this->_cache_sql[$query];
      } else {
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting user.',E_USER_WARNING);
         } else {
            if ( $this->_cache_on ) {
               $this->_cache_sql[$query] = $result;
            }
            return $result;
         }
      }
   }

   function getLastQuery() {
      return $this->_last_query;
   }

   /** build a new user item
    * this method returns a new EMTPY user item
    *
    * @return object cs_item a new EMPTY user
    */
   function getNewItem () {
      include_once('classes/cs_user_item.php');
      return new cs_user_item($this->_environment);
   }

  /** get a user in newest version
    *
    * @param integer item_id id of the item
    *
    * @return object cs_item a label
    */
   function getItem ($item_id) {
      $user = NULL;
      if (isset($this->_cache[$item_id])) {
         $user = $this->_cache[$item_id];
      } elseif ( !empty($item_id) ) {
         $query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".item_id = '".encode(AS_DB,$item_id)."'";
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one user item.',E_USER_WARNING);
         } elseif ( !empty($result[0]) ) {
            $user = $this->_buildItem($result[0]);
            unset($result);
            if ( $this->_cache_on
                 and !array_key_exists($item_id,$this->_cache)
               ) {
               $this->_cache[$item_id] = $user;
            }
         }
      }
      return $user;
   }

   public function getItemByUserIDAuthSourceID ($uid, $asid) {
      $retour = NULL;
      if ( !empty($uid)
           and !empty($asid)
         ) {
         $this->resetLimits();
         $this->setUserIDLimit($uid);
         $this->setAuthSourceLimit($asid);
         $this->setContextLimit($this->_environment->getCurrentContextID());
         $this->select();
         $list = $this->get();
         if ( $list->isNotEmpty()
              and $list->getCount() == 1
            ) {
            $retour = $list->getFirst();
         } elseif ( $list->isNotEmpty()
                    and $list->getCount() > 1
                  ) {
            include_once('functions/error_functions.php');
            trigger_error('bug in database: multiple user for user_id: '.$uid.', auth_source_id: '.$asid.', portal: '.$this->_environment->getCurrentContextID().' - '.__FILE__.' - '.__LINE__,E_USER_ERROR);
         }
      }
      return $retour;
   }

   function getRoomUserByIDsForCache($context_id, $id_array = 0) {
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      if ( !$this->_cache_on ) {
         // do nothing
      } elseif ( !empty($context_id) and !empty($id_array)) {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.item_id IN ('.implode(",", $id_array).') AND '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB,$context_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
         $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
         } else {
            foreach ($result as $rs ) {
               $user = $this->_buildItem($rs);
               if (!array_key_exists($rs['item_id'],$this->_cache)){
                  $this->_cache[$rs['item_id']] = $user;
               }
            }
            unset($result);
         }
         unset($query);
      } elseif ( !empty($context_id)) {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE  '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB,$context_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
         $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
          $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
         } else {
            foreach ($result as $rs ) {
               $user = $this->_buildItem($rs);
               if (!array_key_exists($rs['item_id'],$this->_cache)){
                  $this->_cache[$rs['item_id']] = $user;
               }
             }
            unset($result);
         }
         unset($query);
      }
   }

   function getAllUsersByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id) {
      $retour = array();
      $user_array = $this->getUserArrayByUserAndRoomIDLimit($user_id,$room_id_array,$auth_source_id);
      if ( !empty($user_array) ) {
         foreach ($user_array as $key => $value) {
            $retour[$key] = $this->_buildItem($value);
         }
      }
      return $retour;
      /*
      $user_array = array();
      if ( isset($room_id_array) and !empty($room_id_array) ) {
         $query = 'SELECT * FROM user WHERE user.context_id IN ('.implode(",", $room_id_array).') AND user.user_id = "'.encode(AS_DB,$user_id).'" AND user.status >= "2"';
         $query .= ' AND user.deleter_id IS NULL';
         $query .= ' AND user.deletion_date IS NULL';
         $query .= ' AND user.auth_source = "'.$auth_source_id.'"';
         $query .= ' GROUP BY user.item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
         } else {
            foreach ($result as $rs ) {
               $user = $this->_buildItem($rs);
               $user_array[$rs['context_id']] = $user;
            }
            unset($result);
            unset($query);
         }
      }
      return $user_array;
      */
   }

   public function getMembershipContextIDArrayByUserAndRoomIDLimit ($user_id, $room_id_array, $auth_source_id) {
      $retour = array();
      $user_array = $this->getUserArrayByUserAndRoomIDLimit($user_id,$room_id_array,$auth_source_id);
      if ( !empty($user_array) ) {
         $room_id_array2 = array();
         foreach ($user_array as $value) {
            if ( !empty($value['context_id']) and $value['context_id'] > 0 ) {
               $room_id_array2[] = $value['context_id'];
            }
         }
         foreach ( $room_id_array as $value ) {
            if ( in_array($value,$room_id_array2) ) {
               $retour[] = $value;
            }
         }
      }
      return $retour;
   }

   function getUserArrayByUserAndRoomIDLimit($user_id, $room_id_array, $auth_source_id) {
      $user_array = array();
      if ( isset($room_id_array) and !empty($room_id_array) ) {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.context_id IN ('.implode(",", $room_id_array).') AND '.$this->addDatabasePrefix('user').'.user_id = "'.encode(AS_DB,$user_id).'" AND '.$this->addDatabasePrefix('user').'.status >= "2"';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deleter_id IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.deletion_date IS NULL';
         $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source = "'.$auth_source_id.'"';
         $query .= ' GROUP BY '.$this->addDatabasePrefix('user').'.item_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
         } else {
            foreach ($result as $rs ) {
               $user_array[$rs['context_id']] = $rs;
            }
            unset($result);
            unset($query);
         }
      }
      return $user_array;
   }

   function getAllRoomUsersFromCache ($context_id) {
      $user_list = new cs_list();
      if ( !empty($context_id) and !empty($this->_cache) ){
         foreach($this->_cache as $user){
            $user_list->add($user);
         }
      } else{
         $this->resetLimits();
         $this->setContextLimit($this->_environment->getCurrentContextID());
         $this->setUserLimit();
         $this->select();
         $user_list = $this->get();
      }
      return $user_list;
   }

   function getItemList($id_array) {
      return $this->_getItemList('user', $id_array);
   }

   function getRootUser () {
      if ( !isset($this->_root_user) ) {
         $this->setWithoutDatabasePrefix();
         $query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".user_id = 'root' AND context_id = '".encode(AS_DB,$this->_environment->getServerID())."'";
         $this->setWithDatabasePrefix();
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one user item.',E_USER_WARNING);
         } elseif ( !empty($result[0])) {
            $this->_root_user = $this->_buildItem($result[0]);
            unset($result);
         } else {
            include_once('functions/error_functions.php');
            trigger_error('can not get root user object - '.__LINE__.' - '.__FILE__,E_USER_WARNING);
         }
      }
      return $this->_root_user;
   }

   /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      include_once('functions/text_functions.php');
      $db_array['extras'] = mb_unserialize($db_array['extras']);
      return parent::_buildItem($db_array);
   }

   /** update a user - internal, do not use -> use method save
    * this method updates a user
    *
    * @param object cs_item user_item the user
    */
  function _update ($user_item, $with_creator_id = false) {
     parent::_update($user_item);
     $query  = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';
     if ( $user_item->isChangeModificationOnSave() ) {
        $modificator = $user_item->getModificatorItem();
        if ( isset($modificator) ) {
           $modifier_id = $modificator->getItemID();
           if ( !empty($modifier_id) ) {
              $query .= 'modifier_id="'.encode(AS_DB,$modifier_id).'",';
           }
           unset($modificator);
        }
        $query .= 'modification_date="'.encode(AS_DB,getCurrentDateTimeInMySQL()).'",';
     }

     $contact_status = $user_item->getContactStatus();
     if ( empty($contact_status) ) {
        $contact_status = 0;
     }
  	 
     $query .= 'context_id="'.encode(AS_DB,$user_item->getContextID()).'",';
     $query .= 'status="'.encode(AS_DB,$user_item->getStatus()).'",';
     $query .= 'is_contact="'.encode(AS_DB,$contact_status).'",';
     $query .= 'user_id="'.encode(AS_DB,$user_item->getUserID()).'",';
     $query .= 'auth_source="'.$user_item->getAuthSource().'",';
     $query .= 'firstname="'.encode(AS_DB,$user_item->getFirstname()).'",';
     $query .= 'lastname="'.encode(AS_DB,$user_item->getLastname()).'",';
     $query .= 'email="'.encode(AS_DB,$user_item->getRoomEmail()).'",';
     $query .= 'city="'.encode(AS_DB,$user_item->getCity()).'",';
     $query .= 'visible="'.encode(AS_DB,$user_item->getVisible()).'",';
     $query .= 'description="'.encode(AS_DB,$user_item->getDescription()).'",';
     $query .= 'use_portal_email="'.encode(AS_DB,$user_item->getUsePortalEmail()).'",';
     // Datenschutz
     $expire_date = $user_item->getPasswordExpireDate();
     
     if ( empty ($expire_date) or $expire_date == 0){
     	$query .= 'expire_date=NULL,';
     } else {
     	$query .= 'expire_date="'.encode(AS_DB,$expire_date).'",';
     }
     

     // if user was entered by system (creator_id == 0) then creator_id must change from 0 to item_id of the user_item
     // see methode _create()
     if ($with_creator_id) {
        $query .= 'creator_id="'.encode(AS_DB,$user_item->getCreatorID()).'",';
     }

     $query .= "extras='".encode(AS_DB,serialize($user_item->getExtraInformation()))."'";
     $query .= ' WHERE item_id="'.encode(AS_DB,$user_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems upating user item.',E_USER_ERROR);
     } else {
        unset($result);
     }
     unset($user_item);
  }

   /**
    * This method updates the last login of the user given user in the db.
    * The lastLogin will be setted to the current DateTime
    *
    * @param user_item Is the User, who will be updated.
    */
   function updateLastLoginOf ($user_item) {
      $datetime = getCurrentDateTimeInMySQL();
      $query  = 'UPDATE '.$this->addDatabasePrefix('user').' SET ';
      $query .= 'lastlogin="'.$datetime.'" ';
      $query .= 'WHERE item_id="'.encode(AS_DB,$user_item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems updating users last login.',E_USER_ERROR);
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
   function _create ($item) {

     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET ';
     $context_id = $item->getContextID();
     $query .= 'context_id="'.encode(AS_DB,$item->getContextID()).'", ';
     $query .= 'modification_date="'.getCurrentDateTimeInMySQL().'",'.
               'type="user"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating user.',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $item->setItemID($this->_create_id);
        $this->_newUser($item);
        unset($result);
     }
     unset($item);
  }

  /** creates a new user - internal, do not use -> use method save
    *
    * @param object cs_item user_item the user
    */
  function _newUser ($item) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $query =  'INSERT INTO '.$this->addDatabasePrefix('user').' SET '.
               'item_id="'.encode(AS_DB,$item->getItemID()).'", ';
     $context_id = $item->getContextID();
     $creator_id = $item->getCreatorID();
     if ( empty($creator_id) ) {
        $creator_id = $item->getItemID();
     }
     $query .= 'context_id="'.encode(AS_DB,$item->getContextID()).'", ';
     $query .= 'creator_id="'.encode(AS_DB,$creator_id).'",'.
               'creation_date="'.$current_datetime.'",'.
               'modification_date="'.$current_datetime.'",'.
               'user_id="'.encode(AS_DB,$item->getUserID()).'",'.
               'auth_source="'.encode(AS_DB,$item->getAuthSource()).'",'.
               'status="'.encode(AS_DB,$item->getStatus()).'",'.
               'firstname="'.encode(AS_DB,$item->getFirstName()).'",'.
               'lastname="'.encode(AS_DB,$item->getLastName()).'",'.
               'email="'.encode(AS_DB,$item->getEmail()).'",'.
               'city="'.encode(AS_DB,$item->getCity()).'",'.
               'visible="'.encode(AS_DB,$item->getVisible()).'",'.
               'description="'.encode(AS_DB,$item->getDescription()).'",'.
               'extras="'.encode(AS_DB,serialize($item->getExtraInformation())).'",'.
               'expire_date=NULL';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems insert new user item.', E_USER_ERROR);
     } else {
        unset($result);
     }
  }

  /** updates a new user - internal, do not use -> use method save
    * this method sets the creator id to the item id for new user at the portal
    *
    * @param object cs_item user_item the user
    */
   function _setCreatorID2ItemID ($item) {
      $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET '.
               'creator_id="'.encode(AS_DB,$item->getItemID()).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item->getItemID()).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems set creator id to item id.',E_USER_WARNING);
      } else {
         unset($result);
         return true;
      }
   }

  /**  delete a user item
   *
   * @param cs_user_item the user item to be deleted
   */
   function delete ($item_id) {
      $user_item = $this->getItem($item_id);
      if ( $this->_environment->inPortal() ) {
         if ( isset($user_item)
              and !empty($user_item)
              and $user_item->getContextID() == $this->_environment->getCurrentContextID()
         ) {
            // delete private room - part I
            $private_room_manager = $this->_environment->getPrivateRoomManager();
            $own_room = $private_room_manager->getRelatedOwnRoomForUser($user_item,$this->_environment->getCurrentPortalID());
            if ( isset($own_room) and !empty($own_room) ) {
               $room_id = $own_room->getItemID();
               if ( !empty($room_id) ) {
                   $delete_own_room = true;
               } else {
                  $delete_own_room = false;
               }
            }
            // delete related user in project rooms and community rooms and private room
            $user_list = $user_item->getRelatedUserList();
            if ( !$user_list->isEmpty() ) {
               $u_item = $user_list->getFirst();
               while ($u_item) {
                  $u_item->delete();
                  $u_item = $user_list->getNext();
               }
            }

            // delete private room - part II
            if ( isset($delete_own_room) and $delete_own_room ) {
               $own_room->delete();
            }
         }
      } else if($this->_environment->inProjectRoom()) {
         if ( isset($user_item)
              and !empty($user_item)
              and $user_item->getContextID() == $this->_environment->getCurrentContextID()
         ) {
           // delete related user in group rooms
           if($this->_environment->getCurrentPortalItem()->withGrouproomFunctions()) {
              // get all grouprooms of this user
              $grouproom_manager = $this->_environment->getGroupRoomManager();
              $grouproom_list = $grouproom_manager->getUserRelatedGroupListForUser($user_item);

              if(!$grouproom_list->isEmpty()) {
                 $grouproom_ids = array();
                 $grouproom = $grouproom_list->getFirst();
                while($grouproom) {
                   // is a group room of this project room?
                   $project_room = $grouproom->getLinkedProjectItem();
                   if ( !empty($project_room) ) {
                      $project_room_id = $project_room->getItemID();
                      if($this->_environment->getCurrentContextID() == $project_room_id) {
                         // add grouproom id to array of ids
                         $grouproom_ids[] = $grouproom->getItemID();
                      }
                   }
                   $grouproom = $grouproom_list->getNext();
                }

                // delete related users
                if(!empty($grouproom_ids)) {
                   $user_manager = $this->_environment->getUserManager();
                   $user_manager->resetLimits();
                   $user_manager->setContextArrayLimit($grouproom_ids);
                   $user_manager->setUserIDLimit($user_item->getUserID());
                   $user_manager->setAuthSourceLimit($user_item->getAuthSource());
                   $user_manager->select();
                   $user_list = $user_manager->get();
                   unset($user_manager);

                   if(!$user_list->isEmpty()) {
                      $user = $user_list->getFirst();
                      while($user) {
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
      $hash_manager = $this->_environment->getHashManager();
      $hash_manager->deleteHashesForUser($item_id);
      unset($hash_manager);

      // delete all related items
      $user_item->deleteAllEntriesOfUser();

      // delete the user item itself
      $current_datetime = getCurrentDateTimeInMySQL();
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix('user').' SET '.
               'deletion_date="'.$current_datetime.'",'.
               'deleter_id="'.encode(AS_DB,$user_id).'"'.
               ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting user.',E_USER_WARNING);
      } else {
         unset($result);
         parent::delete($item_id);
         return true;
      }
   }

   /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
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
        if ( $context_id == $portal_id ) {
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
     if ( empty($item_id)
          or ( $item->getLastStatus() != $item->getStatus()
               and $item->isUser()
               and $item->getLastStatus() < 2
             )
        ) {
        $private_room = $item->getOwnRoom();
        if ( isset($private_room) ) {
           $customized_room_id_array = $private_room->getCustomizedRoomIDArray();
           if ( !empty($customized_room_id_array)
                and !in_array($item->getContextID(),$customized_room_id_array)
              ) {
              $new_array = array();
              $new_array[] = $item->getContextID();
              $new_array = array_merge($new_array,$customized_room_id_array);
              $private_room->setCustomizedRoomIDArray($new_array);
              $private_room->save();
              unset($new_array);
              unset($customized_room_id_array);
           }
           unset($private_room);
        }
     }

     //Add modifier to all users who ever edited this user
     if ( $this->_link_modifier ) {
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $mod_id = $item->getModificatorID();
        if ( !empty($mod_id)
             and is_numeric($mod_id)
             and $mod_id > 99
           ) {
           $link_modifier_item_manager->markEdited($item->getItemID(),$mod_id);
        } else {
           $link_modifier_item_manager->markEdited($item->getItemID());
        }
     }
     unset($item);
  }

   function setCreatorID2ItemID ($item) {
      $this->_setCreatorID2ItemID($item);
   }

   function moveRoom($roomMover) {
      $query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE room_id = ".encode(AS_DB,$roomMover->getRoomId());
      $result = $this->_db_connector->performQuery($query);

      $user_ids_transformation = $roomMover->getTransformedUsers();
      foreach ( $result as $row ) {
         if ( !$roomMover->isUserInRoom($row['creator_id'],$roomMover->getRoomId())) {
            $creator = $this->_environment->getCurrentUser();
            $creator_id = $creator->getItemId();
            unset($creator);
         }
         if ( !empty($row['deleter_id']) and !$roomMover->isUserInRoom($row['deleter_id'],$roomMover->getRoomId())) {
            $deleter = $this->_environment->getCurrentUser();
            $deleter_id = $deleter->getItemId();
            unset($deleter);
         }

         $updateQuery = "UPDATE ".$this->addDatabasePrefix("user")." SET ";

         $oldUserId = $row['user_id'];
         if (isset($user_ids_transformation[$oldUserId])) {
            $newUserId = $user_ids_transformation[$oldUserId];
         } else {
            $newUserId = $oldUserId;
         }
         $updateQuery .= " user_id = '".encode(AS_DB,$newUserId)."', ";

         if ( isset($creator_id) ) {
            $updateQuery .= " creator_id='".encode(AS_DB,$creator_id)."', ";
            unset($creator_id);
         }
         if ( isset($deleter_id) ) {
            $updateQuery .= " deleter_id='".encode(AS_DB,$deleter_id)."', ";
            unset($deleter_id);
         }

         $updateQuery .= " context_id = ".encode(AS_DB,$roomMover->getRoomId());
         $updateQuery .= " WHERE user_id = '".encode(AS_DB,$oldUserId)."'";
         $updateQuery .= " AND context_id = '".encode(AS_DB,$roomMover->getOldRoomId())."'";
         $result2 = $this->_db_connector->performQuery($updateQuery);
         if ( !isset($result2) or !$result2 ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems user: move room.',E_USER_WARNING);
         } else {
            unset($result2);
         }
      }
      unset($result);
   }

   function mergeAccount($account_new,$account_old) {
      // implemented in class cs_authentication
   }

   function changeUserID ($new, $old_item) {
     $room_manager = $this->_environment->getRoomManager();
     $room_list = $room_manager->getAllRelatedRoomListForUser($old_item);
     $room_item_ids = array();
     $room_item_ids[] = $this->_environment->getCurrentPortalID();
     if ( !$room_list->isEmpty() ) {
        $room_item = $room_list->getFirst();
        while ( $room_item ) {
           $room_item_ids[] = $room_item->getItemID();
           $room_item = $room_list->getNext();
        }
     }

     # private room
     $own_room = $old_item->getOwnRoom();
     if ( isset($own_room) ) {
        $room_item_ids[] = $own_room->getItemID();
        unset($own_room);
     }
     # private room

     $update  = "UPDATE ".$this->addDatabasePrefix("user")." SET ";
     $update .= " user_id = '".encode(AS_DB,$new)."',";

     $update .= " modifier_id=creator_id,";
     $update .= " modification_date='".getCurrentDateTimeInMySQL()."'";
     $update .= " WHERE user_id = '".encode(AS_DB,$old_item->getUserID())."' AND context_id IN (".implode(',',encode(AS_DB,$room_item_ids)).") AND auth_source='".encode(AS_DB,$old_item->getAuthSource())."'";
     $result = $this->_db_connector->performQuery($update);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems changing user id.',E_USER_WARNING);
        $success = false;
     } else {
        unset($result);
        $success = true;
     }
     return $success;
  }

   public function getCountAuthSourceOfRoom ( $context_id ) {
      $query = 'SELECT count(DISTINCT '.$this->addDatabasePrefix('user').'.auth_source) as number FROM '.$this->addDatabasePrefix('user').' WHERE '.$this->addDatabasePrefix('user').'.context_id = "'.encode(AS_DB,$context_id).'" and '.$this->addDatabasePrefix('user').'.deletion_date IS NULL and '.$this->addDatabasePrefix('user').'.auth_source > 0';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting users.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }
      return $retour;
   }

   public function exists ($user_id, $auth_source = '') {
      $retour = false;
      $this->setUserIDLimit($user_id);
      if ( !empty($auth_source) ) {
         $this->setAuthSourceLimit($auth_source);
      }
      $this->select();
      $count = $this->getCountAll();
      if ( !empty($count) and $count > 0) {
         $retour = true;
      }
      return $retour;
   }

   ##########################################################
   # statistic functions
   ##########################################################

   function getCountUsers ($start, $end) {
      return $this->getCountUsedAccounts($start,$end);
   }

   function getCountNewUsers ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("user").".item_id) as number FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("user").".creation_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("user").".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting users.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }
      return $retour;
   }

   function getCountModUsers ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix("user").".item_id) as number FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix("user").".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix("user").".modification_date < '".encode(AS_DB,$end)."' and ".$this->addDatabasePrefix("user").".modification_date != ".$this->addDatabasePrefix("user").".creation_date";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting users.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }
      return $retour;
   }

   function getCountUsedAccounts ($start, $end) {
      $retour = 0;

      $query  = "SELECT count(DISTINCT ".$this->addDatabasePrefix("user").".email) as number FROM ".$this->addDatabasePrefix("user")." WHERE";
      if ( !empty($this->_context_array_limit)
           and count($this->_context_array_limit) > 0
         ) {
         $query .= " context_id IN (".implode(',',encode(AS_DB,$this->_context_array_limit)).")";
      } elseif (!empty($this->_room_limit)) {
         $query .= " context_id = '".encode(AS_DB,$this->_room_limit)."'";
      }
      $query .= " and lastlogin > '".encode(AS_DB,$start)."' and creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting used accounts.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountOpenAccounts ($start, $end) {
      $retour = 0;

      $query = "SELECT count(DISTINCT ".$this->addDatabasePrefix("user").".email) as number FROM ".$this->addDatabasePrefix("user")." WHERE";
      if ( !empty($this->_context_array_limit)
           and count($this->_context_array_limit) > 0
         ) {
         $query .= " context_id IN (".implode(',',encode(AS_DB,$this->_context_array_limit)).")";
      } elseif (!empty($this->_room_limit)) {
         $query .= " context_id = '".encode(AS_DB,$this->_room_limit)."'";
      }
      $query .= " and status >= 2 and (deletion_date IS NULL or deletion_date > '".encode(AS_DB,$end)."') and creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting open accounts.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountAllAccounts ($start, $end) {
      $retour = 0;

      $query = "SELECT count(DISTINCT ".$this->addDatabasePrefix("user").".email) as number FROM ".$this->addDatabasePrefix("user")." WHERE";
      if ( !empty($this->_context_array_limit)
           and count($this->_context_array_limit) > 0
         ) {
         $query .= " context_id IN (".implode(',',encode(AS_DB,$this->_context_array_limit)).")";
      } elseif (!empty($this->_room_limit)) {
         $query .= " context_id = '".encode(AS_DB,$this->_room_limit)."'";
      }
      $query .= " and ".$this->addDatabasePrefix("user").".creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting all accounts.',E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   function getCountPlugin ($plugin, $start, $end) {
      $retour = 0;

      $query = "SELECT ".$this->addDatabasePrefix($this->_db_table).".email,".$this->addDatabasePrefix($this->_db_table).".extras FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE";
      if ( !empty($this->_context_array_limit)
           and count($this->_context_array_limit) > 0
         ) {
         $query .= " context_id IN (".implode(',',encode(AS_DB,$this->_context_array_limit)).")";
      } elseif (!empty($this->_room_limit)) {
         $query .= " context_id = '".encode(AS_DB,$this->_room_limit)."'";
      }
      $query .= " and ".$this->addDatabasePrefix($this->_db_table).".extras LIKE '%LASTLOGIN_".mb_strtoupper($plugin)."%' and user.creation_date < '".encode(AS_DB,$end)."'";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting all accounts.',E_USER_WARNING);
      } else {
         $retour_array = array();
         include_once('functions/text_functions.php');
         foreach ($result as $rs) {
            $extra_array = array();
            if ( !empty($rs['extras']) ) {
               $extra_array = mb_unserialize($rs['extras']);
               if ( !empty($extra_array['LASTLOGIN_'.mb_strtoupper($plugin)])
                    and $extra_array['LASTLOGIN_'.mb_strtoupper($plugin)] > $start
                  ) {
                  $retour_array[] = $rs['email'];
               }
            }
         }
         unset($result);

         if ( !empty($retour_array) ) {
            $retour_array = array_unique($retour_array);
            $retour = count($retour_array);
         }
      }

      return $retour;
   }

   function resetCacheSQL(){
      $this->_cache_sql = array();
   }
	
	####################################################
	# archive method
	####################################################
	
	public function getLastUsedDateOfRoom ( $room_id ) {
		$retour = '';
		if ( !empty($room_id) ) {
			$query = 'SELECT lastlogin FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE context_id = '.$room_id.' AND lastlogin IS NOT NULL ORDER BY lastlogin DESC LIMIT 0,1';
			$result = $this->_db_connector->performQuery($query);
			if ( !isset($result) ) {
				include_once('functions/error_functions.php');
				trigger_error('Problems getting last used date of this room: '.$room_id,E_USER_WARNING);
			} elseif ( !empty($result[0]['lastlogin']) ) {
				$retour = $result[0]['lastlogin'];
			}	
		}
		return $retour;
	}
	
	public function getUserPasswordExpiredByContextID($cid) {
		$user_array = array();
		$current_date = getCurrentDateTimeInMySQL();
		$user = NULL;
		$query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".expire_date IS NOT NULL AND ".$this->addDatabasePrefix("user").".deletion_date IS NULL AND ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$cid)."' AND ".$this->addDatabasePrefix("user").".expire_date  <= '".encode(AS_DB,$current_date)."'";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
		} else {
			foreach ($result as $rs ) {
				$user_array[] = $this->_buildItem($rs);
			}
			unset($result);
			unset($query);
		}
		
		return $user_array;
		

	}
	
	public function getCountUserPasswordExpiredByContextID($cid) {
		$retour = 0;
		$date = getCurrentDateTimeInMySQL();
		$query = "SELECT count(DISTINCT ".$this->addDatabasePrefix("user").".item_id) as number FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".expire_date IS NOT NULL AND ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$cid)."' AND ".$this->addDatabasePrefix("user").".expire_date  <= '".encode(AS_DB,$date)."'";
		$query .= " and deletion_date IS NULL";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems counting open accounts.',E_USER_WARNING);
		} else {
			foreach ($result as $rs) {
				$retour = $rs['number'];
			}
			unset($result);
		}
		return $retour;
	}
	
	public function getCountUserPasswordExpiredSoonByContextID($cid, $portal_item = NULL) {
		$retour = 0;
		$days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();
		if(isset($days_before_expiring_sendmail)){
			$date = getCurrentDateTimePlusDaysInMySQL($days_before_expiring_sendmail, true);
		} else {
			$date = getCurrentDateTimePlusDaysInMySQL('14', true);
		}
		$now = getCurrentDateTimeInMySQL();
		$query = "SELECT count(DISTINCT ".$this->addDatabasePrefix("user").".item_id) as number FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".expire_date IS NOT NULL AND deletion_date IS NULL AND ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$cid)."' AND ".$this->addDatabasePrefix("user").".expire_date BETWEEN '".encode(AS_DB,$now)."' AND '".encode(AS_DB,$date)."'";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems counting open accounts.',E_USER_WARNING);
		} else {
			foreach ($result as $rs) {
				$retour = $rs['number'];
			}
			unset($result);
		}
		return $retour;
	}
	
	public function getUserPasswordExpiredSoonByContextID($cid, $portal_item = NULL) {
		$days_before_expiring_sendmail = $portal_item->getDaysBeforeExpiringPasswordSendMail();
		
		if(isset($days_before_expiring_sendmail)){
			$date = getCurrentDateTimePlusDaysInMySQL($days_before_expiring_sendmail);
		} else {
			$date = getCurrentDateTimePlusDaysInMySQL('14');
		}
        $now = getCurrentDateTimeInMySQL();
		$user = NULL;
		$query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".expire_date IS NOT NULL AND ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$cid)."' AND ".$this->addDatabasePrefix("user").".deletion_date IS NULL AND ".$this->addDatabasePrefix("user").".expire_date BETWEEN '".encode(AS_DB,$now)."' AND '".encode(AS_DB,$date)."'";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
		} else {
			foreach ($result as $rs ) {
				$user_array[] = $this->_buildItem($rs);
			}
			unset($result);
			unset($query);
		}
		return $user_array;
	}
	
	public function getUserTempLoginExpired(){
		$user = NULL;
		$user_array = array();
		$query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".status = '3' AND ".$this->addDatabasePrefix("user").".deletion_date IS NULL AND ".$this->addDatabasePrefix("user").".extras LIKE '%LOGIN_AS_TMSP%'";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
		} else {
			foreach ($result as $rs ) {
				$user_array[] = $this->_buildItem($rs);
			}
			unset($result);
			unset($query);
		}
		return $user_array;
	}
	
	public function getUserLastLoginLaterAs($date,$cid, $status = 2){
		$user = NULL;
		$user_array = array();
		$query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".lastlogin <= '".encode(AS_DB,$date)."' AND ".$this->addDatabasePrefix("user").".deletion_date IS NULL AND ".$this->addDatabasePrefix("user").".status >= ".encode(AS_DB,$status)." AND ".$this->addDatabasePrefix("user").".context_id = '".encode(AS_DB,$cid)."'";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
		} else {
			foreach ($result as $rs ) {
				$user_array[] = $this->_buildItem($rs);
			}
			unset($result);
			unset($query);
		}
		return $user_array;
	}
	
	public function getAllUserItemArray($uid){
		$user = NULL;
		$user_array = array();
		$query = "SELECT * FROM ".$this->addDatabasePrefix("user")." WHERE ".$this->addDatabasePrefix("user").".user_id = '".encode(AS_DB,$uid)."' AND ".$this->addDatabasePrefix("user").".deletion_date IS NULL";
		$result = $this->_db_connector->performQuery($query);
		if ( !isset($result) ) {
			include_once('functions/error_functions.php');
			trigger_error('Problems selecting list of '.$this->_type.' items.',E_USER_WARNING);
		} else {
			foreach ($result as $rs ) {
				$user_array[] = $this->_buildItem($rs);
			}
			unset($result);
			unset($query);
		}
		return $user_array;
	}
}
?>