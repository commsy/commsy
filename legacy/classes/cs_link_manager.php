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
class cs_link_manager extends cs_manager implements cs_export_import_interface {

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

  var $_cache = array();

  /**
   * string - containing the order limit for the select statement
   */
  var $_order;

  var $_discussion_type_limit;
  /**
   * limits for selecting link items
   */
  var $_linked_item = NULL;
  var $_second_linked_item = NULL;
  var $_link_type_limit = NULL;
  var $_link_type_array_limit = NULL;

  var $_entry_limit = NULL;
  var $_sorting_place_limit = NULL;

  /** constructor: cs_links_manager
    * the only available constructor, initial values for internal variables
    *
    * @param object cs_environment the environment
    */
  function __construct($environment) {
      cs_manager::__construct($environment);
      $this->_db_table = 'link_items';
  }


  /******************* reset methods ************/


  /** reset limits
    * reset limits of this class: context limit, delete limit
    */
   function resetLimits () {
      $this->_order = NULL;
      $this->_room_limit =NULL;
      $this->_linked_item = NULL;
      $this->_second_linked_item = NULL;
      #$this->_version_id_limit = NULL;
      $this->_link_type_limit = NULL;
      $this->_link_type_array_limit = array();
      $this->_sorting_place_limit = NULL;
      $this->_entry_limit = NULL;
   }

  /** reset data
    * reset data of this class
    */
   function resetData () {
      $this->_data = array();
   }

  /** reset cache
    * reset cache of this class
    */
   private function _resetCache () {
      $this->_cache = array();
   }

   function setEntryLimit($count){
      $this->_entry_limit = $count;
   }

   /** reset type_limit
    * reset type_limit of this class
    */
   function resetTypeLimit () {
      $this->_link_type_limit = NULL;
      $this->_link_type_array_limit = array();
   }

  /** reset order
    * reset order of this class
    */
   function resetOrder () {
      unset($this->_order);
   }


  /************** set methods ******************/

  /** sets the type limit
    *
    * @param string
    */
   function setTypeLimit ($type) {
      $this->_link_type_limit = $type;
   }

  /** sets the type limit
    *
    * @param string
    */
   function setTypeArrayLimit ($type) {
      $this->_link_type_array_limit = $type;
   }

  /** sets the rubric type limit
    */
   function setMaterialLimit(){
      $this->setTypeLimit(CS_MATERIAL_TYPE);
   }
   function setTopicLimit(){
      $this->setTypeLimit(CS_TOPIC_TYPE);
   }
   function setRoomLimit($limit){
      $this->_room_limit = $limit;
   }

   function sortbySortingPlace () {
      $this->_setOrderLimit('sorting_place');
   }
   function setSortingPlaceLimit() {
      $this->_sorting_place_limit = true;
   }

   function _setOrderLimit ($value) {
      $this->_order = $value;
   }

   /** set linked_item
    * this method sets a linked-item as a limit
    *
    * @param object of a linked-item
    */
   function setLinkedItemLimit ($object) {
      $this->_linked_item = $object;
   }

   /** set linked_item
    * this method sets a linked-item as a limit
    *
    * @param object of a linked-item
    */
   function setSecondLinkedItemLimit ($object) {
      $this->_second_linked_item = $object;
   }

   /*********************************************/

   /** build a new links item
    * this method returns a new EMTPY user item
    *
    * @return object cs_item a new EMPTY user
    */
   function getNewItem () {
      include_once('classes/cs_link_item.php');
      return new cs_link_item($this->_environment);
   }

