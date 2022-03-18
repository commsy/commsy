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

/** class for database connection to the database table "links"
 * this class implements a database manager for the table "links". Links between commsy items
 */
class cs_links_manager extends cs_manager {

  /**
   * integer - containing the error number if an error occured
   */
  var $_dberrno;

  /**
   * string - containing the error text if an error occured
   */
  var $_dberror;

  /**
   * integer - containing the item id, if an item was created
   */
  var $_create_id;
  /**
   * array - containing the data from the database -> cache data
   */
  var $_data = array();

  /**
   * array - containing key = link-type / values = boolean: true if available in context, false if not
   */
  var $_available = array();

  /**
   * string - containing the order limit for the select statement
   */
  var $_order;

  /**
   * boolean - true all links are selected, false only the links that are not deleted
   */
  var $_with_deleted_links = false;

  var $_item_id_limit = NULL;

  var $_version_id_limit = NULL;

  var $_all_link_file_data = array();

  var $_file_to_material_data = array();

  var $_item_id_array = array();

  private $_limit_item_type = NULL;
  private $_limit_link_type = NULL;

  /** constructor: cs_links_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'links';
  }

  /** reset limits
    * reset limits of this class
    */
  function resetLimits () {
     $this->_item_id_limit = NULL;
     $this->_version_id_limit = NULL;
     $this->_order = NULL;
     $this->_with_deleted_links = false;
     $this->_limit_item_type = NULL;
     $this->_limit_link_type = NULL;
  }

  /** set context limit
    * this method sets a project context limit
    *
    * @param integer limit id of the project context
    */
  function setContextLimit ($limit) {
     $this->_room_limit = (int)$limit;
     $this->_data = array();
  }

  public function setLinkTypeLimit ($value) {
     $this->_limit_link_type = $value;
  }

  public function setItemTypeLimit ($value) {
     $this->_limit_item_type = $value;
  }

  function resetData () {
     $this->_data = array();
  }

  function resetOrder () {
     unset($this->_order);
  }

  /** set flag to get deleted links
    * this method sets a flag to get deleted links
    */
  function withDeletedLinks () {
     $this->_with_deleted_links = true;
  }

    /** set order to from item id
    * set order to from item id
    */
   function setOrderToFromItemID () {
      $this->_order = 'from_item_id';
   }

   /** set order to to item id
    * set order to to item id
    */
   function setOrderToToItemID () {
      $this->_order = 'to_item_id';
   }

   function setOrderToSection () {
      $this->_order = 'section';
   }

   /** set id of "to"-item
    * this method sets the id of "to"-item
    *
    * @param integer value id of "to"-item
    */
   function setItemIDLimit ($value) {
      $this->_item_id_limit = (integer)$value;
   }

   /** set version id of "to"-item
    * this method sets the version id of "to"-item
    *
    * @param integer value version id of "to"-item
    */
   function setVersionIDLimit ($value) {
      $this->_version_id_limit = (integer)$value;
   }

   /** build a new links item
    * this method returns a new EMTPY user item
    *
    * @return object cs_item a new EMPTY user
    */
   function getNewItem () {
#      include_once('classes/cs_links_item.php');
#      return new cs_links_item($this->_environment);
   }

