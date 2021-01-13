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
include_once('functions/date_functions.php');

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

   var $_list_limit = NULL;

   var $_matrix_limit = NULL;

   var $_interval_limit = NULL;

   var $_type_array_limit = array();

   var $_user_userid_limit = NULL;
   var $_user_authsourceid_limit = NULL;
   var $_user_since_lastlogin_limit = NULL;
   var $_cache_row = array();
   private $_no_interval_limit = false;
   
  /**
   * integer - containing the age of material as a limit
   */
  var $_type = NULL;

  /** constructor: cs_item_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
     cs_manager::__construct($environment);
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
      $this->_order_limit = NULL;
      $this->_list_limit = NULL;
      $this->_matrix_limit = NULL;
      $this->_label_limit = NULL;
      $this->_interval_limit = NULL;
      $this->_type_array_limit = array();
      $this->_user_userid_limit = NULL;
      $this->_user_authsourceid_limit = NULL;
      $this->_user_since_lastlogin_limit = NULL;
      $this->_no_interval_limit = false;
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

   function setNoIntervalLimit () {
     $this->_no_interval_limit = true;
   }

   function setTypeArrayLimit ($array) {
     $this->_type_array_limit = $array;
   }

   function setOrderLimit ($limit) {
     $this->_order_limit = $limit;
   }

   function setListLimit ($limit) {
     $this->_list_limit = $limit;
   }

   function setMatrixLimit ($limit) {
     $this->_matrix_limit = $limit;
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
        $query = 'SELECT count('.$this->addDatabasePrefix('items').'.item_id) AS count';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('items').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('items').'.*,label.type AS subtype';
     }
     $query .= ' FROM '.$this->addDatabasePrefix('items');
     $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';

     if ( isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
          and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
        ) {
        $query .= ' INNER JOIN '.$this->addDatabasePrefix('user').' ON '.$this->addDatabasePrefix('user').'.context_id='.$this->addDatabasePrefix($this->_db_table).'.context_id';
     }
     
     if (isset($this->_list_limit)) {
        if ($this->_list_limit == -1){
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="buzzword_for"';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
        }else{
           $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="buzzword_for"';
           $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
        }
     }

     if ( isset($this->_tag_limit) ) {
          $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('items').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('items').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
       }

     $query .= ' WHERE 1';
     $query .= ' AND '.$this->addDatabasePrefix('items').'.draft != "1"';

     if (isset($this->_list_limit)) {
         if ($this->_list_limit ==-1){
            $query .= ' AND (links.to_item_id IS NULL OR links.deletion_date IS NOT NULL)';
         }else{
            $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_list_limit).'"';
         }
     }

     if ( isset($this->_tag_limit) ) {
           $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
           $id_string = implode(', ',$tag_id_array);
           if( isset($tag_id_array[0]) and $tag_id_array[0] == -1 ){
              $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
              $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
           }else{
              $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB,$id_string).') OR l41.second_item_id IN ('.encode(AS_DB,$id_string).') )';
              $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB,$id_string).') OR l42.second_item_id IN ('.encode(AS_DB,$id_string).') ))';
           }
        }

       switch ($this->inactiveEntriesLimit) {
           case self::SHOW_ENTRIES_ONLY_ACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('items') . '.modification_date IS NULL OR ' . $this->addDatabasePrefix('items') . '.modification_date <= "' . getCurrentDateTimeInMySQL() . '")';
               break;
           case self::SHOW_ENTRIES_ONLY_DEACTIVATED:
               $query .= ' AND (' . $this->addDatabasePrefix('items') . '.modification_date IS NOT NULL AND ' . $this->addDatabasePrefix('items') . '.modification_date > "' . getCurrentDateTimeInMySQL() . '")';
               break;
       }

     if ( isset($this->_existence_limit) ) {
         $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_existence_limit).' day)';
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
           $query .=' '.$this->addDatabasePrefix('items').'.type = "'.encode(AS_DB,$type).'"';
        }
        $query .= ' )';
     }
     if (isset($this->_room_limit) and empty($this->_room_array_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } elseif(empty($this->_room_array_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if (isset($this->_id_array_limit) and !empty($this->_id_array_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('items').'.item_id IN ('.implode(", ",encode(AS_DB,$this->_id_array_limit)).')';
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
           $query .=' '.$this->addDatabasePrefix('items').'.type = "'.encode(AS_DB,$type).'"';
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
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
     }
     if (isset($this->_age_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
     }

     if ( isset($this->_user_userid_limit) and !empty($this->_user_userid_limit)
          and isset($this->_user_authsourceid_limit) and !empty($this->_user_authsourceid_limit)
        ) {
        $query .= ' AND '.$this->addDatabasePrefix('user').'.user_id="'.encode(AS_DB,$this->_user_userid_limit).'"';
        $query .= ' AND '.$this->addDatabasePrefix('user').'.auth_source="'.encode(AS_DB,$this->_user_authsourceid_limit).'"';
        if ( isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit ) {
           $query .= ' AND '.$this->addDatabasePrefix('user').'.lastlogin < '.$this->addDatabasePrefix($this->_db_table).'.modification_date';
        }
     }
     //context array limit
     if( !empty($this->_room_array_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.context_id IN ('.implode(", ",encode(AS_DB,$this->_room_array_limit)).')';
      }
        $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
      if (!isset($this->_id_array_limit)) {
         if ($mode == 'select' and !(isset($this->_user_sincelastlogin_limit) and $this->_user_sincelastlogin_limit)
         	 and !$this->_no_interval_limit
         	) {
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
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     } else {
         return $result;
     }
   }



  function getItemList ($id_array) {
     return $this->_getItemList('items', $id_array);
  }

  function getPrivateRoomHomeItemList ($id_array) {
      /** cs_list is needed for storage the commsy items
       */
      $type = 'items';
      include_once('classes/cs_list.php');
      if ( empty($id_array) ) {
         return new cs_list();
      } else {
         if ( $type == 'discussion' ) {
            $type = 'discussions';
         }
         elseif ( $type == 'todo' ) {
            $type = 'todos';
         }
         $query = "SELECT * FROM ".encode(AS_DB,$this->addDatabasePrefix($type))." WHERE ".encode(AS_DB,$this->addDatabasePrefix($type)).".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$type.' items.',E_USER_WARNING);
         } else {
            $list = new cs_list();
            foreach ($result as $rs ) {
               // special for todo
               if ( $type == 'todos' and isset($rs['date']) ){
                  $rs['end_date'] = $rs['date'];
                  unset($rs['date']);
               }
               $list->add($this->_buildItem($rs));
            }
            unset($result);
         }
         unset($query);
         unset($id_array);
         unset($type);

         return $list;
      }
  }


  function getPrivateRoomItemList ($id_array,$user_ids) {
      /** cs_list is needed for storage the commsy items
       */
      $type = 'items';
      include_once('classes/cs_list.php');
      if ( empty($id_array) ) {
         return new cs_list();
      } else {
         if ( $type == 'discussion' ) {
            $type = 'discussions';
         }
         elseif ( $type == 'todo' ) {
            $type = 'todos';
         }
         $query = "SELECT DISTINCT * FROM ".encode(AS_DB,$this->addDatabasePrefix($type));
         $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_modifier_item').' AS modifier ON '.$this->addDatabasePrefix('items').'.item_id=modifier.item_id';
         $query .=" WHERE ".encode(AS_DB,$this->addDatabasePrefix($type)).".item_id IN ('".implode("', '",encode(AS_DB,$id_array))."')";
         $query .= ' AND modifier.modifier_id IN ('.implode(",",encode(AS_DB,$user_ids)).')';;
         $query .= ' GROUP BY '.$this->addDatabasePrefix('items').'.item_id ';
         $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of '.$type.' items.',E_USER_WARNING);
         } else {
            $list = new cs_list();
            foreach ($result as $rs ) {
               // special for todo
               if ( $type == 'todos' and isset($rs['date']) ){
                  $rs['end_date'] = $rs['date'];
                  unset($rs['date']);
               }
               $list->add($this->_buildItem($rs));
            }
            unset($result);
         }
         unset($query);
         unset($id_array);
         unset($type);

         return $list;
      }
  }



   function getAllUsedRubricsOfRoomList($room_ids){
        $rs = array();
        $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.context_id, '.$this->addDatabasePrefix('items').'.type, label.type AS subtype';
        $query .= ' FROM '.$this->addDatabasePrefix('items');
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
        $query .= ' WHERE 1';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
        }
        $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.context_id DESC';
        // perform query
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result)) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
        } else {
           $rs = $result;
        }
        return $rs;
   }



   function getAllPrivateRoomEntriesOfUserList($room_ids,$user_ids){
        $rs = array();
        $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.*, modifier.modifier_id';
        if (isset($this->_search_array[0])) {
           $query .= ', materials.title, materials.description, materials.extras'.LF;
           $query .= ', todos.title, todos.description';
           $query .= ', discarticles.subject, discarticles.description';
           $query .= ', dates.title, dates.description, dates.place';
           $query .= ', discussions.title';
        }
        $query .= ' FROM '.$this->addDatabasePrefix('items');
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id';
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_modifier_item').' AS modifier ON '.$this->addDatabasePrefix('items').'.item_id=modifier.item_id';

       // restrict materials by buzzword (la4)
       if (isset($this->_list_limit)) {
          if ($this->_list_limit == -1){
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="in_mylist"';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS mylists ON links.to_item_id=mylists.item_id AND mylists.type="mylist"';
          }else{
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS links ON links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND links.link_type="in_mylist"';
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS mylists ON links.to_item_id=mylists.item_id AND mylists.type="mylist"';
          }
       }
       if (isset($this->_matrix_limit)) {
          $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS matrix_x ON matrix_x.first_item_id='.$this->addDatabasePrefix('items').'.item_id';
          $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_items').' AS matrix_y ON matrix_y.first_item_id='.$this->addDatabasePrefix('items').'.item_id';
       }



       if (isset($this->_buzzword_limit)) {
          if ($this->_buzzword_limit == -1){
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('links').' AS buzzword_links ON buzzword_links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND buzzword_links.link_type="buzzword_for"';
             $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON buzzword_links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
          }else{
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('links').' AS buzzword_links ON buzzword_links.from_item_id='.$this->addDatabasePrefix('items').'.item_id AND buzzword_links.link_type="buzzword_for"';
             $query .= ' INNER JOIN '.$this->addDatabasePrefix('labels').' AS buzzwords ON buzzword_links.to_item_id=buzzwords.item_id AND buzzwords.type="buzzword"';
          }
       }

       if ( isset($this->_tag_limit) ) {
          $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l41 ON ( l41.deletion_date IS NULL AND ((l41.first_item_id='.$this->addDatabasePrefix('items').'.item_id AND l41.second_item_type="'.CS_TAG_TYPE.'"))) ';
          $query .= ' LEFT JOIN '.$this->addDatabasePrefix('link_items').' AS l42 ON ( l42.deletion_date IS NULL AND ((l42.second_item_id='.$this->addDatabasePrefix('items').'.item_id AND l42.first_item_type="'.CS_TAG_TYPE.'"))) ';
       }

        if (isset($this->_search_array[0])) {
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('materials').' AS materials ON materials.item_id='.$this->addDatabasePrefix('items').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('todos').' AS todos ON todos.item_id='.$this->addDatabasePrefix('items').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('discussionarticles').' AS discarticles ON discarticles.item_id='.$this->addDatabasePrefix('items').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('dates').' AS dates ON dates.item_id='.$this->addDatabasePrefix('items').'.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('discussions').' AS discussions ON discussions.item_id='.$this->addDatabasePrefix('items').'.item_id';
        }

        $query .= ' WHERE 1';
        $query .= ' AND (label.type IS NULL OR label.type="group" OR label.type="topic")';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "tag"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "project"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "community"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "grouproom"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "discarticle"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "section"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "step"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "user"';
        $query .= ' AND modifier.modifier_id IN ('.implode(",",encode(AS_DB,$user_ids)).')';;
        if (isset($this->_list_limit)) {
           if ($this->_list_limit ==-1){
              $query .= ' AND (links.to_item_id IS NULL OR links.deletion_date IS NOT NULL)';
           }else{
              $query .= ' AND mylists.item_id="'.encode(AS_DB,$this->_list_limit).'"';
           }
        }
        if (isset($this->_matrix_limit)) {
           $array = explode('_',$this->_matrix_limit);
           $pos_x = $array[0];
           $pos_y = $array[1];
           if ($this->_matrix_limit !=-1){
              $query .= ' AND matrix_x.second_item_id="'.$pos_x.'" AND  matrix_y.second_item_id="'.$pos_y.'"';
              $query .= ' AND matrix_x.deleter_id IS NULL';
              $query .= ' AND matrix_x.deletion_date IS NULL';
              $query .= ' AND matrix_y.deleter_id IS NULL';
              $query .= ' AND matrix_y.deletion_date IS NULL';
           }
        }
        if (isset($this->_buzzword_limit)) {
           if ($this->_buzzword_limit ==-1){
              $query .= ' AND (buzzword_links.to_item_id IS NULL OR buzzword_links.deletion_date IS NOT NULL)';
           }else{
              $query .= ' AND buzzwords.item_id="'.encode(AS_DB,$this->_buzzword_limit).'"';
           }
        }

        if ( isset($this->_tag_limit) ) {
           $tag_id_array = $this->_getTagIDArrayByTagIDArray($this->_tag_limit);
           $id_string = implode(', ',$tag_id_array);
           if( isset($tag_id_array[0]) and $tag_id_array[0] == -1 ){
              $query .= ' AND (l41.first_item_id IS NULL AND l41.second_item_id IS NULL)';
              $query .= ' AND (l42.first_item_id IS NULL AND l42.second_item_id IS NULL)';
           }else{
              $query .= ' AND ( (l41.first_item_id IN ('.encode(AS_DB,$id_string).') OR l41.second_item_id IN ('.encode(AS_DB,$id_string).') )';
              $query .= ' OR (l42.first_item_id IN ('.encode(AS_DB,$id_string).') OR l42.second_item_id IN ('.encode(AS_DB,$id_string).') ))';
           }
        }

        if (isset($this->_search_array) AND !empty($this->_search_array)) {
           $query .= ' AND (';
           $field_array = array('materials.description',
                                'materials.title',
                                'materials.author',
                                'materials.extras',
                                'discussions.title',
                                'discarticles.subject',
                                'discarticles.description',
                                'dates.title',
                                'dates.description',
                                'dates.place',
                                'todos.title',
                                'todos.description',
                                'todos.extras'
                                );
           $search_limit_query_code = $this->_generateSearchLimitCode($field_array);
           $query .= $search_limit_query_code;
           $query .= ')';
        }

        $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
        if (isset($this->_interval_limit)) {
           $query .= ' LIMIT ';
           $query .= $this->_interval_limit;
        }else{
           $query .= '';
        }
        // perform query
        $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting list of items.',E_USER_WARNING);
            trigger_error('Problems selecting list of entry items.',E_USER_WARNING);
         } else {
            $list = new cs_list();
            foreach ($result as $rs ) {
               // special for todo
               $list->add($this->_buildItem($rs));
            }
            unset($result);
         }

        return $list;
   }

   function getAllNewPrivateRoomEntriesOfRoomList($room_ids){
        $rs = array();
        if ( !empty($room_ids) ) {
           $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.item_id, label.type';
           $query .= ' FROM '.$this->addDatabasePrefix('items');
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('materials').' AS materials ON '.$this->addDatabasePrefix('items').'.item_id=materials.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('dates').' AS dates ON '.$this->addDatabasePrefix('items').'.item_id=dates.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('todos').' AS todos ON '.$this->addDatabasePrefix('items').'.item_id=todos.item_id';
           $query .= ' LEFT JOIN '.$this->addDatabasePrefix('discussions').' AS discussions ON '.$this->addDatabasePrefix('items').'.item_id=discussions.item_id';
           $query .= ' WHERE 1';
           $query .= ' AND (label.type IS NULL OR label.type="group" OR label.type="topic" OR label.type="group")';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "tag"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "project"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "community"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "grouproom"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "discarticle"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "section"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "step"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "user"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "assessments"';
           if (isset($this->_age_limit)) {
              $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
           }
           $query .= ' AND ('.$this->addDatabasePrefix('materials').'.modification_date IS NULL OR '.$this->addDatabasePrefix('materials').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
           $query .= ' AND ('.$this->addDatabasePrefix('dates').'.modification_date IS NULL OR '.$this->addDatabasePrefix('dates').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
           $query .= ' AND ('.$this->addDatabasePrefix('todos').'.modification_date IS NULL OR '.$this->addDatabasePrefix('todos').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
           $query .= ' AND ('.$this->addDatabasePrefix('discussions').'.modification_date IS NULL OR '.$this->addDatabasePrefix('discussions').'.modification_date <= "'.getCurrentDateTimeInMySQL().'")';
           $query .= ' ORDER BY '.$this->addDatabasePrefix('items').'.modification_date DESC';
           $query .= ' LIMIT ';
           if (isset($this->_interval_limit)) {
              $query .= $this->_interval_limit;
           }else{
              $query .= '20';
           }
           // perform query
           $result = $this->_db_connector->performQuery($query);
           if (!isset($result)) {
              include_once('functions/error_functions.php');
              trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
           } else {
               foreach ( $result as $query_result ) {
                   $rs[] = $query_result['item_id'];
              }
           }
        }
        return $rs;
   }


   function getAllNewEntriesOfRoomList($room_ids){
        $rs = array();
        $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.item_id';
        $query .= ' FROM '.$this->addDatabasePrefix('items');
        $query .= ' WHERE 1';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(",",encode(AS_DB,$room_ids)).')';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
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
        $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('items').'.type, label.type AS subtype';
        $query .= ' FROM '.$this->addDatabasePrefix('items');
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('labels').' AS label ON '.$this->addDatabasePrefix('items').'.item_id=label.item_id AND (label.type="institution" OR label.type="topic" OR label.type="group")';
        $query .= ' WHERE 1';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id ="'.encode(AS_DB,$room_id).'"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "annotation"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "discarticle"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "section"';
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "link_item"';
        if (isset($this->_age_limit)){
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "announcement"';
           $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "date"';
        }
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type != "task"';
        if (isset($this->_age_limit)) {
           $query .= ' AND '.$this->addDatabasePrefix('items').'.modification_date > DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$this->_age_limit).' day)';
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
      $query = 'SELECT count('.$this->addDatabasePrefix('items').'.item_id) AS count';
      $query .= ' FROM '.$this->addDatabasePrefix('items');
      $query .= ' INNER JOIN '.$this->addDatabasePrefix('link_modifier_item').' AS l1 ON '.$this->addDatabasePrefix('items').'.item_id=l1.item_id AND l1.modifier_id="'.encode(AS_DB,$user_id).'"';
      $query .= ' WHERE 1';

      if (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      } else {
         $query .= ' AND '.$this->addDatabasePrefix('items').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
      }
      $query .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL';

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
      $query = 'SELECT '.$this->addDatabasePrefix('items').'.type';
      $query .= ' FROM '.$this->addDatabasePrefix('items');
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
     * @return \cs_item an item
     */
    public function getItem($iid, $vid = NULL)
    {
        if (!is_numeric($iid)) {
            return null;
        }

        if (isset($vid) && !is_numeric($vid)) {
            return null;
        }

        $retour = NULL;
        if (!isset($this->_cache_object[$iid])) {
            $query = 'SELECT *';
            $query .= ' FROM ' . $this->addDatabasePrefix('items');
            $query .= ' WHERE item_id="' . encode(AS_DB, $iid) . '"';
            $result = $this->_db_connector->performQuery($query);
            if (isset($result) and !empty($result)) {
                $retour = $this->_buildItem($result[0]);
            } elseif (!$this->_environment->isArchiveMode()
                and get_class($this) == 'cs_item_manager'
            ) {
                $zzz_item_manager = $this->_environment->getZzzItemManager();
                $retour = $zzz_item_manager->getItem($iid);
                if ($retour == 'empty') {
                    $retour = NULL;
                } else {
                    if ($retour->getItemID() == $this->_environment->getCurrentContextID()) {
                        $this->_environment->setFoundCurrentContextInArchive();
                        $this->_environment->activateArchiveMode();
                    }
                }
                unset($zzz_item_manager);
            } elseif ($this->_environment->isArchiveMode()
                and get_class($this) == 'cs_zzz_item_manager'
            ) {
                $item_manager = $this->_environment->getItemManager(true);
                $retour = $item_manager->getItem($iid);
                if ($retour == 'empty') {
                    $retour = NULL;
                }
                unset($item_manager);
            } else {
                $retour = 'empty';
            }

            if (!empty($retour)
                and $retour != 'empty'
                and is_object($retour)
            ) {

                // archive
                $db_prefix = $this->getDatabasePrefix();
                if ($this->withDatabasePrefix()
                    and !empty($db_prefix)
                    and Stristr($query, $db_prefix)
                ) {
                    $retour->setArchiveStatus();
                }
                // archive

                $this->_cache_object[$iid] = $retour;
            }
        } else {
            $retour = $this->_cache_object[$iid];
        }
        return $retour;
    }


   function getExternalViewerForItem($iid, $uid) {
      $retour = NULL;
      $query = 'SELECT user_id';
      $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
      $query .= ' WHERE item_id="'.$iid.'" AND user_id = "'.$uid.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) and !empty($result) ) {
         return true;
      } else {
   	 	return false;
      }
   }

   function getExternalViewerUserStringForItem($iid) {
      $retour = NULL;
      $query = 'SELECT user_id';
      $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
      $query .= ' WHERE item_id="'.$iid.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) and !empty($result) ) {
         foreach ( $result as $query_result ) {
            $retour .= $query_result['user_id'].' ';
         }
         return $retour;
      } else {
         return '';
      }
   }
   function getExternalViewerUserArrayForItem($iid) {
      $retour = array();
      $query = 'SELECT user_id';
      $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
      $query .= ' WHERE item_id="'.$iid.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) and !empty($result) ) {
         foreach ( $result as $query_result ) {
            $retour[] = $query_result['user_id'];
         }
         return $retour;
      } else {
         return '';
      }
   }

   function deleteExternalViewerEntry($iid,$user_id){
      $query = 'DELETE';
      $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
      $query .= ' WHERE item_id="'.$iid.'" and user_id = "'.$user_id.'"';
      $result = $this->_db_connector->performQuery($query);
   }

   function setExternalViewerEntry($iid,$user_id){
      $query = 'INSERT INTO '.$this->addDatabasePrefix('external_viewer').' SET '.
                 'item_id="'.encode(AS_DB,$iid).'",'.
                 'user_id="'.encode(AS_DB,$user_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');trigger_error('Problems creating external_view entry from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function getExternalViewerEntriesForRoom($room_id) {
   	$result_array = array();
   	$query_ids = 'SELECT item_id';
      $query_ids .= ' FROM '.$this->addDatabasePrefix('items');
      $query_ids .= ' WHERE context_id="'.$room_id.'"';
      $result_ids = $this->_db_connector->performQuery($query_ids);
      if ( isset($result_ids) and !empty($result_ids) ) {
      	$id_array = array();
         foreach ( $result_ids as $result_id ) {
            $id_array[] = $result_id['item_id'].' ';
         }
         $query = 'SELECT item_id';
         $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
         $query .= ' WHERE item_id IN ('.implode(',', $id_array).')';
         $result = $this->_db_connector->performQuery($query);
         if ( isset($result) and !empty($result) ) {
	         foreach ( $result as $query_result ) {
	            $result_array[] .= $query_result['item_id'];
	         }
         }
      }
      return $result_array;
   }

   function getExternalViewerEntriesForUser($user_id) {
      $result_array = array();
      $query = 'SELECT item_id';
      $query .= ' FROM '.$this->addDatabasePrefix('external_viewer');
      $query .= ' WHERE user_id="'.$user_id.'"';
      $result = $this->_db_connector->performQuery($query);
      if ( isset($result) and !empty($result) ) {
      	foreach($result as $query_result){
            $result_array[] .= $query_result['item_id'];
      	}
      }
      return $result_array;
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
     $this->_label_limit = array(0=>CS_TOPIC_TYPE);
  }

   public function deleteSpecialItems ($context_id, $type) {
      $current_user = $this->_environment->getCurrentUserItem();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET deleter_id='.encode(AS_DB,$current_user->getItemID()).', deletion_date=NOW() WHERE context_id='.encode(AS_DB,$context_id).' AND type="'.encode(AS_DB,$type).'"';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems deleting items from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function deleteReallyOlderThan ($days) {
      $retour = false;
      $timestamp = getCurrentDateTimeMinusDaysInMySQL($days);
      $query = 'DELETE FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE deletion_date IS NOT NULL and deletion_date < "'.$timestamp.'" AND type != "'.CS_DISCARTICLE_TYPE.'" AND type != "'.CS_USER_TYPE.'";'; // user und discarticle werden noch gebraucht
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

   ########################################################
   # statistic functions
   ########################################################

   function getCountItems ($start, $end) {
      $retour = 0;

      $query = "SELECT count(".$this->addDatabasePrefix($this->_db_table).".item_id) as number FROM ".$this->addDatabasePrefix($this->_db_table)." WHERE ".$this->addDatabasePrefix($this->_db_table).".context_id = '".encode(AS_DB,$this->_room_limit)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date > '".encode(AS_DB,$start)."' and ".$this->addDatabasePrefix($this->_db_table).".modification_date < '".encode(AS_DB,$end)."';";
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems counting items with query: '.$query,E_USER_WARNING);
      } else {
         foreach ($result as $rs) {
            $retour = $rs['number'];
         }
         unset($result);
      }

      return $retour;
   }

   ########################################################
   # archive functions
   ########################################################

    function moveFromDbToBackup($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $this->_db_table . ' SELECT * FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'DELETE FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $this->_db_table . ' SELECT * FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.context_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'DELETE FROM ' . $this->_db_table . ' WHERE ' . $this->_db_table . '.context_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);
        }
    }

    function moveFromBackupToDb($context_id)
    {
        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($context_id)) {
            $query = 'INSERT INTO ' . $this->_db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'DELETE FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.item_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'INSERT INTO ' . $this->_db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.context_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);

            $query = 'DELETE FROM ' . $c_db_backup_prefix . '_' . $this->_db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $this->_db_table . '.context_id = "' . $context_id . '"';
            $this->_db_connector->performQuery($query);
        }
    }

    public function moveFromDbToBackupWorkflow($context_id)
    {
        $db_table = 'workflow_read';

        $id_array_items = array();
        $item_manager = $this->_environment->getItemManager();
        $item_manager->setContextLimit($context_id);
        $item_manager->select();
        $item_list = $item_manager->get();
        $temp_item = $item_list->getFirst();
        while ($temp_item) {
            $id_array_items[] = $temp_item->getItemID();
            $temp_item = $item_list->getNext();
        }

        $id_array_users = array();
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($context_id);
        $user_manager->select();
        $user_list = $user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array_users[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array_items) and !empty($id_array_users)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $c_db_backup_prefix . '_' . $db_table . ' SELECT * FROM ' . $db_table . ' WHERE ' . $db_table . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . $db_table . '.user_id IN (' . implode(",", $id_array_users) . ')';
                $this->_db_connector->performQuery($query);

                $this->_deleteFromDbWorkflow($context_id);
            }
        }
    }

    function moveFromBackupToDbWorkflow($context_id)
    {
        $db_table = 'workflow_read';

        $id_array_items = array();
        $zzz_item_manager = $this->_environment->getZzzItemManager();
        $zzz_item_manager->setContextLimit($context_id);
        $zzz_item_manager->select();
        $item_list = $zzz_item_manager->get();
        $temp_item = $item_list->getFirst();
        while ($temp_item) {
            $id_array_items[] = $temp_item->getItemID();
            $temp_item = $item_list->getNext();
        }

        $id_array_users = array();
        $zzz_user_manager = $this->_environment->getZzzUserManager();
        $zzz_user_manager->setContextLimit($context_id);
        $zzz_user_manager->select();
        $user_list = $zzz_user_manager->get();
        $temp_user = $user_list->getFirst();
        while ($temp_user) {
            $id_array_users[] = $temp_user->getItemID();
            $temp_user = $user_list->getNext();
        }

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        if (!empty($id_array_items) and !empty($id_array_users)) {
            if (!empty($context_id)) {
                $query = 'INSERT INTO ' . $db_table . ' SELECT * FROM ' . $c_db_backup_prefix . '_' . $db_table . ' WHERE ' . $c_db_backup_prefix . '_' . $db_table . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . $c_db_backup_prefix . '_' . $db_table . '.user_id IN (' . implode(",", $id_array_users) . ')';
                $this->_db_connector->performQuery($query);

                $this->_deleteFromDbWorkflow($context_id, true);
            }
        }
    }

    // archive
    private function _deleteFromDbWorkflow($context_id, $from_backup = false)
    {
        $db_table = 'workflow_read';

        global $symfonyContainer;
        $c_db_backup_prefix = $symfonyContainer->getParameter('commsy.db.backup_prefix');

        $db_prefix = '';
        $id_array_items = array();
        $id_array_users = array();
        if (!$from_backup) {
            $item_manager = $this->_environment->getItemManager();
            $item_manager->setContextLimit($context_id);
            $item_manager->select();
            $item_list = $item_manager->get();
            $temp_item = $item_list->getFirst();
            while ($temp_item) {
                $id_array_items[] = $temp_item->getItemID();
                $temp_item = $item_list->getNext();
            }
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($context_id);
            $user_manager->select();
            $user_list = $user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array_users[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        } else {
            $db_prefix .= $c_db_backup_prefix . '_';
            $zzz_item_manager = $this->_environment->getZzzItemManager();
            $zzz_item_manager->setContextLimit($context_id);
            $zzz_item_manager->select();
            $item_list = $zzz_item_manager->get();
            $temp_item = $item_list->getFirst();
            while ($temp_item) {
                $id_array_items[] = $temp_item->getItemID();
                $temp_item = $item_list->getNext();
            }
            $zzz_user_manager = $this->_environment->getZzzUserManager();
            $zzz_user_manager->setContextLimit($context_id);
            $zzz_user_manager->select();
            $user_list = $zzz_user_manager->get();
            $temp_user = $user_list->getFirst();
            while ($temp_user) {
                $id_array_users[] = $temp_user->getItemID();
                $temp_user = $user_list->getNext();
            }
        }

        if (!empty($id_array_items) and !empty($id_array_users)) {
            $query = 'DELETE FROM ' . $db_prefix . $db_table . ' WHERE ' . $db_prefix . $db_table . '.item_id IN (' . implode(",", $id_array_items) . ') OR ' . $db_prefix . $db_table . '.user_id IN (' . implode(",", $id_array_users) . ')';
            $this->_db_connector->performQuery($query);
        }
    }
   
   ########################################################
   # archive functions - END
   ########################################################   
   
   function getItemsForNewsletter ($room_id_array, $user_id_array,$age_limit){
     $query1 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('items').'.context_id, '.$this->addDatabasePrefix('items').'.type FROM '.$this->addDatabasePrefix('items');
     $query1 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(", ",encode(AS_DB,$room_id_array)).')';
     $query1 .= ' AND modification_date >= DATE_SUB(CURRENT_DATE,interval '.encode(AS_DB,$age_limit).' day) AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
     $query1 .= ' AND '.$this->addDatabasePrefix('items').'.type !="task" AND '.$this->addDatabasePrefix('items').'.type !="link_item"';
     $query1 .= ' ORDER BY context_id, type';
     // perform query
     $result1 = array();
     $result1 = $this->_db_connector->performQuery($query1);
     if (!isset($result1)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     }
     $query2 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id,'.$this->addDatabasePrefix('noticed').'.read_date,'.$this->addDatabasePrefix('noticed').'.user_id FROM '.$this->addDatabasePrefix('items');
     $query2 .= ' INNER JOIN '.$this->addDatabasePrefix('noticed').' ON '.$this->addDatabasePrefix('noticed').'.item_id = '.$this->addDatabasePrefix('items').'.item_id';
     $query2 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(", ",encode(AS_DB,$room_id_array)).')';
     $query2 .= ' AND '.$this->addDatabasePrefix('noticed').'.user_id IN ('.implode(", ",encode(AS_DB,$user_id_array)).')';
     $query2 .= ' AND '.$this->addDatabasePrefix('items').'.modification_date <= '.$this->addDatabasePrefix('noticed').'.read_date';
     $query2 .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
     // perform query
     $r2 = array();
     $r2 = $this->_db_connector->performQuery($query2);
     if (!isset($r2)) {
         include_once('functions/error_functions.php');trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     }
     $result2 = array();
     $read_date_array = array();
     foreach($r2 as $r){
     	$result2[] = $r['item_id'];
     }

     $query3 = 'SELECT '.$this->addDatabasePrefix('items').'.item_id, '.$this->addDatabasePrefix('noticed').'.read_date,'.$this->addDatabasePrefix('noticed').'.user_id FROM '.$this->addDatabasePrefix('items');
     $query3 .= ' INNER JOIN '.$this->addDatabasePrefix('noticed').' ON '.$this->addDatabasePrefix('noticed').'.item_id = '.$this->addDatabasePrefix('items').'.item_id';
     $query3 .= ' WHERE '.$this->addDatabasePrefix('items').'.context_id IN ('.implode(", ",encode(AS_DB,$room_id_array)).')';
     $query3 .= ' AND '.$this->addDatabasePrefix('noticed').'.user_id IN ('.implode(", ",encode(AS_DB,$user_id_array)).')';
     #$query3 .= ' AND '.$this->addDatabasePrefix('items').'.modification_date <= '.$this->addDatabasePrefix('noticed').'.read_date';
     $query3 .= ' AND '.$this->addDatabasePrefix('items').'.deleter_id IS NULL and '.$this->addDatabasePrefix('items').'.deletion_date IS NULL';
	  #pr($read_date_array);
     #pr($query2);

     $r3 = $this->_db_connector->performQuery($query3);
     foreach($r3 as $r){
     	$read_date_array[$r['user_id']][$r['item_id']] = $r['read_date'];
     }
     #pr($read_date_array);

     $tmp_result = array();
     $annotation_manager = $this->_environment->getAnnotationManager();
     $discarticle_manager = $this->_environment->getDiscussionArticleManager();
     $section_manager = $this->_environment->getSectionManager();
     $step_manager = $this->_environment->getStepManager();
     ######################################################
     $label_manager = $this->_environment->getLabelManager();
     ######################################################
     $result = array();
     foreach($result1 as $r){
     	if (!in_array($r['item_id'],$result2)){
     	   if (isset($r['type']) and $r['type'] == 'annotation'){
     	      $anno_item = $annotation_manager->getItem($r['item_id']);
     	      $linked_item = $anno_item->getLinkedItem();
     	      $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'annotation';

     	      if(empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
     	      }

     	      unset($anno_item);
     	      unset($linked_item);
     	   }elseif (isset($r['type']) and $r['type'] == 'discarticle'){
     	      $discarticle_item = $discarticle_manager->getItem($r['item_id']);
     	      $linked_item = $discarticle_item->getLinkedItem();
     	      $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'discarticle';

     	      if(empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
     	      }

      	   unset($discarticle_item);
     	      unset($linked_item);
     	   }elseif (isset($r['type']) and $r['type'] == 'section'){
     	      $section_item = $section_manager->getItem($r['item_id']);
     	      $linked_item = $section_item->getLinkedItem();
     	      $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'section';

     	      if(empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
     	      }

     	      unset($section_item);
     	      unset($linked_item);
     	   }elseif (isset($r['type']) and $r['type'] == 'step'){
     	      $step_item = $step_manager->getItem($r['item_id']);
     	      $linked_item = $step_item->getLinkedItem();
     	      $result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()][] = 'step';

     	      if(empty($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$linked_item->getItemID()])){
     	      	$result[$linked_item->getContextID()][$linked_item->getItemType()][$linked_item->getItemID()]['noticed'] = 'changed';
     	      }

     	      unset($step_item);
     	      unset($linked_item);
     	   }elseif (isset($r['type']) and $r['type'] == 'label'){
     	   	$label_item = $label_manager->getItem($r['item_id']);
     	   	$result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()][] = $label_item->getLabelType();

     	      if(empty($read_date_array[$user_id_array[0]][$label_item->getItemID()])){
     	      	$result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$label_item->getItemID()])){
     	      	$result[$label_item->getContextID()][$label_item->getItemType()][$label_item->getItemID()]['noticed'] = 'changed';
     	      }

     	      unset($label_item);
     	   }else{
     	      $result[$r['context_id']][$r['type']][$r['item_id']][] = 'entry';
     	      $linked_item = $this->getItem($r['item_id']);

     	      if(empty($read_date_array[$user_id_array[0]][$r['item_id']])){
     	      	$result[$r['context_id']][$r['type']][$r['item_id']]['noticed'] = 'new';
     	      } elseif(isset($read_date_array[$user_id_array[0]][$r['item_id']])){
     	      	$result[$r['context_id']][$r['type']][$r['item_id']]['noticed'] = 'changed';
     	      }

     	      unset($linked_item);
     	   }
     	}
     }
     #########################
     unset($label_manager);
     #########################
     unset($step_manager);
     unset($section_manager);
     unset($discarticle_manager);
     unset($annotation_manager);
     #pr($result);
     return $result;
  }

  function isItemMarkedAsWorkflowRead($item_id, $user_id){
     $query = 'SELECT * FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.' and user_id = '.$user_id.';';
     $result = $this->_db_connector->performQuery($query);
     if(empty($result)){
        return false;
     } else {
        return true;
     }
  }
  
  function getUsersMarkedAsWorkflowReadForItem($item_id){
     $result = array();
     $query = 'SELECT * FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.';';
     $result = $this->_db_connector->performQuery($query);
     return $result;
  }
  
  function markItemAsWorkflowRead($item_id, $user_id){
     if(!$this->isItemMarkedAsWorkflowRead($item_id, $user_id)){
        $query = 'INSERT INTO '.$this->addDatabasePrefix('workflow_read').' (item_id, user_id) VALUES ('.$item_id.', '.$user_id.');';
        $result = $this->_db_connector->performQuery($query);
     }
  }
  
  function markItemAsWorkflowNotRead($item_id, $user_id){
     if($this->isItemMarkedAsWorkflowRead($item_id, $user_id)){
        $query = 'DELETE FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.' AND user_id = '.$user_id.';';
        $result = $this->_db_connector->performQuery($query);
     }
  }
  
  function markItemAsWorkflowNotReadForAllUsers($item_id){
     $query = 'DELETE FROM '.$this->addDatabasePrefix('workflow_read').' WHERE item_id = '.$item_id.';';
     $result = $this->_db_connector->performQuery($query);
  }

    public function getAllDraftItems()
    {
        $result = array();
        $query = 'SELECT * FROM '.$this->addDatabasePrefix('items').' WHERE draft = 1 AND deletion_date IS NULL AND deleter_id IS NULL;';
        $result = $this->_db_connector->performQuery($query);

        return $result;
    }
}
?>