  /** get all links
    * this method get all links
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
   function _performQuery ($mode = 'select', $with_linked_items= true) {
      $data = array();
      if ($mode == 'count') {
         $query = 'SELECT count( DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id';
      } else {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.*';
      }
      $query .= ' FROM '.$this->addDatabasePrefix('link_items').' ';

      $query .= ' WHERE 1';

      if ( isset($this->_linked_item) or isset($this->_link_type_limit) ) {
         $query .= ' AND ((';
      }
      if ( isset($this->_linked_item) ) {
         $query .= ' first_item_id ="'.encode(AS_DB,$this->_linked_item->getItemID()).'"';
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND second_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) {
         if ( !empty($this->_linked_item) or !empty($this->_second_linked_item) ) {
            $query .= ' AND';
         }
         $query .= ' second_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"';
      } elseif (!empty($this->_link_type_array_limit) ) {
         if ( !empty($this->_linked_item) or !empty($this->_second_linked_item) ) {
            $query .= ' AND';
         }
         $query .= ' (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' second_item_type ="'.encode(AS_DB,$limit).'"';
            } else {
               $query .= ' OR second_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ')';
      }
      if ( isset($this->_linked_item) or isset($this->_link_type_limit) ) {
         $query .= ')';
         $query .= ' OR (';
      }
      if ( isset($this->_linked_item) ) {
         $query .= ' second_item_id ="'.encode(AS_DB,$this->_linked_item->getItemID()).'"';
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND first_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) {
         if ( !empty($this->_linked_item) or !empty($this->_second_linked_item) ) {
            $query .= ' AND';
         }
         $query .= ' first_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"';
      } if (!empty($this->_link_type_array_limit) ) {
         if ( !empty($this->_linked_item) or !empty($this->_second_linked_item) ) {
            $query .= ' AND';
         }
         $query .= ' (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' first_item_type ="'.encode(AS_DB,$limit).'"';
            }else{
               $query .= ' OR first_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ' )';
      }
      if ( isset($this->_linked_item) or isset($this->_link_type_limit) ) {
         $query .= '))';
      }
      $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('link_items').'.deletion_date IS NULL';
      if (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
#       else {
#         $query .= ' AND link_items.context_id = "'.$this->_environment->getCurrentContextID().'"';
#      }

      if ( isset($this->_sorting_place_limit)
           and !empty($this->_sorting_place_limit)
           and $this->_sorting_place_limit
         ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.sorting_place IS NOT NULL';
      }

      // group to eliminate versions
      // there are no version_ids in this table ???????????
      #if ( isset($this->_linked_item) ) {
      #   $query .= ' GROUP BY link_items.item_id';
      #}

      // order
      if ( !empty($this->_order) ) {
         if ( $this->_order == 'sorting_place') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.sorting_place ASC, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
         }
      } else {
         $query .= ' ORDER BY '.$this->addDatabasePrefix('link_items').'.creation_date DESC';
      }
      if (isset($this->_entry_limit)) {
         $query .= ' LIMIT 0, '.encode(AS_DB,$this->_entry_limit);
      }

      $cache_exists = false;
      if (!empty($this->_cache)){
         foreach ($this->_cache as $cache_query){
            if ($cache_query['query'] == $query){
               $cache_exists = true;
               $result = $cache_query['result'];
            }
         }
      }

      if (!$cache_exists){
         // perform query
         $r = $this->_db_connector->performQuery($query);
         if (!isset($r)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $result = $r;
            if ( $this->_cache_on ) {
               $temp = array();
               $temp['query'] = $query;
               $temp['result'] = $r;
               $this->_cache[] = $temp;
            }
         }
      }
      return $result;

   }

  /** get all links
    * this method get all links
    *
    * @param string  type       type of the link
    * @param string  mode       one of count, select, select_with_item_type_from
    */
   function _performQuery2 ($mode = 'select', $with_linked_items= true) {
      $data = array();
      if ($mode == 'count') {
         $query = 'SELECT count( DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id) AS count';
      } elseif ($mode == 'id_array') {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.item_id';
      } else {
         $query = 'SELECT DISTINCT '.$this->addDatabasePrefix('link_items').'.*';
      }
      $query .= ' FROM '.$this->addDatabasePrefix('link_items').' ';

      $query .= ' WHERE 1';

      if ( isset($this->_id_array_limit) or isset($this->_link_type_limit) ) { // id-Array // user
         $query .= ' AND ((';
      }
      if ( isset($this->_id_array_limit) and !empty($this->_id_array_limit) ) { // id-Array
         $query .= ' first_item_id IN ('.implode(',',$this->_id_array_limit).')';
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND second_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) { // user
         if ( !empty($this->_id_array_limit) or !empty($this->_second_linked_item) ) { // id-Array
            $query .= ' AND';
         }
         $query .= ' second_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"'; //user
      } elseif (!empty($this->_link_type_array_limit) ) {
         if ( !empty($this->_id_array_limit) or !empty($this->_second_linked_item) ) { // id-Array
            $query .= ' AND';
         }
         $query .= ' (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' second_item_type ="'.encode(AS_DB,$limit).'"';
            } else {
               $query .= ' OR second_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ')';
      }
      if ( isset($this->_id_array_limit) or isset($this->_link_type_limit) ) {  // id-Array // user
         $query .= ')';
         $query .= ' OR (';
      }
      if ( isset($this->_id_array_limit) and !empty($this->_id_array_limit) ) { // id-Array
         $query .= ' second_item_id IN ('.implode(',',$this->_id_array_limit).')'; // id-Array
      }
      if (isset($this->_second_linked_item) ) {
         $query .= ' AND first_item_id ="'.encode(AS_DB,$this->_second_linked_item->getItemID()).'"';
      }
      if (!empty($this->_link_type_limit) ) { // user
         if ( !empty($this->_id_array_limit) or !empty($this->_second_linked_item) ) { // id-Array
            $query .= ' AND';
         }
         $query .= ' first_item_type ="'.encode(AS_DB,$this->_link_type_limit).'"'; // user
      } if (!empty($this->_link_type_array_limit) ) {
         if ( !empty($this->_id_array_limit) or !empty($this->_second_linked_item) ) { // id-Array
            $query .= ' AND';
         }
         $query .= ' (';
         $first = true;
         foreach ($this->_link_type_array_limit as $limit){
            if ($first){
               $first = false;
               $query .= ' first_item_type ="'.encode(AS_DB,$limit).'"';
            }else{
               $query .= ' OR first_item_type ="'.encode(AS_DB,$limit).'"';
            }
         }
         $query .= ' )';
      }
      if ( isset($this->_id_array_limit) or isset($this->_link_type_limit) ) { // id-Array // user
         $query .= '))';
      }
      $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('link_items').'.deletion_date IS NULL';
      if (isset($this->_room_limit)) {
         $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
      }
#       else {
#         $query .= ' AND link_items.context_id = "'.$this->_environment->getCurrentContextID().'"';
#      }

      if ( isset($this->_sorting_place_limit)
           and !empty($this->_sorting_place_limit)
           and $this->_sorting_place_limit
         ) {
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.sorting_place IS NOT NULL';
      }

      // group to eliminate versions
      // there are no version_ids in this table ???????????
      #if ( isset($this->_linked_item) ) {
      #   $query .= ' GROUP BY link_items.item_id';
      #}