  /** get all links and save it - INTERNAL
    * this method get all links and cache it this class
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
  function _performQuery ($type = '', $mode = 'select', $item_id = '') {
     $data = array();
     if ($mode == 'count') {
        $query = 'SELECT count('.$this->addDatabasePrefix('links').'.item_id)';
     } elseif ($mode == 'id_array') {
         $query = 'SELECT '.$this->addDatabasePrefix('links').'.item_id';
     } else {
        $query = 'SELECT '.$this->addDatabasePrefix('links').'.*';
        if ($mode == 'select_with_item_type_from') {
           $query .= ','.$this->addDatabasePrefix('items').'.type';
        }
     }
     $query .= ' FROM '.$this->addDatabasePrefix('links');
     if (isset($this->_order) and $this->_order == 'section') {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('section').' ON '.$this->addDatabasePrefix('section').'.item_id = '.$this->addDatabasePrefix('links').'.from_item_id AND '.$this->addDatabasePrefix('section').'.version_id = '.$this->addDatabasePrefix('links').'.from_version_id';
     }
     if ( $mode == 'select_with_item_type_from' ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id='.$this->addDatabasePrefix('links').'.to_item_id';
     }
     if ( !empty($this->_limit_item_type) ) {
        $query .= ' LEFT JOIN '.$this->addDatabasePrefix('items').' ON '.$this->addDatabasePrefix('items').'.item_id='.$this->addDatabasePrefix('links').'.from_item_id';
     }
     if (!empty($type)) {
        $query .= ' WHERE '.$this->addDatabasePrefix('links').'.link_type LIKE "'.encode(AS_DB,$type).'"';
     } else {
        $query .= ' WHERE 1';
     }

     if ( !empty($this->_limit_link_type) and empty($type) ) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.link_type="'.$this->_limit_link_type.'"';
     }

     if ( !empty($this->_limit_item_type) ) {
        $query .= ' AND '.$this->addDatabasePrefix('items').'.type="'.$this->_limit_item_type.'"';
     }

      // fifth, insert limits into the select statement
     if (isset($this->_item_id_limit)) {
        $query .= ' AND ('.$this->addDatabasePrefix('links').'.from_item_id = "'.encode(AS_DB,$this->_item_id_limit).'"';
        $query .= ' OR '.$this->addDatabasePrefix('links').'.to_item_id = "'.encode(AS_DB,$this->_item_id_limit).'" )';
     }
     if (isset($this->_version_id_limit)) {
        $query .= ' AND ('.$this->addDatabasePrefix('links').'.from_version_id = "'.encode(AS_DB,$this->_version_id_limit).'"';
        $query .= ' OR '.$this->addDatabasePrefix('links').'.to_version_id = "'.encode(AS_DB,$this->_version_id_limit).'") ';
     }
     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     if (!$this->_with_deleted_links) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.deleter_id IS NULL';
     }
     if (isset($this->_order)) {
        if ($this->_order == 'section') {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('section').'.number';
        } else {
           $query .= ' ORDER BY '.$this->addDatabasePrefix('links').'.'.encode(AS_DB,$this->_order);
        }
     }

     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting links from query: "'.$query.'"',E_USER_WARNING);
     } else {
        if (!empty($type)) {
           foreach ($result as $query_result) {
              $data[] = $query_result;
           }
        } else {
           return $result;
        }
     }
     $this->_data[$type] = $data;
  }

  function combineLabels($label1, $label2) {
     $this->_combine('label_for',$label1,$label2);
  }

  function combineBuzzwords($buzz1, $buzz2) {
     $this->_combine('buzzword_for',$buzz1,$buzz2);
  }

  function _combine($type, $buzz1, $buzz2) {
     $result = $this->getLinksTo2($type,$buzz1);
     $from_id_array = array();
     if ( !empty($result) ) {
        foreach ( $result as $value ) {
           $from_id_array[] = $value['from_item_id'];
        }
        $from_id_array = array_unique($from_id_array);
     }

     $query = 'UPDATE '.$this->addDatabasePrefix('links').' SET '.
              'to_item_id="'.encode(AS_DB,$buzz1).'"'.
              ' WHERE '.$this->addDatabasePrefix('links').'.to_item_id="'.encode(AS_DB,$buzz2).'"';
     if ( !empty($from_id_array) ) {
        $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.from_item_id NOT IN ('.implode(',',encode(AS_DB,$from_id_array)).')';
     }
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems combining from query: "'.$query.'"',E_USER_WARNING);
     }

     $this->deleteLinksTo($buzz2);
  }

  /** get links to and from a commsy item
    * this method returns a list of links to and from a commsy item
    *
    * @param string  type       type of the link
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    *
    * @return object cs_list list of links
    */
  function getLinks ($type, $item, $version_id = NULL, $version_filter = 'eq') {
     $data_array = array();
     $data_array = $this->getLinksFrom($type, $item, $version_id, 'select', $version_filter);
     $to_data_array = $this->getLinksTo($type, $item, $version_id, $version_filter);
     if (!empty($to_data_array)){
        foreach($to_data_array as $data)
        $data_array[] = $data;
     }
     return $data_array;
  }

  /** get links from a commsy item
    * this method returns a list of links from a commsy item
    *
    * @param string  type       type of the link
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    *
    * @return object cs_list list of links
    */
  function getLinksFrom ($type, $item, $version_id = NULL, $mode = 'select', $version_filter = 'eq') {
     if ($this->_isAvailable($type)) {
        if (!isset($this->_data[$type]) || $item->getContextID() != $this->_environment->getCurrentContextID()) { // this checks wether we have the right stuff in cache
           if ($item->getContextID() != $this->_environment->getCurrentContextID()) {
              $this->setContextLimit($item->getContextID());
           }
           $this->_performQuery($type, $mode);
        }
        return $this->_getCachedLinks($type, $item->getItemID(), 'from', $version_id, $version_filter);
     } else {
        return array();
     }
  }

  /** get links from a commsy item with item type of the linked items
    * this method returns a list of links from a commsy item with item type of the linked items
    *
    * @param string  type       type of the link
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    *
    * @return array list of links
    */
  function getLinksFromWithItemType ($type, $item, $version_id = NULL) {
     return $this->getLinksFrom($type, $item, $version_id, 'select_with_item_type_from');
  }

  /** Checks if from_item is linked to to_item
    * this method returns true or false
    *

    * @param integer $from_item_id    id of the from_item
    * @param integer $to_item_id      id of the to_item
    * @param integer $from_version_id version id of the from_item
    * @param integer $to_version_id version id of the to_item
    * @param integer $link_type type of the link
    *
    * @return true or false
    */
  function isLinkedTo ($from_item_id, $to_item_id, $from_version_id=NULL, $to_version_id=NULL, $link_type=NULL) {

     $query  = 'SELECT * FROM '.$this->addDatabasePrefix('links');
     $query .= ' WHERE '.$this->addDatabasePrefix('links').'.from_item_id="'.encode(AS_DB,$from_item_id).'"';
     $query .= ' AND '.$this->addDatabasePrefix('links').'.to_item_id = "'.encode(AS_DB,$to_item_id).'"';
     $query .= isset($from_version_id) ? ' AND '.$this->addDatabasePrefix('links').'.from_version_id="'.encode(AS_DB,$from_version_id).'"' : '';
     $query .= isset($to_version_id) ? ' AND '.$this->addDatabasePrefix('links').'.to_version_id="'.encode(AS_DB,$to_version_id).'"' : '';
     $query .= isset($link_type) ? ' AND '.$this->addDatabasePrefix('links').'.link_type = "'.encode(AS_DB,$link_type).'"' : '';
     if (!$this->_with_deleted_links) {
        $query .= ' AND '.$this->addDatabasePrefix('links').'.deleter_id IS NULL';
     }
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result)) {
        include_once('functions/error_functions.php');trigger_error('Problems checking one link from query: "'.$query.'"',E_USER_WARNING);
     } else {
        return (count($result) > 0 ? TRUE : FALSE);
     }
  }

  /** Checks whether to items are linked
    * this method returns true or false
    *

    * @param integer $item_id_one    id of item 1
    * @param integer $item_id_two    id of item 2
    * @param integer $version_id_one version id of item 1
    * @param integer $version_id_two version id of item 2
    * @param integer $link_type type of the link
    *
    * @return true or false
    */
  function isLinked ($item_id_one, $item_id_two, $version_id_one=NULL, $version_id_two=NULL, $link_type=NULL) {
     if ($this->isLinkedTo($item_id_one, $item_id_two, $version_id_one, $version_id_two, $link_type)) {
          return TRUE;
     } else {
          return $this->isLinkedTo($item_id_two, $item_id_one, $version_id_two, $version_id_one, $link_type);
     }
  }

  /** get links to a commsy item
    * this method returns a list of links to a commsy item
    *
    * @param string  type       type of the link
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    *
    * @return object cs_list list of links
    */
  function getLinksTo($type, $item, $version_id = NULL, $version_filter = 'eq') {
     if ($this->_isAvailable($type)) {
        if (!isset($this->_data[$type]) or $item->getContextID() != $this->_environment->getCurrentContextID()) {
           if ($item->getContextID() != $this->_environment->getCurrentContextID()) {
              $this->setContextLimit($item->getContextID());
           }
           $this->_performQuery($type);
        }
        return $this->_getCachedLinks($type, $item->getItemID(), 'to', $version_id, $version_filter);
     } else {
        return array();
     }
  }

  function getLinksTo2 ($type, $item_id, $version_id = NULL, $version_filter = 'eq') {
     if ($this->_isAvailable($type)) {
        if ( !isset($this->_data[$type]) ) {
           $this->_performQuery($type);
        }
        return $this->_getCachedLinks($type, $item_id, 'to', $version_id, $version_filter);
     } else {
        return array();
     }
  }

   function _getCachedLinks($type, $item_id, $field, $version_id = NULL, $filter = 'eq') {
      $data_array = array();
      reset($this->_data[$type]);
      $line = current($this->_data[$type]);
      $item_field = $field."_item_id";
      $version_field = $field."_version_id";
      while ($line) {
        if ($line[$item_field] == $item_id) {
           if ( !isset($version_id)
                or ($filter == 'eq' and $line[$version_field] == $version_id)
                or ($filter == 'gt' and $line[$version_field] > $version_id)
                or ($filter == 'ge' and $line[$version_field] >= $version_id)
                or ($filter == 'lt' and $line[$version_field] < $version_id)
                or ($filter == 'le' and $line[$version_field] <= $version_id)
              ) {
              $data_array[] = $line;
           }
        }
        $line = next($this->_data[$type]);
      }
      return $data_array;
   }

  /** create a link - internal, do not use -> use method save
    * this method creates a link
    *
    * @param array
    */
  function _create ($db_data) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('links').' SET '.
              'from_item_id="'.encode(AS_DB,$db_data['from_item_id']).'",'.
              'from_version_id="'.encode(AS_DB,$db_data['from_version_id']).'",'.
              'to_item_id="'.encode(AS_DB,$db_data['to_item_id']).'",'.
              'to_version_id="'.encode(AS_DB,$db_data['to_version_id']).'",'.
              'link_type="'.encode(AS_DB,$db_data['link_type']).'",';
     $query .= 'context_id="'.encode(AS_DB,$db_data['room_id']).'"';
     if ( !empty($db_data['x']) ) {
        $query .= ',x="'.encode(AS_DB,$db_data['x']).'"';
     }
     if ( !empty($db_data['y']) ) {
        $query .= ',y="'.encode(AS_DB,$db_data['y']).'"';
     }
     $query .= ';';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating link item from query: "'.$query.'"',E_USER_WARNING);
     }
  }

   public function savePos ($db_data) {
      if ( !empty($db_data)
           and !empty($db_data['x'])
           and !empty($db_data['y'])
           and !empty($db_data['from_item_id'])
           and !empty($db_data['to_item_id'])
           and !empty($db_data['link_type'])
         ) {
         $sql =  'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET x="'.$db_data['x'].'",y="'.$db_data['y'].'"';
         $sql .= ' WHERE from_item_id="'.$db_data['from_item_id'].'" AND to_item_id="'.$db_data['to_item_id'].'" AND link_type="'.$db_data['link_type'].'"';
         if ( !empty($db_data['from_version_id']) ) {
            $sql .= ' AND from_version_id="'.$db_data['from_version_id'].'"';
         }
         if ( !empty($db_data['to_version_id']) ) {
            $sql .= ' AND to_version_id="'.$db_data['to_version_id'].'"';
         }
         $sql .= ';';
         $result = $this->_db_connector->performQuery($sql);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems saving x and y from query: "'.$sql.'"',E_USER_WARNING);
         }
      }
   }

   /** save a link
    * save a link into the database table "links"
    *
    * @param array
    */
   function save ($link_array) {
      $this->_create($link_array);
      if (isset($this->_data[$link_array['link_type']])) {
         $this->_data[$link_array['link_type']][] = $link_array;
      }
   }

  /** Do not use!
    * This is only here to prevent accidental use of cs_manager->saveItem
    * by inheritance.
    *
    * @param cs_item
    */
  function saveItem ($item) {
    include_once('functions/error_functions.php');
    trigger_error("cs_links_manager->saveItem(): Do not use this Function! Use save() instead!", E_USER_ERROR);
  }

  /** delete links
    * this method deletes a links to and from a commsy item
    *
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    * @param string  link_type  type of the link
    */
  function deleteLinks ($item_id, $version_id, $link_type) {
     $this->deleteLinksFrom($item_id,$version_id,$link_type);
     $this->deleteLinksTo($item_id,$version_id,$link_type);
  }

  /** delete links from an item
    * this method deletes a links from a commsy item
    *
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    * @param string  link_type  type of the link
    */
  function deleteLinksFrom ($from_item_id, $from_version_id=NULL, $link_type=NULL) {
     if ($this->_isAvailable($link_type)) {
        $query = 'DELETE FROM '.$this->addDatabasePrefix('links').' WHERE '.
                 'from_item_id="'.encode(AS_DB,$from_item_id).'"';
        if (!empty($from_version_id)) {
           $query .= ' AND from_version_id="'.encode(AS_DB,$from_version_id).'"';
        } elseif ($from_version_id == 0 and $from_version_id != '') {
           $query .= ' AND from_version_id="0"';
        }
        if (!empty($link_type)) {
           $query .= ' AND link_type="'.encode(AS_DB,$link_type).'"';
        }
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) or !$result ) {
           include_once('functions/error_functions.php');trigger_error('Problems deleting link item from query: "'.$query.'"',E_USER_WARNING);
        }
     }
  }

  /** delete links to an item
    * this method deletes a links to a commsy item
    *
    * @param integer item_id    id of the item
    * @param integer version_id id of the version
    * @param string  link_type  type of the link
    */
  function deleteLinksTo ($to_item_id, $to_version_id=NULL, $link_type=NULL) {
     if ($this->_isAvailable($link_type)) {
        $query = 'DELETE FROM '.$this->addDatabasePrefix('links').' WHERE '.
                 'to_item_id="'.$to_item_id.'"';
        if (!empty($to_version_id)) {
           $query .= ' AND to_version_id="'.encode(AS_DB,$to_version_id).'"';
        } elseif ($to_version_id == 0 and $to_version_id != '') {
           $query .= ' AND to_version_id="0"';
        }
        if(!empty($link_type)) {
           $query .= ' AND link_type="'.encode(AS_DB,$link_type).'"';
        }
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) or !$result ) {
           include_once('functions/error_functions.php');trigger_error('Problems deleting link item from query: "'.$query.'"',E_USER_WARNING);
        }
     }
  }

  /** delete link between two items
    * this method deletes a link between two commsy items
    *
    * @param integer from_item_id       id of the from_item
    * @param integer to_item_id         id of the to_item
    * @param integer from_version_id    version id of the from_item
    * @param integer to_version_id    version id of the to_item
    * @param string  link_type        type of the link
    */
  function deleteLink ($from_item_id, $to_item_id, $from_version_id=NULL, $to_version_id=NULL, $link_type=NULL) {
     if ($this->_isAvailable($link_type)) {
        $query = 'DELETE FROM '.$this->addDatabasePrefix('links').' WHERE '.
                 'from_item_id="'.encode(AS_DB,$from_item_id).'" and '.
                 'to_item_id="'.encode(AS_DB,$to_item_id).'"';
        $query .= !empty($from_version_id) ? ' and from_version_id="'.encode(AS_DB,$from_version_id).'"' : '';
        $query .= !empty($to_version_id) ? ' and to_version_id="'.encode(AS_DB,$to_version_id).'"'  : '';
        $query .= !empty($link_type) ? ' and link_type="'.encode(AS_DB,$link_type).'"'  : '';

        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) or !$result ) {
           include_once('functions/error_functions.php');trigger_error('Problems deleting one link from query: "'.$query.'"',E_USER_WARNING);
        }
     }
  }

  /** delete link between two items
    * this method deletes a link between two commsy items
    *
    * @param integer from_item_array  array of the from_item
    * @param integer to_item_id       id of the to_item
    */
  function deleteFromLinks ($from_item_array, $to_item_id) {
     if ( !empty($from_item_array) and !empty($to_item_id) ) {
        $query = 'DELETE FROM '.$this->addDatabasePrefix('links').' WHERE '.
                 'from_item_id IN ('.encode(AS_DB,implode(',',$from_item_array)).') AND '.
                 'to_item_id="'.encode(AS_DB,$to_item_id).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) or !$result ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems deleting from links from query: "'.$query.'"',E_USER_WARNING);
        }
     }
  }

    /** delete link , but it is just an update
     * this method deletes all links from an item, but only as an update to restore it later and for evaluation
     *
     * @param integer item_id       id of the item
     * @param integer version_id    version id of the item
     */
    public function deleteLinksBecauseItemIsDeleted($item_id, $version_id = null)
    {
        $user_id = $this->_current_user->getItemID() ?: 0;
        $query = 'UPDATE ' . $this->addDatabasePrefix('links') . ' SET ' .
            'deletion_date="' . getCurrentDateTimeInMySQL() . '",' .
            'deleter_id="' . encode(AS_DB, $user_id) . '"' .
            ' WHERE (from_item_id="' . encode(AS_DB, $item_id) . '"';
        if ($version_id) {
            $query .= ' AND from_version_id="' . encode(AS_DB, $version_id) . '"';
        }
        $query .= ') OR (to_item_id="' . encode(AS_DB, $item_id) . '"';
        if ($version_id) {
            $query .= ' AND to_version_id="' . encode(AS_DB, $version_id) . '"';
        }
        $query .= ')';
        $result = $this->_db_connector->performQuery($query);
        if (!isset($result) or !$result) {
            include_once('functions/error_functions.php');
            trigger_error('Problems deleting (updating) links of an item from query: "' . $query . '"', E_USER_WARNING);
        }
    }

   /*
   checks if link type is supported in the current context
   so far only groups and materials are checked within contexts, since they can be "switched off"
   @param string link type
   @return boolean is supported
   */
   function _isAvailable($type) {
      // check if groups and/or materials are available in the context
      if(($type == 'relevant_for' || $type == 'material_for' || $type == 'member_of')  and $this->_environment->inProjectRoom()) {
         if(!isset($this->_available[$type])) {
            $context_item = $this->_environment->getCurrentContextItem();
            if($type == 'relevant_for' || $type == 'member_of') {
               $this->_available[$type] = $context_item->withRubric(CS_GROUP_TYPE);
            } else if($type == 'material_for') {
               $this->_available[$type] = $context_item->withRubric(CS_MATERIAL_TYPE);
            }
         }
         return $this->_available[$type];
      } else {
         return true;
      }
   }