      // order
      if ( !empty($this->_order) ) {
         if ( $this->_order == 'sorting_place') {
            $query .= ' ORDER BY '.$this->addDatabasePrefix($this->_db_table).'.sorting_place ASC, '.$this->addDatabasePrefix($this->_db_table).'.creation_date DESC';
         }
      } else {
         $query .= ' ORDER BY '.$this->addDatabasePrefix('link_items').'.creation_date DESC';
      }
      if (isset($this->_entry_limit)) {
         $query .= ' LIMIT 0, '.encode(AS_DB,$this->_entry_limit);
      }

      $cache_exists = false;
      if (!empty($this->_cache)){
         foreach ($this->_cache as $cache_query){
            if ($cache_query['query'] == $query){
               $cache_exists = true;
               $result = $cache_query['result'];
            }
         }
      }

      if (!$cache_exists){
         // perform query
         $r = $this->_db_connector->performQuery($query);
         if (!isset($r)) {
            include_once('functions/error_functions.php');
            trigger_error('Problems with links: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
         } else {
            $result = $r;
            if ( $this->_cache_on ) {
               $temp = array();
               $temp['query'] = $query;
               $temp['result'] = $r;
               $this->_cache[] = $temp;
            }
         }
      }
      return $result;

   }

   /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select ($with_linked_items = true) {
      if ( isset($this->_linked_item) ) {
         $result = $this->_performQuery('select', $with_linked_items);
         $this->_data = new cs_list();
         $this->_id_array = NULL;
         if (!$with_linked_items){
            foreach ($result as $query_result) {
               $item = $this->_buildItem($query_result);
               $this->_data->add($item);
            }
         } else {
            $link_list = new cs_list();
            $item_id_array = array();
            foreach ($result as $query_result) {
               if ($this->_linked_item->getItemID() == $query_result['first_item_id']) {
                  $item_id_array[] = $query_result['second_item_id'];
               } else {
                  $item_id_array[] = $query_result['first_item_id'];
               }
               $item = $this->_buildItem($query_result);
               $link_list->add($item);
            }
            $this->_data = $link_list;
         }
        if ( isset($this->_order)
             and !empty($this->_order)
             and $this->_order == 'sorting_place'
           ) {
           $item = $this->_data->getFirst();
           $link_list1 = new cs_list();
           $link_list2 = new cs_list();
           while ($item) {
              if ($item->getSortingPlace()) {
                 $link_list1->add($item);
              } else {
                 $link_list2->add($item);
              }
              $item = $this->_data->getNext();
           }
           $link_list1->addList($link_list2);
           $this->_data = $link_list1;
           unset($link_list1);
           unset($link_list2);
         }
      } else {
         parent::select();
      }
   }

  /** select items limited by limits
   * this method returns a list (cs_list) of items within the database limited by the limits.
   * depends on _performQuery(), which must be overwritten
   */
   function select2 ($with_linked_items = true) {
      if ( isset($this->_id_array_limit) ) {
         $result = $this->_performQuery2('select', $with_linked_items);
         $this->_data = new cs_list();
         $this->_id_array = NULL;
         if (!$with_linked_items){
            foreach ($result as $query_result) {
               $item = $this->_buildItem($query_result);
               $this->_data->add($item);
            }
         } else {
            $link_list = new cs_list();
            $item_id_array = array();
            foreach ($result as $query_result) {
               if ($this->_linked_item->getItemID() == $query_result['first_item_id']) {
                  $item_id_array[] = $query_result['second_item_id'];
               } else {
                  $item_id_array[] = $query_result['first_item_id'];
               }
               $item = $this->_buildItem($query_result);
               $link_list->add($item);
            }
            $this->_data = $link_list;
         }
        if ( isset($this->_order)
             and !empty($this->_order)
             and $this->_order == 'sorting_place'
           ) {
           $item = $this->_data->getFirst();
           $link_list1 = new cs_list();
           $link_list2 = new cs_list();
           while ($item) {
              if ($item->getSortingPlace()) {
                 $link_list1->add($item);
              } else {
                 $link_list2->add($item);
              }
              $item = $this->_data->getNext();
           }
           $link_list1->addList($link_list2);
           $this->_data = $link_list1;
           unset($link_list1);
           unset($link_list2);
         }
      } else {
         parent::select();
      }
   }