########### file links ###########

   function linkFile ($from_item, $file_item) {
      $file_id = $file_item->getFileID();
      $this->linkFileByID($from_item, $file_id);
   }

   function linkFileByID ($from_item, $file_id) {
      if ( $this->_existFileLink($from_item, $file_id) ) {
         $query = "UPDATE ".$this->addDatabasePrefix("item_link_file")." SET ";
         $query .= "deleter_id=NULL, ";
         $query .= "deletion_date=NULL ";
         $query .= "WHERE ";
         $query .= "item_iid='".encode(AS_DB,$from_item->getItemID())."' AND ";
         $query .= "item_vid='".encode(AS_DB,$from_item->getVersionID())."' AND ";
         $query .= "file_id='".encode(AS_DB,$file_id)."'";
      } else {
         $query = "INSERT INTO ".$this->addDatabasePrefix("item_link_file")." SET ";
         $query .= "item_iid='".encode(AS_DB,$from_item->getItemID())."', ";
         $query .= "item_vid='".encode(AS_DB,$from_item->getVersionID())."', ";
         $query .= "file_id='".encode(AS_DB,$file_id)."'";
      }
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');trigger_error("Problem creating File-Link query: ".$query, E_USER_WARNING);
      }
   }

   function getMaterialIDForFileID($file_id) {
      if(isset($this->_file_to_material_data[$file_id])) {
        return $this->_file_to_material_data[$file_id];
      } else {
        $query = '
          SELECT
            item_iid
          FROM
            ' . $this->addDatabasePrefix("item_link_file") . '
          WHERE
            file_id="' . encode(AS_DB, $file_id) . '" AND
            deletion_date IS NULL
        ';
        $result = $this->_db_connector->performQuery($query);
        if(!isset($result)) {
          include_once('functions/error_functions.php');
          trigger_error("Problems loading file links: ".$query, E_USER_WARNING);
        } else {
          $this->_file_to_material_data[$file_id] = $result[0]['item_iid'];
          return $result[0]['item_iid'];
        }
      }
   }

   function getFileLinks ($from_item) {
      $data = array();
      $id = $from_item->getItemID();
      $version_id = $from_item->getVersionID();
      if ( empty($version_id) ) {
         $version_id = '0';
      }
      if (!empty($id)) {
         if ( in_array($id.'_'.$version_id, $this->_item_id_array) ) {
            if ( array_key_exists($id.'_'.$version_id,$this->_all_link_file_data) ) {
               $temp_data_array = $this->_all_link_file_data[$id.'_'.$version_id];
               foreach ($temp_data_array as $temp_data) {
                  if ($temp_data['item_vid'] == $version_id) {
                     $data[] = $temp_data;
                  }
               }
            }
         } else {
            $query = "SELECT * FROM ".$this->addDatabasePrefix("item_link_file");
            $query .= " WHERE item_iid=".encode(AS_DB,$from_item->getItemID());
            $query .= " AND item_vid=".encode(AS_DB,$version_id);
            $query .= " AND deletion_date IS NULL";
            $result = $this->_db_connector->performQuery($query);
            if( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error("Problems loading file links: ".$query, E_USER_WARNING);
            } else {
               $id = $from_item->getItemID();
               if ( !in_array($id.'_'.$version_id, $this->_item_id_array) ){
                  $this->_item_id_array[] = $id.'_'.$version_id;
               }
               foreach ($result as $query_result) {
                  $data[] = $query_result;
               }
               $this->_all_link_file_data[$id.'_'.$version_id] = $data;
            }
         }
      }
      return $data;
   }

   function getAllFileLinksForListByIDs($id_array, $v_id_array = NULL){
      // ------------------
      // --->UTF8 - OK<----
      // ------------------
      $data = array();
      $file_id_array = array();
      if (count($id_array)>0){
         foreach($id_array as $id){
            if (!in_array($id,$this->_item_id_array)){
               if( !isset($v_id_array) ){
                  $this->_item_id_array[] = $id.'_0';
               }else{
                  if (isset($v_id_array[$id])){
                     $this->_item_id_array[] = $id.'_'.$v_id_array[$id];
                  }else{
                     $this->_item_id_array[] = $id.'_0';
                  }
               }
            }
         }
         $query  = 'SELECT item_iid, MAX(item_vid) as item_vid, file_id, deleter_id, deletion_date FROM '.$this->addDatabasePrefix('item_link_file').
                ' WHERE item_iid IN ('.implode(",",encode(AS_DB,$id_array)).')'.
                ' AND deleter_id IS NULL'.
                ' AND deletion_date IS NULL'.
                ' GROUP BY file_id';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
           include_once('functions/error_functions.php');trigger_error('Problems selecting noticed from query: "'.$query.'"');
         } else {
            foreach ($result as $query_result) {
               $this->_all_link_file_data[$query_result['item_iid'].'_'.$query_result['item_vid']][] = $query_result;
               $file_id_array[] = $query_result['file_id'];
               if (!in_array($query_result['item_iid'].'_'.$query_result['item_vid'],$this->_item_id_array)){
                  $this->_item_id_array[] = $query_result['item_iid'].'_'.$query_result['item_vid'];
               }
            }
         }
      }
      return $file_id_array;
   }

   function deleteFileLinks($from_item) {
      $this->deleteFileLinkByID($from_item);
   }

   function deleteFileLink($from_item, $file_item) {
      $file_id = $file_item->getFileID();
      $this->deleteFileLinkByID($from_item, $file_id);
   }

   function deleteFileLinkByID($from_item, $file_id=NULL) {
      $deleter = $this->_environment->getCurrentUser();
      $query = "UPDATE ".$this->addDatabasePrefix("item_link_file")." SET deletion_date='".getCurrentDateTimeInMySQL()."', deleter_id=".encode(AS_DB,$deleter->getItemID());
      $query .= " WHERE item_iid=".encode(AS_DB,$from_item->getItemID());
      $query .= " AND item_vid=".encode(AS_DB,$from_item->getVersionID());
      if ($file_id) {   // this test is needed when invoked by deleteFileLinks()
         $query .= " AND file_id=".$file_id;
      }
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result) or !$result) {
         include_once('functions/error_functions.php');trigger_error("Problem deleting File-Link: ".$query, E_USER_WARNING);
      }
   }

   private function _existFileLink ($from_item, $file_id) {
      $retour = false;
      $version_id = $from_item->getVersionID();
      if ( empty($version_id) ) {
         $version_id = '0';
      }
      $query = "SELECT * FROM ".$this->addDatabasePrefix("item_link_file");
      $query .= " WHERE item_iid=".encode(AS_DB,$from_item->getItemID());
      $query .= " AND item_vid=".encode(AS_DB,$version_id);
      $query .= ' AND file_id="'.encode(AS_DB,$file_id).'"';
      $result = $this->_db_connector->performQuery($query);
      if (!isset($result)) {
         include_once('functions/error_functions.php');
         trigger_error("Problems loading file links: ".$query, E_USER_WARNING);
      } elseif(empty($result[0])) {
         $retour = false;
      } else {
         $retour = true;
      }
      return $retour;
   }

   ### end file links ###

   function _updateFromBackup ( $data_array ) {

      $success = false;

      if ( empty($data_array['from_version_id']) ) {
         $data_array['from_version_id'] = 0;
      }
      if ( empty($data_array['to_version_id']) ) {
         $data_array['to_version_id'] = 0;
      }

      // is entry allready stored in database ?
      $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table);
      $query .= ' WHERE from_item_id="'.encode(AS_DB,$data_array['from_item_id']).'"';
      $query .= ' AND from_version_id="'.encode(AS_DB,$data_array['from_version_id']).'"';
      $query .= ' AND to_item_id="'.encode(AS_DB,$data_array['to_item_id']).'"';
      $query .= ' AND to_version_id="'.encode(AS_DB,$data_array['to_version_id']).'"';

      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) ) {
         include_once('functions/error_functions.php');
         trigger_error('Problem deleting items from query: "'.$query.'"',E_USER_ERROR);
      } else {

         // now the backup
         $query = '';
         if ( empty($result[0]) ) {
            $query .= 'INSERT INTO '.$this->addDatabasePrefix($this->_db_table).'';
         } else {
            $query .= 'UPDATE '.$this->addDatabasePrefix($this->_db_table).'';
         }

         $query .= ' SET ';
         $first = true;

         foreach ($data_array as $key => $value) {
            if ( empty($result[0])
                 or ( $key != 'from_item_id'
                      and $key != 'from_version_id'
                      and $key != 'to_item_id'
                      and $key != 'to_version_id'
                    )
               ) {
               if ($first) {
                  $first = false;
               } else {
                  $query .= ',';
               }
               $query .= $key.'="'.encode(AS_DB,$value).'"';
            }
         }

         if ( !isset($data_array['deleter_id'])
              or ( empty($data_array['deleter_id'])
                   and !strstr($query,'deleter_id')
                 )
            ) {
            $query .= ',deleter_id=NULL';
         }
         if ( !isset($data_array['deletion_date'])
              or ( empty($data_array['deletion_date'])
                   and !strstr($query,'deletion_date')
                 )
            ) {
            $query .= ',deletion_date=NULL';
         }

         if ( strstr($query,'deletion_date="0"') ) {
            $query = str_replace('deletion_date="0"','deletion_date=NULL',$query);
         }
         if ( strstr($query,'deleter_id="0"') ) {
            $query = str_replace('deleter_id="0"','deleter_id=NULL',$query);
         }

         if ( !empty($query_result) ) {
            $query .= ' WHERE from_item_id="'.encode(AS_DB,$data_array['from_item_id']).'"';
            $query .= ' AND from_version_id="'.encode(AS_DB,$data_array['from_version_id']).'"';
            $query .= ' AND to_item_id="'.encode(AS_DB,$data_array['to_item_id']).'"';
            $query .= ' AND to_version_id="'.encode(AS_DB,$data_array['to_version_id']).'"';
         }
         $query .= ';';

         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or !$result ) {
            include_once('functions/error_functions.php');
            trigger_error('Problem backuping item from query: "'.$query.'"',E_USER_ERROR);
         } else {
            $success = true;
         }
      }
      return $success;
   }

   public function getCountLinksFromItemIDArray ( $id_array, $label_type ) {
      $retour = array();
      if ( !empty($id_array) ) {
         $link_type = '';
         if ( $label_type == 'buzzword' ) {
            $query = 'SELECT to_item_id, count(from_item_id) AS num FROM `'.$this->addDatabasePrefix('links').'` WHERE to_item_id IN ('.implode(',',$id_array).') GROUP BY to_item_id;';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problem counting links from query: "'.$query.'"',E_USER_ERROR);
            } else {
               foreach ( $result as $row ) {
                  $retour[$row['to_item_id']] = $row['num'];
               }
            }
         }elseif ( $label_type == 'mylist' ) {
            $query = 'SELECT to_item_id, count(from_item_id) AS num FROM `'.$this->addDatabasePrefix('links').'` WHERE to_item_id IN ('.implode(',',$id_array).') GROUP BY to_item_id;';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) ) {
               include_once('functions/error_functions.php');
               trigger_error('Problem counting links from query: "'.$query.'"',E_USER_ERROR);
            } else {
               foreach ( $result as $row ) {
                  $retour[$row['to_item_id']] = $row['num'];
               }
            }
         } else {
            include_once('functions/error_functions.php');
            trigger_error($label_type.' not implemented yet',E_USER_ERROR);
         }
      }

      return $retour;
   }

   public function saveLinksMaterialToBuzzword ($new_array,$item_id) {
      $this->setItemTypeLimit(CS_MATERIAL_TYPE);
      $this->setItemIDLimit($item_id);
      $result_array = $this->_performQuery();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      foreach ($result_array as $link) {
         if ( !in_array($link['from_item_id'],$new_array) ) {
            $delete_array[] = $link['from_item_id'];
         } else {
            $nothing_array[] = $link['from_item_id'];
         }
      }
      $insert_array = array_diff($new_array,$nothing_array);
      $this->deleteFromLinks($delete_array,$item_id);
      $this->_insertFromLinks($insert_array,$item_id,'buzzword_for');
   }

   public function saveLinksRubricToBuzzword ($new_array,$item_id,$rubric) {
      $this->setItemTypeLimit($rubric);
      $this->setItemIDLimit($item_id);
      $result_array = $this->_performQuery();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      foreach ($result_array as $link) {
         if ( !in_array($link['from_item_id'],$new_array) ) {
            $delete_array[] = $link['from_item_id'];
         } else {
            $nothing_array[] = $link['from_item_id'];
         }
      }
      $insert_array = array_diff($new_array,$nothing_array);
      $this->deleteFromLinks($delete_array,$item_id);
      $this->_insertFromLinks($insert_array,$item_id,'buzzword_for');
   }

   public function saveLinksRubricToMatrix ($new_array,$item_id,$rubric) {
      $this->setItemTypeLimit($rubric);
      $this->setItemIDLimit($item_id);
      $result_array = $this->_performQuery();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      foreach ($result_array as $link) {
         if ( !in_array($link['from_item_id'],$new_array) ) {
            $delete_array[] = $link['from_item_id'];
         } else {
            $nothing_array[] = $link['from_item_id'];
         }
      }
      $insert_array = array_diff($new_array,$nothing_array);
      $this->deleteFromLinks($delete_array,$item_id);
      $this->_insertFromLinks($insert_array,$item_id,'in_matrix');
   }

   private function _insertFromLinks($insert_array,$item_id,$link_type) {
      foreach ($insert_array as $from_item_id) {
         $temp_array = array();
         $temp_array['from_item_id'] = $from_item_id;
         $temp_array['from_version_id'] = 'NULL';
         $temp_array['to_item_id'] = $item_id;
         $temp_array['to_version_id'] = 'NULL';
         $temp_array['link_type'] = $link_type;
         $temp_array['room_id'] = $this->_environment->getCurrentContextID();
         $this->save($temp_array);
      }
   }
   
   function export_items() {
	   $links_array = $this->_performQuery();
   	$links_xml = new SimpleXMLElementExtended('<links></links>');
   	foreach ($links_array as $link) {
   	   $link_xml = new SimpleXMLElementExtended('<link></link>');
   	   $link_xml->addChildWithCDATA('from_item_id', $link['from_item_id']);
         $link_xml->addChildWithCDATA('from_version_id', $link['from_version_id']);
         $link_xml->addChildWithCDATA('to_item_id', $link['to_item_id']);
         $link_xml->addChildWithCDATA('to_version_id', $link['to_version_id']);
         $link_xml->addChildWithCDATA('link_type', $link['link_type']);
         $link_xml->addChildWithCDATA('context_id', $link['context_id']);
         $link_xml->addChildWithCDATA('deleter_id', $link['deleter_id']);
         $link_xml->addChildWithCDATA('deletion_date', $link['deletion_date']);
         $link_xml->addChildWithCDATA('x', $link['x']);
         $link_xml->addChildWithCDATA('y', $link['y']);
         $this->simplexml_import_simplexml($links_xml, $link_xml);
      }
   	return $links_xml;
	}
   
   function import_items($xml, $top_item, &$options) {
      if ($xml != null) {
         foreach ($xml->children() as $link) {
            $new_from_item_id = $options[(string)$link->from_item_id[0]];
            $new_to_item_id = $options[(string)$link->to_item_id[0]];
            if (($new_from_item_id != '') && ($new_to_item_id != '')) {
               $link_array = array();
               $link_array['from_item_id'] = $new_from_item_id;
               $link_array['from_version_id'] = (string)$link->from_version_id[0];
               $link_array['to_item_id'] = $new_to_item_id;
               $link_array['to_version_id'] = (string)$link->to_version_id[0];
               $link_array['link_type'] = (string)$link->link_type[0];
               $link_array['room_id'] = $top_item->getItemId();
               $link_array['deleter_id'] = (string)$link->deleter_id[0];
               $link_array['deletion_date'] = (string)$link->deletion_date[0];
               $link_array['x'] = (string)$link->x[0];
               $link_array['y'] = (string)$link->y[0];
               $this->_create($link_array);
            }
         }
      }
   }
}
?>