   function _selectForExport () {
      $result = $this->_performQuery('select',false);
      $this->_id_array = NULL;
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data = '<'.$this->_db_table.'_list>'.LF;
      } else {
         $this->_data = new cs_list();
      }
      foreach ($result as $query_result) {
         if ( isset($this->_output_limit)
              and !empty($this->_output_limit)
              and $this->_output_limit == 'XML'
            ) {
            if ( isset($query_result)
                 and !empty($query_result) ) {
               $this->_data .= '<'.$this->_db_table.'_item>'.LF;
               foreach ($query_result as $key => $value) {
                  $value = str_replace('<','lt_commsy_export',$value);
                  $value = str_replace('>','gt_commsy_export',$value);
                  $value = str_replace('&','and_commsy_export',$value);
                  if ( $key == 'extras' ) {
                     $value = serialize($value);
                  }
                  $this->_data .= '<'.$key.'>'.$value.'</'.$key.'>'.LF;
               }
               $this->_data .= '</'.$this->_db_table.'_item>'.LF;
            }
         } else {
            $item = $this->_buildItem($query_result);
            $this->_data->add($item);
         }
         //$this->_id_array[] = $query_result['item_id'];
      }
      if ( isset($this->_output_limit)
           and !empty($this->_output_limit)
           and $this->_output_limit == 'XML'
         ) {
         $this->_data .= '</'.$this->_db_table.'_list>'.LF;
      }
   }

  /** update a link item - internal, do not use -> use method save
    * this method updates a link item
    *
    * @param object cs_item link_item
    */
  function _update ($link_item) {   // wird nicht benötigt???
     parent::_update($link_item);
     $first_item = $link_item->getFirstLinkedItem();
     $second_item = $link_item->getSecondLinkedItem();
     $modificator = $link_item->getModificatorItem();
     $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'first_item_id="'.encode(AS_DB,$first_item->getItemID()).'",'.
              'second_item_id="'.encode(AS_DB,$second_item->getItemID()).'",'.
              'first_item_type="'.encode(AS_DB,$link_item->getFirstLinkedItemType()).'",'.
              'second_item_type="'.encode(AS_DB,$link_item->getSecondLinkedItemType()).'",';
              
     if ($link_item->getSortingPlace() != '') {
        $query .= 'sorting_place="'.encode(AS_DB,$link_item->getSortingPlace()).'",';
     }
     
     $query .= 'sorting_place="'.encode(AS_DB,$link_item->getSortingPlace()).'",'.
               'extras="'.encode(AS_DB,serialize($link_item->getExtraInformation())).'"'.
               ' WHERE item_id="'.encode(AS_DB,$link_item->getItemID()).'"';

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems updating link item from query: "'.$query.'"',E_USER_WARNING);
     }
  }

  function getItemList ($id_array) {
     return $this->_getItemList('link_items', $id_array);
  }


  /** create a link item - internal, do not use -> use method save
    * this method creates a link item
    *
    * @param object cs_item link_item
    */
  function _create ($link_item) {
     $query = 'INSERT INTO '.$this->addDatabasePrefix('items').' SET '.
              'context_id="'.encode(AS_DB,$link_item->getContextID()).'",'.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'type="link_item"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems creating link item from query: "'.$query.'"',E_USER_WARNING);
        $this->_create_id = NULL;
     } else {
        $this->_create_id = $result;
        $link_item->setItemID($this->getCreateID());

        $creator = $link_item->getCreatorItem();
        $creator_id = $creator->getItemID();
        $current_datetime = getCurrentDateTimeInMySQL();
        $query  = 'INSERT INTO '.$this->addDatabasePrefix('link_items').' SET '.
                  'item_id="'.encode(AS_DB,$link_item->getItemID()).'",';
        $query .= 'context_id="'.encode(AS_DB,$link_item->getContextID()).'",';
        $first_item = $link_item->getFirstLinkedItem();
        if ( isset($first_item) ) {
           $first_item_id = $first_item->getItemID();
           $first_item_type = $first_item->getItemType();
        } else {
           $first_item_id = $link_item->getFristLinkedItemID();
           $first_item_type = $link_item->getFirstLinkedItemID();
        }
        $second_item = $link_item->getSecondLinkedItem();
        if ( isset($second_item) ) {
           $second_item_id = $second_item->getItemID();
           $second_item_type = $second_item->getItemType();
        } else {
           $second_item_id = $link_item->getSecondLinkedItemID();
           $second_item_type = $link_item->getSecondLinkedItemID();
        }

        if (isset($creator_id)) {
            $query .= 'creator_id="'.encode(AS_DB,$creator_id).'",';
        }
        
        $query .= 'creation_date="'.$current_datetime.'",'.
                  'modification_date="'.$current_datetime.'",'.
                  'first_item_id="'.encode(AS_DB,$first_item_id).'",'.
                  'second_item_id="'.encode(AS_DB,$second_item_id).'",'.
                  'first_item_type="'.encode(AS_DB,$first_item_type).'",'.
                  'second_item_type="'.encode(AS_DB,$second_item_type).'",';
                  
        if ($link_item->getSortingPlace() != '') {
            $query .= 'sorting_place="'.encode(AS_DB,$link_item->getSortingPlace()).'",';
        }
        
        $query .= 'extras="'.encode(AS_DB,serialize($link_item->getExtraInformation())).'"';
        $result = $this->_db_connector->performQuery($query);
        if ( !isset($result) ) {
           include_once('functions/error_functions.php');
           trigger_error('Problems creating link item from query: "'.$query.'"',E_USER_WARNING);
           $query = 'DELETE FROM '.$this->addDatabasePrefix('items').' WHERE item_id="'.$this->getCreateID().'"';
           $result = $this->_db_connector->performQuery($query);
           $this->_create_id = NULL;
        }
        unset($creator);
        unset($first_item);
        unset($second_item);
     }
  }

  /** delete a link_item
    *
    * @param integer item_id the link_item
    */
  function delete ($item_id) {
     $current_datetime = getCurrentDateTimeInMySQL();
     $current_user = $this->_environment->getCurrentUserItem();
     $user_id = $current_user->getItemID() ?: 0;
     $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.$current_datetime.'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE item_id="'.encode(AS_DB,$item_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting link_items from query: "'.$query.'"',E_USER_WARNING);
     } else {
        // delete item from table 'items'
        parent::delete($item_id);
     }

     // reset cache
     $this->_resetCache();
  }

  function deleteAllLinkItemsInCommunityRoom($item_id,$context_id){
      $current_user = $this->_environment->getCurrentUserItem();
      $user_id = $current_user->getItemID() ?: 0;
     $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB,$user_id).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ' OR second_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ')';
     $query .= ' AND context_id ="'.encode(AS_DB,$context_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

   function getCountExistingLinkItemsOfUser($user_id){
     $query = 'SELECT count('.$this->addDatabasePrefix('link_items').'.item_id) AS count';
     $query .= ' FROM '.$this->addDatabasePrefix('link_items');
     $query .= ' WHERE 1';

     if (isset($this->_room_limit)) {
        $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB,$this->_room_limit).'"';
     } else {
        $query .= ' AND '.$this->addDatabasePrefix('link_items').'.context_id = "'.encode(AS_DB,$this->_environment->getCurrentContextID()).'"';
     }
     $query .= ' AND NOT('.$this->addDatabasePrefix('link_items').'.first_item_type="user" AND '.$this->addDatabasePrefix('link_items').'.second_item_type="group")';
     $query .= ' AND '.$this->addDatabasePrefix('link_items').'.deleter_id IS NULL';
     $query .= ' AND '.$this->addDatabasePrefix('link_items').'.creator_id ="'.encode(AS_DB,$user_id).'"';

     // perform query
     $result = $this->_db_connector->performQuery($query);
     if (!isset($result) or empty($result[0]['count'])) {
         include_once('functions/error_functions.php');
         trigger_error('Problems selecting items from query: "'.$query.'"',E_USER_WARNING);
     } else {
         return $result[0]['count'];
     }
   }

  /** delete link , but it is just an update
    * this method deletes all links from an item, but only as an update to restore it later and for evaluation
    *
    * @param integer item_id       id of the item
    * @param integer version_id    version id of the item
    */
  function deleteLinksBecauseItemIsDeleted ($item_id, $version_id=NULL) {
     $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'deletion_date="'.getCurrentDateTimeInMySQL().'",'.
              'deleter_id="'.encode(AS_DB,$this->_current_user->getItemID()).'"'.
              ' WHERE (first_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ') OR (second_item_id="'.encode(AS_DB,$item_id).'"';
     $query .= ')';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // delete in items table too
     $query = '
        UPDATE ' .
           $this->addDatabasePrefix('link_items') . '
        SET
           deletion_date = "' . getCurrentDateTimeInMySQL() . '",
           deleter_id = "' . encode(AS_DB, $this->_current_user->getItemID()) . '"
        WHERE
           item_id = "' . encode(AS_DB, $item_id) . '"
     ';
     $result = $this->_db_connector->performQuery($query);
     if(!isset($result) || !$result) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting(updating) an item from query: "' . $query . '"', E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

  function undeleteLinks ($item) {
     $query = 'UPDATE '.$this->addDatabasePrefix('link_items').' SET '.
              'modification_date="'.getCurrentDateTimeInMySQL().'",'.
              'deletion_date=NULL,'.
              'deleter_id=NULL'.
              ' WHERE deletion_date>="'.encode(AS_DB,$item->getDeletionDate()).'"'.
              ' AND (first_item_id="'.encode(AS_DB,$item->getItemID()).'"'.
              ' OR second_item_id="'.encode(AS_DB,$item->getItemID()).'")';
     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');
        trigger_error('Problems deleting (updating) links of an item from query: "'.$query.'"',E_USER_WARNING);
     }

     // reset cache
     $this->_resetCache();
  }

  function getModiefiedItemIDArray($type, $creator_id){
     $query ='';
     switch ( $type ) {
        case CS_MATERIAL_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('materials').'.item_id FROM '.$this->addDatabasePrefix('materials').' WHERE '.$this->addDatabasePrefix('materials').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('materials').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('materials').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('materials').'.modification_date DESC, '.$this->addDatabasePrefix('materials').'.title ASC';
           break;
        case CS_PROJECT_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('room').'.item_id FROM '.$this->addDatabasePrefix('room').' WHERE '.$this->addDatabasePrefix('room').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('room').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('room').'.deletion_date IS NULL AND '.$this->addDatabasePrefix('room').'.type="project" ORDER BY '.$this->addDatabasePrefix('room').'.modification_date DESC, '.$this->addDatabasePrefix('room').'.title ASC';
           break;
        case CS_ANNOUNCEMENT_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('announcement').'.item_id FROM '.$this->addDatabasePrefix('announcement').' WHERE '.$this->addDatabasePrefix('announcement').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('announcement').'.deleter_id IS NULL  AND '.$this->addDatabasePrefix('announcement').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('announcement').'.modification_date DESC';
           break;
        case CS_DISCUSSION_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('discussions').'.item_id FROM '.$this->addDatabasePrefix('discussions').' WHERE '.$this->addDatabasePrefix('discussions').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('discussions').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('discussions').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('discussions').'.modification_date DESC, '.$this->addDatabasePrefix('discussions').'.title DESC';
           break;
        case CS_TODO_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('todos').'.item_id FROM '.$this->addDatabasePrefix('todos').' WHERE '.$this->addDatabasePrefix('todos').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('todos').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('todos').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('todos').'.modification_date DESC';
           break;
        case CS_DATE_TYPE:
           $query ='SELECT DISTINCT '.$this->addDatabasePrefix('dates').'.item_id FROM '.$this->addDatabasePrefix('dates').' WHERE '.$this->addDatabasePrefix('dates').'.creator_id ="'.encode(AS_DB,$creator_id).'" AND '.$this->addDatabasePrefix('dates').'.deleter_id IS NULL AND '.$this->addDatabasePrefix('dates').'.deletion_date IS NULL ORDER BY '.$this->addDatabasePrefix('dates').'.datetime_start ASC';
           break;
     }
     // perform query

      $result = $this->_db_connector->performQuery($query);
      $id_array = array();
      if ( isset($result) ) {
         foreach ($result as $query_result) {
            $id_array[] = $query_result['item_id'];
         }
      }
      if ($type =='CS_DISCUSSION_TYPE'){
         $query ='SELECT DISTINCT '.$this->addDatabasePrefix('discussionarticless').'.item_id FROM '.$this->addDatabasePrefix('discussionarticles').' WHERE '.$this->addDatabasePrefix('discussionarticles').'.creator_id ="'.encode(AS_DB,$creator_id).'" OR '.$this->addDatabasePrefix('discussionarticles').'.deleter_id IS NULL  ORDER BY '.$this->addDatabasePrefix('discussionarticle').'.modification_date DESC, '.$this->addDatabasePrefix('discussionarticle').'.subject DESC';
         $result = $this->_db_connector->performQuery($query);
         $id_array = array();
         if ( isset($result) ) {
            foreach ($result as $query_result) {
               $id_array[] = $query_result['item_id'];
            }
         }
      }

      if ( !isset($id_array[0]) ) {
         return array();
      } else {
         return $id_array;
      }
  }

  function moveRoom ($roomMover) {
     $query = "UPDATE ".$this->addDatabasePrefix("link_items")." SET ";
     $query .= " WHERE context_id = '".encode(AS_DB,$roomMover->getRoomId())."'";

     $result = $this->_db_connector->performQuery($query);
     if ( !isset($result) or !$result ) {
        include_once('functions/error_functions.php');trigger_error('Problems creating links from query: "'.$query.'"',E_USER_WARNING);
     }
  }

   function mergeAccounts ($new_id, $old_id) {
     parent::mergeAccounts($new_id,$old_id);

     $query = 'SELECT * FROM '.$this->addDatabasePrefix('link_items').' WHERE creator_id = "'.encode(AS_DB,$new_id).'" AND first_item_id ="'.encode(AS_DB,$old_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( isset($result) ) {
        foreach ( $result as $row ) {
           $update = "UPDATE ".$this->addDatabasePrefix("link_items")." SET ";
           $update.= " first_item_id = ".encode(AS_DB,$new_id);
           $update.= " WHERE item_id = ".$row['item_id'];

           $result2 = $this->_db_connector->performQuery($update);
           if ( !isset($result2) or !$result2 ) {
              include_once('functions/error_functions.php');trigger_error('Problems creating link_items: "'.$this->_dberror.'" from query: "'.$query.'"',E_USER_WARNING);
           }
        }
     }

     $query = 'SELECT * FROM '.$this->addDatabasePrefix('link_items').' WHERE creator_id = "'.encode(AS_DB,$new_id).'" AND second_item_id ="'.encode(AS_DB,$old_id).'"';
     $result = $this->_db_connector->performQuery($query);
     if ( isset($result) ) {
        foreach ( $result as $row ) {
           $update = "UPDATE ".$this->addDatabasePrefix("link_items")." SET ";
           $update.= " second_item_id = ".encode(AS_DB,$new_id);
           $update.= " WHERE item_id = ".$row['item_id'];

           $result2 = $this->_db_connector->performQuery($update);
           if ( !isset($result2) or !$result2 ) {
              include_once('functions/error_functions.php');
              trigger_error('Problems creating link_items from query: "'.$query.'"',E_USER_WARNING);
           }
        }
     }
   }

   function cleanSortingPlaces ($linked_item) {
      $item_id = $linked_item->getItemID();
      $query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place=NULL WHERE first_item_id="'.encode(AS_DB,$item_id).'" OR second_item_id="'.encode(AS_DB,$item_id).'";';
      $result = $this->_db_connector->performQuery($query);
      if ( !isset($result) or !$result ) {
         include_once('functions/error_functions.php');
         trigger_error('Problems cleaning sorting place at table '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
      }
   }

   function saveSortingPlaces ($value_array) {
      if ( isset($value_array)
           and !empty($value_array)
           and is_array($value_array)
         ) {
         foreach ($value_array as $value) {
            $item_id = $value['item_id'];
            $place = $value['place'];

            $query = '
               UPDATE ' .
                  $this->addDatabasePrefix($this->_db_table) . '
               SET
                  sorting_place="' . encode(AS_DB, $place) . '",
                  modification_date = "' . getCurrentDateTimeinMySQL() . '"
               WHERE
                  item_id="' . encode(AS_DB, $item_id) . '";
            ';

            //$query = 'UPDATE '.$this->addDatabasePrefix($this->_db_table).' SET sorting_place="'.encode(AS_DB,$place).'" WHERE item_id="'.encode(AS_DB,$item_id).'";';
            $result = $this->_db_connector->performQuery($query);
            if ( !isset($result) or !$result ) {
               include_once('functions/error_functions.php');
               trigger_error('Problems saveing sorting place at table '.$this->_db_table.' from query: "'.$query.'"',E_USER_WARNING);
            }
         }
      }
   }

   public function saveLinkItemsMaterialToItem ($new_array,$item) {
      $type = CS_MATERIAL_TYPE;
      $this->setTypeLimit($type);
      $this->setLinkedItemLimit($item);
      $this->select(false);
      $result_list = $this->get();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      if ( $result_list->isNotEmpty() ) {
         $link_item = $result_list->getFirst();
         while ($link_item) {
            if ( $link_item->getFirstLinkedItemType() == $type
                 and !in_array($link_item->getFirstLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } elseif ( $link_item->getSecondLinkedItemType() == $type
                 and !in_array($link_item->getSecondeLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } else {
               if ( $link_item->getFirstLinkedItemType() == $type ) {
                  $nothing_array[] = $link_item->getFirstLinkedItemID();
               } else {
                  $nothing_array[] = $link_item->getSecondLinkedItemID();
               }
            }
            $link_item = $result_list->getNext();
         }
      }
      unset($result_list);
      $insert_array = array_diff($new_array,$nothing_array);
      foreach ( $delete_array as $item_id ) {
         $this->delete($item_id);
      }
      foreach ($insert_array as $item_id) {
         $new_link_item = $this->getNewItem();
         $new_link_item->setFirstLinkedItemID($item_id);
         $new_link_item->setFirstLinkedItemType($type);
         $new_link_item->setSecondLinkedItemID($item->getItemID());
         $new_link_item->setSecondLinkedItemType($item->getType());
         $new_link_item->setContextID($this->_environment->getCurrentContextID());
         $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
         $new_link_item->save();
      }
   }

   public function saveLinkItemsRubricToItem ($new_array,$item,$rubric) {
      $type = $rubric;
      $this->setTypeLimit($type);
      $this->setLinkedItemLimit($item);
      $this->select(false);
      $result_list = $this->get();
      $insert_array = array();
      $nothing_array = array();
      $delete_array = array();
      if ( $result_list->isNotEmpty() ) {
         $link_item = $result_list->getFirst();
         while ($link_item) {
            if ( $link_item->getFirstLinkedItemType() == $type
                 and !in_array($link_item->getFirstLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } elseif ( $link_item->getSecondLinkedItemType() == $type
                 and !in_array($link_item->getSecondeLinkedItemID(),$new_array)
               ) {
               $delete_array[] = $link_item->getItemID();
            } else {
               if ( $link_item->getFirstLinkedItemType() == $type ) {
                  $nothing_array[] = $link_item->getFirstLinkedItemID();
               } else {
                  $nothing_array[] = $link_item->getSecondLinkedItemID();
               }
            }
            $link_item = $result_list->getNext();
         }
      }
      unset($result_list);
      $insert_array = array_diff($new_array,$nothing_array);
      foreach ( $delete_array as $item_id ) {
         $this->delete($item_id);
      }
      foreach ($insert_array as $item_id) {
         $new_link_item = $this->getNewItem();
         $new_link_item->setFirstLinkedItemID($item_id);
         $new_link_item->setFirstLinkedItemType($type);
         $new_link_item->setSecondLinkedItemID($item->getItemID());
         $new_link_item->setSecondLinkedItemType($item->getType());
         $new_link_item->setContextID($this->_environment->getCurrentContextID());
         $new_link_item->setCreatorItem($this->_environment->getCurrentUserItem());
         $new_link_item->save();
      }
   }

  /** save a commsy item
    * this method saves a commsy item
    *
    * @param cs_item
    */
  function saveItem ($item) {
     $item_id = $item->getItemID();

     $modifier = $item->getModificatorItem();
     if ( !isset($modifier) ) {
        $user = $this->_environment->getCurrentUser();
        $item->setModificatorItem($user);
     } else {
        $modifier_id = $modifier->getItemID();
        if (empty($modifier_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setModificatorItem($user);
        }
     }

     if (!empty($item_id)) {
        $this->_update($item);
     } else {
        $creator_id = $item->getCreatorID();
        if (empty($creator_id)) {
           $user = $this->_environment->getCurrentUser();
           $item->setCreatorItem($user);
        }
        $this->_create($item);
     }

     //Add modifier to all users who ever edited this section
     if ( $this->_update_with_changing_modification_information ) {
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $link_modifier_item_manager->markEdited($item->getItemID());
     }

     // reset cache
     $this->_resetCache();
  }

  /** get a link item
    *
    * @param integer item_id id of the item
    *
    * @return object cs_link_item a link item
    */
   function getItem ($item_id) {
      $item = NULL;
      if ( !empty($item_id)
           and !empty($this->_cache_object[$item_id])
         ) {
         return $this->_cache_object[$item_id];
      } elseif ( array_key_exists($item_id,$this->_cached_items) ) {
         $item = $this->_buildItem($this->_cached_items[$item_id]);
      } elseif ( !empty($item_id) ) {
         $query = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE '.$this->addDatabasePrefix($this->_db_table).'.item_id = "'.encode(AS_DB,$item_id).'";';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) or empty($result[0]) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one '.$this->_db_table.' item ('.$item_id.').',E_USER_WARNING);
         } else {
            $item = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
         }
      }
      return $item;
   }

   /** Prepares the db_array for the item
    *
    * @param $db_array Contains the data from the database
    *
    * @return array Contains prepared data ( textfunctions applied etc. )
    */
   function _buildItem($db_array) {
      if ( !empty($db_array['extras']) ) {
         include_once('functions/text_functions.php');
         $db_array['extras'] = mb_unserialize($db_array['extras']);
      }
      return parent::_buildItem($db_array);
   }

	public function getItemByFirstAndSecondID ($first_id,$second_id, $deletion_date = false) {
      $item = NULL;
      if ( !empty($first_id)
           and !empty($second_id)
         ) {
         $query  = 'SELECT * FROM '.$this->addDatabasePrefix($this->_db_table).' WHERE';
         if($deletion_date) {
            $query .= ' deletion_date IS NULL AND deleter_id IS NULL AND';
          }
         $query .= ' ('.$this->addDatabasePrefix($this->_db_table).'.first_item_id = "'.encode(AS_DB,$first_id).'"';
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.second_item_id = "'.encode(AS_DB,$second_id).'")';
         $query .= ' OR ('.$this->addDatabasePrefix($this->_db_table).'.first_item_id = "'.encode(AS_DB,$second_id).'"';
         $query .= ' AND '.$this->addDatabasePrefix($this->_db_table).'.second_item_id = "'.encode(AS_DB,$first_id).'")';
        
         $query .= ';';
         $result = $this->_db_connector->performQuery($query);
         if ( !isset($result) ) {
            include_once('functions/error_functions.php');
            trigger_error('Problems selecting one '.$this->_db_table.' item ('.$first_id.','.$second_id.').',E_USER_WARNING);
         } elseif ( !empty($result[0]) ) {
            $item = $this->_buildItem($result[0]);
            if ( $this->_cache_on ) {
               $this->_cached_items[$result[0]['item_id']] = $result[0];
            }
         }
      }
      return $item;
   }
   
   public function getALlLinksByTagIDArray ($contextId, $idArray) {
   	if ( !empty($idArray)
   	) {
   		$inString = "'" . implode("', '", $idArray) . "'";
   		
   		$query = "
   			SELECT
   				first_item_id,
   				first_item_type,
   				second_item_id,
   				second_item_type
   			FROM
   				" . $this->addDatabasePrefix($this->_db_table) . "
   			WHERE
   				" . $this->addDatabasePrefix($this->_db_table) . ".context_id = " . encode(AS_DB, $contextId) . " AND
   				" . $this->addDatabasePrefix($this->_db_table) . ".deletion_date IS NULL AND
   				(
   					" . $this->addDatabasePrefix($this->_db_table) . ".first_item_id IN (" . $inString . ") OR
   					" . $this->addDatabasePrefix($this->_db_table) . ".second_item_id IN (" . $inString . ")
   				);
		";
   		$result = $this->_db_connector->performQuery($query);
   		if ( !isset($result) ) {
   			include_once('functions/error_functions.php');
   			trigger_error('Problems selecting items',E_USER_WARNING);
   		} else {
   			return $result;
   		}
   	}
   }
   
   function export_item($id) {
	   $item = $this->getItem($id);
	
   	$xml = new SimpleXMLElementExtended('<link_item></link_item>');
   	$xml->addChildWithCDATA('item_id', $item->getItemID());
   	$xml->addChildWithCDATA('context_id', $item->getContextID());
   	$xml->addChildWithCDATA('creator_id', $item->getCreatorID());
   	$xml->addChildWithCDATA('deleter_id', $item->getDeleterID());
   	$xml->addChildWithCDATA('creation_date', $item->getCreationDate());
   	$xml->addChildWithCDATA('deletion_date', $item->getDeletionDate());
   	$xml->addChildWithCDATA('modification_date', $item->getModificationDate());
   	$xml->addChildWithCDATA('first_item_id', $item->getFirstLinkedItemID());
   	$xml->addChildWithCDATA('first_item_type', $item->getFirstLinkedItemType());
   	$xml->addChildWithCDATA('second_item_id', $item->getSecondLinkedItemID());
   	$xml->addChildWithCDATA('second_item_type', $item->getSecondLinkedItemType());
   	$xml->addChildWithCDATA('sorting_place', $item->getSortingPlace());

   	$extras_array = $item->getExtraInformation();
      $xmlExtras = $this->getArrayAsXML($xml, $extras_array, true, 'extras');
      $this->simplexml_import_simplexml($xml, $xmlExtras);
   	   	
   	return $xml;
	}
	
   function export_sub_items($xml, $top_item) {
      
   }
   
   function import_item($xml, $top_item, &$options) {
      $item = null;
      if ($xml != null) {
         if (isset($options[(string)$xml->first_item_id[0]]) && isset($options[(string)$xml->second_item_id[0]])) {
            $new_first_item_id = $options[(string)$xml->first_item_id[0]];
            $new_second_item_id = $options[(string)$xml->second_item_id[0]];
            if (($new_first_item_id != '') && ($new_second_item_id != '')) {
               $item_manger = $this->_environment->getItemManager();
               $first_item = $item_manger->getItem($new_first_item_id);
               $second_item = $item_manger->getItem($new_second_item_id);
            
               $item = $this->getNewItem();
               $item->setFirstLinkedItemID($new_first_item_id);
               $item->setFirstLinkedItemType((string)$xml->first_item_type[0]);
               $item->setFirstLinkedItem($first_item);
               $item->setSecondLinkedItemID($new_second_item_id);
               $item->setSecondLinkedItemType((string)$xml->second_item_type[0]);
               $item->setSecondLinkedItem($second_item);
               $item->setSortingPlace((string)$xml->sorting_place[0]);
               $extra_array = $this->getXMLAsArray($xml->extras);
               $item->setExtraInformation($extra_array['extras']);
               $item->save();
            }
         }
      }
      return $item;
   }
	
   function import_sub_items($xml, $top_item, &$options) {
      
   }
}
?>